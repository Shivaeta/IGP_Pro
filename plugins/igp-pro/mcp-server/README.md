# IGP MCP Bridge

This Node MCP server exposes IGP Pro AI Copilot tools to MCP-compatible clients. It is intentionally a thin wrapper over WordPress REST endpoints.

## Safety model

The bridge does not access the WordPress database, filesystem, post meta, SQL, PHP execution, or plugin files. Every tool calls the WordPress REST API, which then routes through the IGP AI Copilot service, compiler, validators, permissions, and changeset/draft save paths.

Unsafe tools are intentionally absent:

- `igp_write_post_meta`
- `igp_write_content_graph_json`
- `igp_execute_sql`
- `igp_edit_plugin_file`
- `igp_publish_without_review`

## Tools

- `igp_ai_get_yaml_contract`
- `igp_ai_get_supported_blocks`
- `igp_ai_validate_yaml`
- `igp_ai_compile_yaml`
- `igp_ai_preview_yaml`
- `igp_ai_create_draft_from_yaml`
- `igp_ai_create_changeset_from_yaml`

## WordPress setup

1. Install the Phase 15/16 plugin patch.
2. In WordPress admin, enable the `enable_mcp_bridge` feature flag in IGP Pro settings.
3. Create a WordPress Application Password for an authorized user that has the IGP AI Copilot capability.
4. Keep credentials in environment variables only. Do not commit secrets.

## Local server setup

```bash
cd wp-content/plugins/igp-pro/mcp-server
npm install
npm run check
```

## Environment variables

```bash
export IGP_MCP_ENABLED=true
export IGP_WORDPRESS_URL="https://example.com"
export IGP_WP_USERNAME="admin-user"
export IGP_WP_APP_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx"
```

Optional:

```bash
export IGP_WP_BEARER_TOKEN="..."
export IGP_MCP_TIMEOUT_MS=30000
export IGP_MCP_MAX_YAML_BYTES=200000
```

## Run

```bash
npm start
```

The server communicates over stdio for MCP clients.
