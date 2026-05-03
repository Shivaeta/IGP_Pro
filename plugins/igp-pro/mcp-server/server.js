import "dotenv/config";
import express from "express";
import { z } from "zod";
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";

const {
  WP_BASE_URL,
  WP_USERNAME,
  WP_APP_PASSWORD,
  MCP_BEARER_TOKEN,
  PORT = 3000,
} = process.env;

const SERVER_VERSION = "0.4.0-igp-ai-copilot";
const WP_REST_NAMESPACE = "/wp-json/igp/v1";

if (!WP_BASE_URL || !WP_USERNAME || !WP_APP_PASSWORD) {
  throw new Error("Missing WP_BASE_URL, WP_USERNAME, or WP_APP_PASSWORD");
}

const app = express();
app.use(express.json({ limit: "25mb" }));

app.use((req, res, next) => {
  res.setHeader("Access-Control-Allow-Origin", "*");
  res.setHeader("Access-Control-Allow-Methods", "GET, POST, DELETE, OPTIONS");
  res.setHeader(
    "Access-Control-Allow-Headers",
    "Content-Type, Accept, Authorization, Mcp-Session-Id, Last-Event-ID, Mcp-Method"
  );
  res.setHeader("Access-Control-Expose-Headers", "Content-Type, Mcp-Session-Id");

  if (req.method === "OPTIONS") {
    return res.status(204).end();
  }

  next();
});

function requireBearerToken(req, res, next) {
  if (!MCP_BEARER_TOKEN) return next();

  const auth = req.headers.authorization || "";
  const expected = `Bearer ${MCP_BEARER_TOKEN}`;

  if (auth !== expected) {
    return res.status(401).json({ error: "Unauthorized" });
  }

  next();
}

function wpAuthHeader() {
  return `Basic ${Buffer.from(`${WP_USERNAME}:${WP_APP_PASSWORD}`).toString("base64")}`;
}

function wpBase() {
  return `${WP_BASE_URL.replace(/\/$/, "")}${WP_REST_NAMESPACE}`;
}

async function wpRequest(path, options = {}) {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  const url = `${wpBase()}${normalizedPath}`;

  const response = await fetch(url, {
    ...options,
    headers: {
      Authorization: wpAuthHeader(),
      "Content-Type": "application/json",
      Accept: "application/json",
      ...(options.headers || {}),
    },
  });

  const text = await response.text();
  let data;
  try {
    data = text ? JSON.parse(text) : null;
  } catch {
    data = text;
  }

  if (!response.ok) {
    const message = typeof data === "string" ? data : JSON.stringify(data);
    const error = new Error(`WordPress request failed: ${response.status} ${response.statusText} ${message}`);
    error.status = response.status;
    error.response = data;
    throw error;
  }

  return data;
}

async function logMcpToolCall(tool, status, summary = "") {
  try {
    await wpRequest("/mcp/log", {
      method: "POST",
      body: JSON.stringify({ tool, status, summary: String(summary).slice(0, 1000) }),
    });
  } catch (error) {
    // Logging must never break the tool call itself.
    console.error("Unable to log MCP tool call in WordPress:", error instanceof Error ? error.message : error);
  }
}

function textResponse(data) {
  return {
    content: [
      {
        type: "text",
        text: JSON.stringify(data, null, 2),
      },
    ],
  };
}

function errorResponse(error, tool) {
  return textResponse({
    success: false,
    tool,
    error: {
      message: error instanceof Error ? error.message : String(error),
      status: error?.status || null,
      response: error?.response || null,
    },
  });
}

async function runWordPressTool(tool, handler) {
  try {
    const result = await handler();
    await logMcpToolCall(tool, "success", "Tool completed successfully.");
    return textResponse(result);
  } catch (error) {
    await logMcpToolCall(tool, "failure", error instanceof Error ? error.message : String(error));
    return errorResponse(error, tool);
  }
}

const ContextSchema = z.record(z.any()).optional();
const YAMLSchema = z.string().min(1, "YAML is required.");
const TargetPostIdSchema = z.number().int().nonnegative().optional();

