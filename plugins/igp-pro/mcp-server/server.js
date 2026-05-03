#!/usr/bin/env node
import { McpServer } from '@modelcontextprotocol/server';
import { StdioServerTransport } from '@modelcontextprotocol/server/stdio';
import * as z from 'zod/v4';
import { IGPWordPressClient } from './src/client.js';
import { registerIGPTools } from './src/tools.js';

const server = new McpServer({
  name: 'igp-mcp-bridge',
  version: '1.0.0'
});

const client = new IGPWordPressClient({
  baseUrl: process.env.IGP_WORDPRESS_URL || '',
  username: process.env.IGP_WP_USERNAME || '',
  applicationPassword: process.env.IGP_WP_APP_PASSWORD || '',
  bearerToken: process.env.IGP_WP_BEARER_TOKEN || '',
  enabled: /^(1|true|yes|on)$/i.test(process.env.IGP_MCP_ENABLED || ''),
  requestTimeoutMs: Number.parseInt(process.env.IGP_MCP_TIMEOUT_MS || '30000', 10),
  maxYamlBytes: Number.parseInt(process.env.IGP_MCP_MAX_YAML_BYTES || '200000', 10)
});

registerIGPTools(server, client, z);

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
}

main().catch((error) => {
  const message = error && error.stack ? error.stack : String(error);
  process.stderr.write(`[igp-mcp-bridge] ${message}\n`);
  process.exit(1);
});
