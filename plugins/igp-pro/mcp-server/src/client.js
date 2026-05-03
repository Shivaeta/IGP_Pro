export class IGPWordPressClient {
  constructor(options) {
    this.baseUrl = normalizeBaseUrl(options.baseUrl || '');
    this.username = options.username || '';
    this.applicationPassword = options.applicationPassword || '';
    this.bearerToken = options.bearerToken || '';
    this.enabled = Boolean(options.enabled);
    this.requestTimeoutMs = Number.isFinite(options.requestTimeoutMs) ? Math.max(1000, options.requestTimeoutMs) : 30000;
    this.maxYamlBytes = Number.isFinite(options.maxYamlBytes) ? Math.max(1000, options.maxYamlBytes) : 200000;
    this.lastRequestAt = 0;
    this.minIntervalMs = 250;
  }

  async ensureReady() {
    if (!this.enabled) {
      throw new Error('IGP MCP Bridge is disabled locally. Set IGP_MCP_ENABLED=true to enable it.');
    }
    if (!this.baseUrl) {
      throw new Error('IGP_WORDPRESS_URL is required.');
    }
    if (!this.bearerToken && (!this.username || !this.applicationPassword)) {
      throw new Error('Provide IGP_WP_BEARER_TOKEN or IGP_WP_USERNAME + IGP_WP_APP_PASSWORD.');
    }
    const status = await this.get('/igp/v1/mcp/status', { skipEnabledCheck: true });
    if (!status?.data?.enabled) {
      throw new Error('WordPress reports that enable_mcp_bridge is disabled. Enable it in IGP Pro settings before using MCP tools.');
    }
    return true;
  }

  async get(path, options = {}) {
    if (!options.skipEnabledCheck) {
      await this.ensureReady();
    }
    return this.request('GET', path);
  }

  async post(path, body = {}, options = {}) {
    if (!options.skipEnabledCheck) {
      await this.ensureReady();
    }
    return this.request('POST', path, body);
  }

  async callTool(toolName, method, path, body = {}) {
    let status = 'success';
    let summary = 'Tool call completed.';
    try {
      if (body && typeof body.yaml === 'string') {
        const bytes = Buffer.byteLength(body.yaml, 'utf8');
        if (bytes > this.maxYamlBytes) {
          throw new Error(`YAML payload is too large: ${bytes} bytes.`);
        }
      }
      const result = method === 'GET' ? await this.get(path) : await this.post(path, body);
      return result;
    } catch (error) {
      status = 'failure';
      summary = error instanceof Error ? error.message : String(error);
      throw error;
    } finally {
      await this.logToolCall(toolName, status, summary).catch(() => undefined);
    }
  }

  async logToolCall(tool, status, summary) {
    if (!this.enabled || !this.baseUrl) return;
    await this.request('POST', '/igp/v1/mcp/log', { tool, status, summary }, { noThrow: true });
  }

  async request(method, path, body, options = {}) {
    await this.rateLimit();
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), this.requestTimeoutMs);
    const headers = {
      Accept: 'application/json',
      'X-IGP-MCP-Bridge': '1'
    };
    if (method !== 'GET') {
      headers['Content-Type'] = 'application/json';
    }
    if (this.bearerToken) {
      headers.Authorization = `Bearer ${this.bearerToken}`;
    } else {
      headers.Authorization = `Basic ${Buffer.from(`${this.username}:${this.applicationPassword}`).toString('base64')}`;
    }
    try {
      const response = await fetch(`${this.baseUrl}/wp-json${path}`, {
        method,
        headers,
        body: method === 'GET' ? undefined : JSON.stringify(body || {}),
        signal: controller.signal
      });
      const text = await response.text();
      let payload = null;
      try {
        payload = text ? JSON.parse(text) : null;
      } catch (error) {
        payload = { raw: text };
      }
      if (!response.ok && !options.noThrow) {
        const message = payload?.message || payload?.data?.error?.message || payload?.error?.message || `WordPress REST request failed with HTTP ${response.status}.`;
        const wrapped = new Error(message);
        wrapped.status = response.status;
        wrapped.payload = payload;
        throw wrapped;
      }
      return payload;
    } finally {
      clearTimeout(timeout);
    }
  }

  async rateLimit() {
    const now = Date.now();
    const wait = this.minIntervalMs - (now - this.lastRequestAt);
    if (wait > 0) {
      await new Promise((resolve) => setTimeout(resolve, wait));
    }
    this.lastRequestAt = Date.now();
  }
}

function normalizeBaseUrl(value) {
  return String(value || '').replace(/\/+$/, '');
}