const REGISTERED_TOOL_NAMES = [
  "igp_ai_get_yaml_contract",
  "igp_ai_get_supported_blocks",
  "igp_ai_validate_yaml",
  "igp_ai_compile_yaml",
  "igp_ai_preview_yaml",
  "igp_ai_create_draft_from_yaml",
  "igp_ai_create_changeset_from_yaml",
];

function createMcpServer() {
  const mcpServer = new McpServer(
    {
      name: "wordpress-igp-ai-copilot-mcp",
      version: SERVER_VERSION,
    },
    {
      instructions:
        "Use IGP AI Copilot tools only. Submit YAML drafts, then validate, compile, preview, and create a draft or changeset. Do not attempt direct WordPress post-meta, database, file, SQL, plugin, or publish operations.",
    }
  );

  mcpServer.registerTool(
    "igp_ai_get_yaml_contract",
    {
      title: "Get IGP AI YAML Contract",
      description: "Return the current IGP AI Copilot YAML contract and allowed draft shape.",
      inputSchema: {},
    },
    async () => runWordPressTool("igp_ai_get_yaml_contract", () => wpRequest("/ai-copilot/contract"))
  );

  mcpServer.registerTool(
    "igp_ai_get_supported_blocks",
    {
      title: "Get IGP AI Supported Blocks",
      description: "Return supported AI block aliases and registered IGP block IDs.",
      inputSchema: {},
    },
    async () => runWordPressTool("igp_ai_get_supported_blocks", () => wpRequest("/ai-copilot/blocks"))
  );

  mcpServer.registerTool(
    "igp_ai_validate_yaml",
    {
      title: "Validate IGP AI YAML",
      description: "Parse, normalize, and validate an AI YAML draft without compiling or saving.",
      inputSchema: {
        yaml: YAMLSchema,
      },
    },
    async ({ yaml }) =>
      runWordPressTool("igp_ai_validate_yaml", () =>
        wpRequest("/ai-copilot/validate", {
          method: "POST",
          body: JSON.stringify({ yaml }),
        })
      )
  );

  mcpServer.registerTool(
    "igp_ai_compile_yaml",
    {
      title: "Compile IGP AI YAML",
      description: "Compile validated YAML into IGP Content Graph without saving or publishing.",
      inputSchema: {
        yaml: YAMLSchema,
        context: ContextSchema,
        target_post_id: TargetPostIdSchema,
      },
    },
    async ({ yaml, context, target_post_id }) =>
      runWordPressTool("igp_ai_compile_yaml", () =>
        wpRequest("/ai-copilot/compile", {
          method: "POST",
          body: JSON.stringify({ yaml, context: context || {}, target_post_id: target_post_id || 0 }),
        })
      )
  );

  mcpServer.registerTool(
    "igp_ai_preview_yaml",
    {
      title: "Preview IGP AI YAML",
      description: "Render a central-renderer preview from YAML without saving post meta or publishing.",
      inputSchema: {
        yaml: YAMLSchema,
        context: ContextSchema,
        target_post_id: TargetPostIdSchema,
      },
    },
    async ({ yaml, context, target_post_id }) =>
      runWordPressTool("igp_ai_preview_yaml", () =>
        wpRequest("/ai-copilot/preview", {
          method: "POST",
          body: JSON.stringify({ yaml, context: context || {}, target_post_id: target_post_id || 0 }),
        })
      )
  );

  mcpServer.registerTool(
    "igp_ai_create_draft_from_yaml",
    {
      title: "Create IGP Draft From YAML",
      description: "Create a WordPress draft after AI Copilot validation and compile checks pass. This never publishes.",
      inputSchema: {
        yaml: YAMLSchema,
        context: ContextSchema,
      },
    },
    async ({ yaml, context }) =>
      runWordPressTool("igp_ai_create_draft_from_yaml", () =>
        wpRequest("/ai-copilot/create-draft", {
          method: "POST",
          body: JSON.stringify({ yaml, context: context || {}, confirm_draft_only: true }),
        })
      )
  );

  mcpServer.registerTool(
    "igp_ai_create_changeset_from_yaml",
    {
      title: "Create IGP AI Changeset From YAML",
      description: "Create a reviewable AI changeset from YAML. Human approval is required before existing content is changed.",
      inputSchema: {
        yaml: YAMLSchema,
        context: ContextSchema,
        target_post_id: TargetPostIdSchema,
      },
    },
    async ({ yaml, context, target_post_id }) =>
      runWordPressTool("igp_ai_create_changeset_from_yaml", () =>
        wpRequest("/ai-copilot/create-changeset", {
          method: "POST",
          body: JSON.stringify({ yaml, context: context || {}, target_post_id: target_post_id || 0 }),
        })
      )
  );

  return mcpServer;
}

