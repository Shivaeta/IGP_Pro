function jsonText(value) {
  return {
    content: [
      {
        type: 'text',
        text: JSON.stringify(value, null, 2)
      }
    ]
  };
}

function repairHint(error) {
  const payload = error && error.payload ? error.payload : null;
  const data = payload?.data?.error?.data || payload?.data || payload;
  return {
    ok: false,
    error: error instanceof Error ? error.message : String(error),
    details: data || null,
    repair_hint: 'Fix the YAML according to the YAML contract, remove unsafe content, use supported block aliases, and retry validation before compile/save.'
  };
}

export function registerIGPTools(server, client, z) {
  server.registerTool(
    'igp_ai_get_yaml_contract',
    {
      description: 'Get the current IGP AI Copilot YAML contract from WordPress. Read-only.',
      inputSchema: z.object({})
    },
    async () => jsonText(await client.callTool('igp_ai_get_yaml_contract', 'GET', '/igp/v1/ai-copilot/contract'))
  );

  server.registerTool(
    'igp_ai_get_supported_blocks',
    {
      description: 'Get supported AI block aliases and registered IGP block IDs from WordPress. Read-only.',
      inputSchema: z.object({})
    },
    async () => jsonText(await client.callTool('igp_ai_get_supported_blocks', 'GET', '/igp/v1/ai-copilot/blocks'))
  );

  server.registerTool(
    'igp_ai_validate_yaml',
    {
      description: 'Validate AI YAML through the IGP AI Copilot service. Does not save content.',
      inputSchema: z.object({ yaml: z.string().min(1) })
    },
    async ({ yaml }) => {
      try {
        return jsonText(await client.callTool('igp_ai_validate_yaml', 'POST', '/igp/v1/ai-copilot/validate', { yaml }));
      } catch (error) {
        return jsonText(repairHint(error));
      }
    }
  );

  server.registerTool(
    'igp_ai_compile_yaml',
    {
      description: 'Compile valid YAML into an IGP Content Graph through WordPress. Does not save content.',
      inputSchema: z.object({ yaml: z.string().min(1), context: z.record(z.string(), z.unknown()).optional() })
    },
    async ({ yaml, context = {} }) => {
      try {
        return jsonText(await client.callTool('igp_ai_compile_yaml', 'POST', '/igp/v1/ai-copilot/compile', { yaml, context }));
      } catch (error) {
        return jsonText(repairHint(error));
      }
    }
  );

  server.registerTool(
    'igp_ai_preview_yaml',
    {
      description: 'Render a central-renderer preview from YAML through WordPress. Does not save content.',
      inputSchema: z.object({ yaml: z.string().min(1), context: z.record(z.string(), z.unknown()).optional() })
    },
    async ({ yaml, context = {} }) => {
      try {
        return jsonText(await client.callTool('igp_ai_preview_yaml', 'POST', '/igp/v1/ai-copilot/preview', { yaml, context }));
      } catch (error) {
        return jsonText(repairHint(error));
      }
    }
  );

  server.registerTool(
    'igp_ai_create_draft_from_yaml',
    {
      description: 'Create a WordPress draft from YAML only after IGP validation/compile checks pass. Never publishes.',
      inputSchema: z.object({ yaml: z.string().min(1), context: z.record(z.string(), z.unknown()).optional(), confirm_draft_only: z.boolean().default(true) })
    },
    async ({ yaml, context = {}, confirm_draft_only = true }) => {
      try {
        return jsonText(await client.callTool('igp_ai_create_draft_from_yaml', 'POST', '/igp/v1/ai-copilot/create-draft', { yaml, context, confirm_draft_only }));
      } catch (error) {
        return jsonText(repairHint(error));
      }
    }
  );

  server.registerTool(
    'igp_ai_create_changeset_from_yaml',
    {
      description: 'Create a reviewable AI changeset from YAML. Human approval is required before saving content.',
      inputSchema: z.object({ yaml: z.string().min(1), target_post_id: z.number().int().nonnegative().optional(), context: z.record(z.string(), z.unknown()).optional() })
    },
    async ({ yaml, target_post_id = 0, context = {} }) => {
      try {
        return jsonText(await client.callTool('igp_ai_create_changeset_from_yaml', 'POST', '/igp/v1/ai-copilot/create-changeset', { yaml, target_post_id, context: { ...context, actor_type: 'mcp', source: 'mcp_bridge' } }));
      } catch (error) {
        return jsonText(repairHint(error));
      }
    }
  );
}
