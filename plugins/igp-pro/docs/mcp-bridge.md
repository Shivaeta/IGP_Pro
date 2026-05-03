# IGP MCP Bridge

The IGP MCP Bridge exposes AI Copilot functionality to MCP clients through the WordPress REST API only.

## Boundary

```text
MCP client
  → Node MCP Bridge
  → WordPress REST API
  → IGP_AI_Copilot_Service
  → Parser / normalizer / validator / compiler
  → Content Graph validator
  → preview, draft save, or changeset creation
```

The bridge must not write post meta directly, write Content Graph JSON directly, execute SQL, edit plugin files, or publish content.

## Feature flag

The bridge is disabled unless the `enable_mcp_bridge` feature flag is enabled. The external Node server also requires `IGP_MCP_ENABLED=true`.

## Safe tools

- Get YAML contract
- Get supported blocks
- Validate YAML
- Compile YAML
- Preview YAML
- Create draft from YAML
- Create changeset from YAML

## Human review

The safest production workflow is:

1. MCP submits YAML.
2. IGP validates/compiles it.
3. MCP creates a changeset.
4. A human reviews the changeset in **IGP Pro → AI Changesets**.
5. Human approves or rejects.
6. Approval saves draft-safe content; it does not publish.
7. Rollback uses the snapshot created during approval.
