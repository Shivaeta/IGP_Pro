# IGP MCP Bridge

The IGP MCP Bridge exposes AI Copilot capabilities to an external MCP server without allowing direct WordPress database, post-meta, SQL, plugin-file, or publish operations.

## WordPress REST namespace

The bridge uses:

```text
/wp-json/igp/v1
```

## Required WordPress endpoints

```text
GET  /wp-json/igp/v1/ai-copilot/contract
GET  /wp-json/igp/v1/ai-copilot/blocks
POST /wp-json/igp/v1/ai-copilot/validate
POST /wp-json/igp/v1/ai-copilot/compile
POST /wp-json/igp/v1/ai-copilot/preview
POST /wp-json/igp/v1/ai-copilot/create-draft
POST /wp-json/igp/v1/ai-copilot/create-changeset
GET  /wp-json/igp/v1/mcp/status
GET  /wp-json/igp/v1/mcp/tools
POST /wp-json/igp/v1/mcp/log
```

## Exposed MCP tools

```text
igp_ai_get_yaml_contract
igp_ai_get_supported_blocks
igp_ai_validate_yaml
igp_ai_compile_yaml
igp_ai_preview_yaml
igp_ai_create_draft_from_yaml
igp_ai_create_changeset_from_yaml
```

## Forbidden operations

The bridge must not expose tools for:

```text
igp_write_post_meta
igp_write_content_graph_json
igp_execute_sql
igp_edit_plugin_file
igp_publish_without_review
```

## External server setup

The included MCP server uses Streamable HTTP.

```bash
cd wp-content/plugins/igp-pro/mcp-server
npm install
cp .env.example .env
npm run check
npm start
```

Required environment variables:

```text
WP_BASE_URL=https://your-wordpress-site.com
WP_USERNAME=your-wordpress-username
WP_APP_PASSWORD=your-wordpress-application-password
MCP_BEARER_TOKEN=choose-a-long-random-token
PORT=3000
```

The WordPress user must have permission to use IGP AI Copilot endpoints.
Use a WordPress Application Password for server-to-WordPress authentication.

## Health checks

```text
GET /health
GET /debug/tools
GET /debug/wordpress
```

`/debug/wordpress` verifies that the WordPress bridge endpoints are reachable and that the MCP bridge feature flag is enabled.