app.get("/", (req, res) => {
  res.json({
    ok: true,
    name: "wordpress-igp-ai-copilot-mcp",
    version: SERVER_VERSION,
    wordpressNamespace: WP_REST_NAMESPACE,
    endpoints: { health: "/health", mcp: "/mcp", debugTools: "/debug/tools", debugWordPress: "/debug/wordpress" },
  });
});

app.get("/health", (req, res) => {
  res.json({ ok: true, name: "wordpress-igp-ai-copilot-mcp", version: SERVER_VERSION });
});

app.get("/debug/tools", requireBearerToken, (req, res) => {
  res.json({ ok: true, version: SERVER_VERSION, count: REGISTERED_TOOL_NAMES.length, tools: REGISTERED_TOOL_NAMES });
});

app.get("/debug/wordpress", requireBearerToken, async (req, res) => {
  try {
    const [status, tools] = await Promise.all([wpRequest("/mcp/status"), wpRequest("/mcp/tools")]);
    res.json({ ok: true, status, tools });
  } catch (error) {
    res.status(500).json({
      ok: false,
      error: error instanceof Error ? error.message : String(error),
      response: error?.response || null,
    });
  }
});

async function safeClose(resource, label) {
  if (!resource || typeof resource.close !== "function") return;

  try {
    await resource.close();
  } catch (error) {
    console.error(`Error while closing ${label}:`, error);
  }
}

function handleMcpRequest() {
  return async (req, res) => {
    let mcpServer;
    let transport;
    let closed = false;

    const cleanup = async () => {
      if (closed) return;
      closed = true;
      await safeClose(transport, "MCP transport");
      await safeClose(mcpServer, "MCP server");
    };

    try {
      if (!["GET", "POST", "DELETE"].includes(req.method)) {
        return res.status(405).json({ error: "Method not allowed" });
      }

      // Create a fresh MCP server/protocol per transport.
      // Reusing one global McpServer can cause:
      // "Already connected to a transport. Call close() before connecting..."
      mcpServer = createMcpServer();
      transport = new StreamableHTTPServerTransport({
        // Stateless mode avoids stale session bookkeeping on hosts/CDNs that may
        // interrupt long-running connections.
        sessionIdGenerator: undefined,
      });

      req.on("aborted", cleanup);
      res.on("close", cleanup);
      res.on("finish", cleanup);
      transport.onclose = cleanup;

      await mcpServer.connect(transport);

      if (req.method === "POST") {
        await transport.handleRequest(req, res, req.body);
        return;
      }

      await transport.handleRequest(req, res);
    } catch (error) {
      console.error("MCP error:", error);
      await cleanup();

      if (!res.headersSent) {
        return res.status(500).json({
          error: "MCP server error",
          message: error instanceof Error ? error.message : String(error),
        });
      }
    }
  };
}

app.all("/mcp", requireBearerToken, handleMcpRequest());
app.all("/mcp/", requireBearerToken, handleMcpRequest());

app.use((err, req, res, next) => {
  console.error("Express error:", err);
  if (res.headersSent) return next(err);
  res.status(400).json({ error: "Bad request", message: err.message });
});

app.listen(Number(PORT), () => {
  console.log(`WordPress IGP AI Copilot MCP server v${SERVER_VERSION} running on port ${PORT}`);
  console.log(`Registered ${REGISTERED_TOOL_NAMES.length} MCP tools`);
  console.log(`WordPress REST base: ${wpBase()}`);
});
