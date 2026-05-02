# IGP Pro Plugin — Blueprint V2

**Document role:** Execution source of truth for implementing the V2 platform-hardening roadmap.  
**Version:** V2.0  
**Baseline:** V1 implementation through Phase 5 is complete and gate-passed.  
**Next active phase:** Phase 6 — Safety and Scale Foundation.  

---

## 1. Purpose

This blueprint defines the implementation sequence, dependencies, deliverables, file additions, validation mapping, rollback expectations, and forward/backward wiring for V2 features.

V2 must be implemented as a controlled continuation of the existing IGP Pro system. It must not replace the working V1 pipeline.

---

## 2. Execution Rules

1. Build in sequence.
2. Do not skip gates.
3. Do not expand into later phases before the current phase passes its gate.
4. Preserve all Phase 1–5 functionality.
5. Keep implementation phase-scoped.
6. Do not create all target folders upfront.
7. Create only the files required for the active phase/step.
8. Every step must produce a testable artifact.
9. Every data-changing step must include rollback notes.
10. Every schema-changing step must include migration logic or explicit no-migration justification.
11. Every new subsystem must be guarded by feature flags where practical.
12. Optional integrations must degrade safely if the external plugin is inactive.
13. REST/MCP operations must never bypass service-layer validation.
14. Update `STRUCTURE.md` and `current_status.json` only after user confirms gate pass.
15. If a gate fails, stop, fix, retest, and do not proceed.

---

## 3. Versioning Rules

### 3.1 Content Graph versioning

V2 must introduce or confirm explicit Content Graph versioning:

```text
content_graph.version
content_graph.schema_version
content_graph.migrated_from
content_graph.last_migrated_at
```

### 3.2 Block schema versioning

Every block schema must define:

```text
block_id
version
category
data_source
fields
defaults
validation
variants
render_callback/render_path
supports
```

### 3.3 Migration naming

Migration functions should be traceable:

```text
igp_migrate_content_graph_1_0_to_2_0()
igp_migrate_block_hero_1_0_to_2_0()
```

### 3.4 Feature flag naming

Feature flags should use stable slugs:

```text
enable_relationship_layer
enable_semantic_outline
enable_smart_block_variants
enable_brand_engine
enable_starter_templates
enable_media_optimizer
enable_rank_math_bridge
enable_link_whisper_bridge
enable_mcp_bridge
```

---

## 4. V2 Phase Map

```text
Phase 6  — Safety and Scale Foundation
Phase 7  — Entity Relationships and Content Projection
Phase 8  — Semantic SEO and Crawler-Friendly DOM
Phase 9  — Smart Blocks, Design Tokens, and Brand CSS Engine
Phase 10 — High-Utility Travel Block Expansion
Phase 11 — Starter Content / Industry Template System
Phase 12 — Media SEO and Optimization Layer
Phase 13 — SEO Integrations and Internal Link Intelligence
Phase 14 — REST API and MCP Bridge
Phase 15 — Production Hardening and Release Readiness
```

---

## 5. Phase 6 — Safety and Scale Foundation

### Goal

Create the safety infrastructure required before schema expansion, imports, integrations, and AI/MCP automation.

### Dependencies

- V1 Phase 5 completed and gate-passed.
- Existing plugin activation stable.
- Existing content editor and Content Graph save/load functional.

### Step 6.1 — Feature Flag System

Deliverables:

```text
includes/core/feature-flags.php
includes/admin/settings.php updates
```

Required behavior:

- Register default feature flags.
- Store flags in a single option.
- Provide helper functions for checking flags.
- Ensure disabled features do not load admin panels, REST routes, or frontend output unnecessarily.

Required helpers:

```text
igp_feature_enabled( $flag )
igp_get_feature_flags()
igp_update_feature_flag( $flag, $enabled )
```

Forward wiring:

- Enables safe activation/deactivation of every V2 subsystem.

Backward wiring:

- If a V2 module misbehaves, disable its flag without disabling the plugin.

Gate mapping:

- Gate 19 — Safety Foundation
- Gate 37 — System Stability Regression

---

### Step 6.2 — Capabilities and Permission Model

Deliverables:

```text
includes/core/capabilities.php
includes/api/rest-permissions.php
includes/admin/settings.php updates
```

Required capabilities:

```text
igp_manage_settings
igp_edit_content_graph
igp_import_content
igp_manage_templates
igp_manage_media_optimization
igp_manage_seo
igp_manage_integrations
igp_use_mcp_bridge
igp_publish_ai_changes
igp_manage_recovery
```

Required behavior:

- Administrators receive full IGP capabilities on activation or migration.
- Content editors receive restricted capabilities only when explicitly granted.
- REST endpoints must use capabilities, not only login status.
- Admin panels must be hidden when the user lacks capability.

Forward wiring:

- Required for media, templates, integrations, recovery, REST, and MCP.

Backward wiring:

- If unauthorized edits occur, trace to capability mapping or REST permission callback.

Gate mapping:

- Gate 20 — Permissions and Roles
- Gate 34 — MCP / AI Bridge Safety

---

### Step 6.3 — Logger

Deliverables:

```text
includes/core/logger.php
storage/logs/.gitkeep
includes/admin/diagnostics-panel.php initial section
```

Required behavior:

- Log critical actions and failures.
- Use structured log entries.
- Avoid logging secrets, payment credentials, API keys, or personal sensitive data unnecessarily.
- Provide admin-readable recent log summary.

Required log fields:

```text
timestamp
actor_user_id
actor_type
operation
object_type
object_id
source_module
status
error_code
summary
snapshot_id
```

Forward wiring:

- Required for imports, REST writes, MCP writes, media jobs, and rollback.

Backward wiring:

- If silent failure occurs, trace to logger integration gap.

Gate mapping:

- Gate 21 — Logging
- Gate 37 — System Stability Regression

---

### Step 6.4 — Snapshot and Rollback Engine

Deliverables:

```text
includes/recovery/snapshots.php
includes/recovery/rollback.php
includes/admin/recovery-panel.php
storage/snapshots/.gitkeep
```

Required behavior:

- Create snapshots before destructive writes.
- Store before-state for Content Graph, SEO fields, relationship data, template imports, and MCP edits.
- Provide rollback path.
- Detect conflicts if object changed after snapshot.
- Log snapshot creation and rollback.

Required snapshot functions:

```text
igp_create_snapshot( $object_type, $object_id, $before_data, $context )
igp_get_snapshot( $snapshot_id )
igp_list_snapshots( $args )
igp_restore_snapshot( $snapshot_id, $mode )
```

Forward wiring:

- Required before template imports and MCP write tools.

Backward wiring:

- If rollback fails, trace to snapshot serialization or object restore adapter.

Gate mapping:

- Gate 22 — Snapshot and Recovery

---

### Step 6.5 — Migration Framework

Deliverables:

```text
includes/migration/block-migrations.php
includes/migration/content-graph-migrations.php
includes/migration/schema-version-map.php
```

Required behavior:

- Register available migrations.
- Detect old Content Graph and block versions.
- Migrate without data loss.
- Preserve original data through snapshots before migration.
- Fail safely if migration path is missing.

Forward wiring:

- Required before heading schema, style schema, relationships, media fields, and V2 imports.

Backward wiring:

- If old pages fail to render, trace to migration registry or fallback renderer.

Gate mapping:

- Gate 23 — Migration Safety

---

### Phase 6 Exit Criteria

Phase 6 is complete only if:

- feature flags work
- capabilities are enforced
- logger records critical actions
- snapshots can be created and restored
- migration registry exists and is callable
- no V1 functionality regresses

---

## 6. Phase 7 — Entity Relationships and Content Projection

### Goal

Create a relationship layer between tours and destinations and expose structured Content Graph projections for SEO, links, integrations, and AI.

### Dependencies

- Phase 6 complete.
- Migration and snapshot services available.

### Step 7.1 — Tour/Destination Relationship Service

Deliverables:

```text
includes/relationships/relationships.php
includes/relationships/relationship-validator.php
includes/relationships/relationship-queries.php
```

Required fields:

```text
primary_destination_id
destination_ids[]
route_stop_ids[]
related_tour_ids[]
related_destination_ids[]
```

Required behavior:

- Save relationship data safely.
- Validate referenced post IDs and post types.
- Prevent self-referential invalid relationships where inappropriate.
- Degrade safely if a referenced post is deleted.
- Provide query helpers for destination pages.

Forward wiring:

- Enables related blocks, breadcrumbs, schema, internal links, template imports.

Backward wiring:

- If related content is wrong, trace to relationship service before block renderer.

Gate mapping:

- Gate 24 — Entity Relationship Layer

---

### Step 7.2 — Relationship Admin UI

Deliverables:

```text
includes/relationships/relationship-admin.php
includes/admin/relationships-panel.php
assets/js/admin-relationships.js
```

Required behavior:

- Allow editors to set primary destination for tours.
- Allow editors to select multiple destinations.
- Allow related tours/destinations to be managed.
- Enforce permissions.
- Save through service layer only.

Gate mapping:

- Gate 24 — Entity Relationship Layer
- Gate 20 — Permissions and Roles

---

### Step 7.3 — Content Projection Service

Deliverables:

```text
includes/content/projection.php
```

Required projections:

```text
frontend_html
seo_text
analysis_html
rank_math_content
link_whisper_content
mcp_markdown
search_index_text
plain_text_summary
```

Required behavior:

- Convert Content Graph into meaningful semantic content without mutating original graph.
- Include headings, paragraphs, itinerary labels, FAQ questions, tour facts, destination names, and approved internal links.
- Exclude purely decorative fields.
- Be callable by SEO audits, Rank Math bridge, Link Whisper bridge, and MCP.

Gate mapping:

- Gate 25 — Content Projection

---

### Phase 7 Exit Criteria

Phase 7 is complete only if:

- tour/destination relationships save and reload
- destination pages can query related tours
- deleted references do not break rendering
- content projection outputs clean semantic text/HTML
- old V1 pages still render

---

## 7. Phase 8 — Semantic SEO and Crawler-Friendly DOM

### Goal

Make frontend block output semantically correct, crawler-friendly, accessible, and aligned with structured SEO policy.

### Dependencies

- Phase 6 complete.
- Phase 7 content projection available.

### Step 8.1 — Page-Level Heading Policy

Deliverables:

```text
includes/seo/outline-engine.php
includes/seo/heading-policy.php
includes/blocks/heading-support.php
```

Required behavior:

- Enforce exactly one H1.
- Resolve H1 using explicit `seo.h1`, then post title, then primary hero fallback.
- Allow blocks to expose H2–H4 controls.
- Validate heading hierarchy.
- Prevent H1 duplication between theme title and hero.

Gate mapping:

- Gate 26 — Semantic SEO Outline

---

### Step 8.2 — Block Schema Heading Extension

Deliverables:

```text
updates to heading-enabled block schema.json files
migration functions for old block data
```

Required schema object:

```json
{
  "heading": {
    "text": "",
    "level": "h2",
    "eyebrow": "",
    "visible": true
  }
}
```

Required behavior:

- Heading fields render in editor from schema.
- Invalid heading levels are rejected.
- Empty visible headings are rejected where heading is required.
- Existing V1 headings migrate safely.

Gate mapping:

- Gate 23 — Migration Safety
- Gate 26 — Semantic SEO Outline

---

### Step 8.3 — Semantic Renderer Upgrade

Deliverables:

```text
includes/blocks/renderer.php updates
includes/blocks/block-supports.php
```

Required behavior:

- Render semantic wrappers.
- Add stable IDs for heading-linked sections.
- Add `aria-labelledby` where applicable.
- Avoid fake headings for decorative sections.
- Ensure CTA and card blocks remain accessible.

Gate mapping:

- Gate 27 — Crawler-Friendly DOM

---

### Step 8.4 — SEO Audit Panel Enhancements

Deliverables:

```text
includes/seo/seo-audit.php
includes/admin/seo-panel.php updates
```

Required checks:

```text
H1 count
heading hierarchy
meta title presence/length
meta description presence/length
canonical presence
indexability
schema presence
image alt coverage
internal link counts
orphan page risk
CWV status summary
```

Gate mapping:

- Gate 28 — SEO Health Dashboard

---

### Phase 8 Exit Criteria

Phase 8 is complete only if:

- frontend pages render one H1
- block headings are selectable H2–H4
- outline validator rejects invalid hierarchy
- semantic wrappers exist
- SEO audit panel detects common issues
- no duplicate SEO output is introduced

---

## 8. Phase 9 — Smart Blocks, Design Tokens, and Brand CSS Engine

### Goal

Solve visual repetition with controlled variants, density, themes, design tokens, and generated brand CSS.

### Dependencies

- Phase 6 migration foundation complete.
- Phase 8 semantic block supports complete.

### Step 9.1 — Shared Block Style Support

Deliverables:

```text
includes/blocks/style-support.php
schema updates for visual blocks
migration functions
```

Required style object:

```json
{
  "style": {
    "variant": "default",
    "density": "comfortable",
    "theme": "brand",
    "container": "wide",
    "surface": "default",
    "media_position": "auto"
  }
}
```

Required behavior:

- Every visual block has schema-defined style options.
- Invalid variants are rejected.
- Missing V1 style data uses defaults.
- Style config changes classes only, not content data.

Gate mapping:

- Gate 29 — Smart Block Variant System

---

### Step 9.2 — Variant Expansion for Existing Blocks

Deliverables:

```text
schema/render updates for hero, CTA, cards, gallery, itinerary, trust, pricing, FAQ, related blocks
```

Minimum required variant support:

```text
Hero: full-width, image-left, image-right, split-overlay, centered-minimal
CTA: inline, banner, split, card
Cards: grid, carousel-safe, list, featured
Gallery: grid, masonry-safe, slider-safe
FAQ: accordion, grouped, compact
Itinerary: timeline, cards, compact
Trust: logo-strip, testimonial-cards, stats
```

Do not implement heavy frontend sliders unless performance-safe.

Gate mapping:

- Gate 29 — Smart Block Variant System
- Gate 37 — System Stability Regression

---

### Step 9.3 — Design Token Engine

Deliverables:

```text
includes/design/design-tokens.php
includes/design/token-validator.php
```

Required token categories:

```text
colors
typography
spacing
radius
shadow
buttons
containers
surfaces
```

Required behavior:

- Validate token values.
- Provide plugin defaults.
- Reject malformed color/size/token values.
- Support import/export with brand profiles.

Gate mapping:

- Gate 30 — Brand Token Engine

---

### Step 9.4 — Brand Profiles and CSS Generator

Deliverables:

```text
includes/design/brand-profiles.php
includes/design/css-generator.php
includes/admin/brand-panel.php
assets/css/frontend-base.css
storage/generated-css/.gitkeep
```

Required behavior:

- Store brand profiles.
- Generate CSS variables.
- Cache generated CSS.
- Invalidate cache on token/profile change.
- Scope CSS to IGP frontend classes where practical.

Gate mapping:

- Gate 30 — Brand Token Engine
- Gate 31 — Generated CSS Cache

---

### Phase 9 Exit Criteria

Phase 9 is complete only if:

- visual blocks support controlled variants
- brand tokens generate frontend CSS
- old pages render with default styles
- generated CSS cache invalidates correctly
- arbitrary raw CSS is not required for normal branding

---

## 9. Phase 10 — High-Utility Travel Block Expansion

### Goal

Add high-value travel blocks that improve SEO, usability, conversion, and structured content coverage.

### Dependencies

- Phase 8 semantic heading support.
- Phase 9 style/variant support.
- Phase 7 relationships for relationship-aware blocks.

### Step 10.1 — P0 Travel Blocks

Deliverables:

```text
includes/blocks/tour-facts/schema.json
includes/blocks/tour-facts/render.php
includes/blocks/inclusions-exclusions/schema.json
includes/blocks/inclusions-exclusions/render.php
includes/blocks/departure-dates/schema.json
includes/blocks/departure-dates/render.php
includes/blocks/package-tiers/schema.json
includes/blocks/package-tiers/render.php
includes/blocks/reviews-summary/schema.json
includes/blocks/reviews-summary/render.php
```

Required blocks:

1. Tour Facts / Quick Info
2. Inclusions / Exclusions
3. Departure Dates / Availability
4. Package Tiers / Price Comparison
5. Reviews Summary / Aggregate Trust

Each block must define:

```text
schema
version
defaults
variants
heading support where applicable
style support
validation rules
server render path
SEO/schema contribution where applicable
```

Gate mapping:

- Gate 32 — Travel Block Expansion

---

### Step 10.2 — P1 Travel Blocks

Deliverables:

```text
includes/blocks/visa-requirements/
includes/blocks/best-time-to-visit/
includes/blocks/route-timeline/
includes/blocks/expert-box/
includes/blocks/sticky-booking-cta/
includes/blocks/nearby-attractions/
includes/blocks/brochure-cta/
```

Required behavior:

- Add only after P0 blocks pass.
- Keep frontend performance lean.
- Avoid heavy frontend JavaScript.
- Ensure sticky CTA does not harm CWV or accessibility.

Gate mapping:

- Gate 32 — Travel Block Expansion
- Gate 37 — System Stability Regression

---

### Phase 10 Exit Criteria

Phase 10 is complete only if:

- new blocks register centrally
- schemas validate
- render paths use central renderer
- SEO/schema contributions are controlled
- no new block bypasses migration/heading/style rules

---

## 10. Phase 11 — Starter Content / Industry Template System

### Goal

Create a starter content importer that imports full industry-specific branding and structured content systems.

### Dependencies

- Phase 6 snapshot/rollback complete.
- Phase 7 relationships complete.
- Phase 8 SEO outline complete.
- Phase 9 brand tokens complete.
- Phase 10 blocks available where templates need them.

### Step 11.1 — Template Registry and Manifest Format

Deliverables:

```text
includes/starter-content/template-registry.php
includes/starter-content/template-validator.php
starter-content/templates/*/manifest.json
```

Required template families:

```text
luxury-tours
budget-tours
pilgrimage-travel
international-inbound
```

Required manifest fields:

```text
template_id
version
name
industry
required_blocks
required_features
brand_profile
pages
tours
destinations
media_placeholders
seo_profile
link_map
```

Gate mapping:

- Gate 33 — Starter Template Registry

---

### Step 11.2 — Template Preview and Dry Run

Deliverables:

```text
includes/starter-content/template-preview.php
includes/admin/starter-content-panel.php
assets/js/admin-starter-content.js
```

Required behavior:

- Show what will be created/updated.
- Validate all graphs before write.
- Detect missing blocks/features.
- Detect duplicate template UUIDs.
- Detect relationship mapping issues.
- No database writes in dry run.

Gate mapping:

- Gate 34 — Starter Template Dry Run

---

### Step 11.3 — Template Importer

Deliverables:

```text
includes/starter-content/template-importer.php
```

Required import modes:

```text
create_new
merge_existing
```

Required behavior:

- Create pages, tours, destinations, content graphs, SEO fields, relationships, media placeholders, brand profile assignment, and link map entries.
- Store template source metadata.
- Create import batch ID.
- Create snapshots before merge updates.
- Be idempotent.

Gate mapping:

- Gate 35 — Starter Template Importer

---

### Step 11.4 — Template Rollback

Deliverables:

```text
includes/starter-content/template-rollback.php
includes/admin/recovery-panel.php updates
```

Required behavior:

- Roll back last import batch.
- Distinguish created objects from modified objects.
- Do not delete user-created content incorrectly.
- Log rollback.

Gate mapping:

- Gate 36 — Starter Template Rollback

---

### Phase 11 Exit Criteria

Phase 11 is complete only if:

- templates validate before import
- dry run performs no writes
- imports are idempotent
- imported content is structured and editable
- rollback works
- starter content is not hardcoded into business logic

---

## 11. Phase 12 — Media SEO and Optimization Layer

### Goal

Create a media panel/layer for SEO, accessibility, performance, and image optimization.

### Dependencies

- Phase 6 capabilities and logging complete.
- Phase 8 SEO audit available.
- Phase 11 starter media placeholders if imported templates are used.

### Step 12.1 — Media Inventory Service

Deliverables:

```text
includes/media/media-inventory.php
```

Required behavior:

- Detect images used by page/tour/destination.
- Include featured image, hero image, block images, galleries, OG image, schema images, and related media fields.
- Identify LCP candidate image.

Gate mapping:

- Gate 38 — Media Inventory

---

### Step 12.2 — Media Audit Service and Panel

Deliverables:

```text
includes/media/media-audit.php
includes/admin/media-panel.php
assets/js/admin-media.js
```

Required checks:

```text
missing alt text
weak/generic alt text
bad filename patterns
oversized image
missing dimensions
missing responsive sizes
incorrect lazy-loading policy
missing OG image
missing schema image
```

Gate mapping:

- Gate 39 — Media SEO Audit

---

### Step 12.3 — Image Optimization and WebP Adapter

Deliverables:

```text
includes/media/image-optimizer.php
includes/media/webp-adapter.php
includes/media/lazy-loading-policy.php
```

Required behavior:

- Generate WebP where supported.
- Preserve original images.
- Fail safely if image library is unavailable.
- Never run heavy conversion on frontend page load.
- Disable lazy loading for LCP/hero image by default.
- Enable lazy loading for below-fold images where appropriate.

Gate mapping:

- Gate 40 — Media Optimization

---

### Phase 12 Exit Criteria

Phase 12 is complete only if:

- media inventory is accurate
- media audits flag issues
- WebP failures do not break pages
- LCP image policy is correct
- original image recovery is possible

---

## 12. Phase 13 — SEO Integrations and Internal Link Intelligence

### Goal

Integrate with Rank Math Pro and Link Whisper Pro safely while keeping IGP as the structured source of truth.

### Dependencies

- Phase 7 content projection complete.
- Phase 8 SEO outline complete.
- Phase 12 media audit complete.

### Step 13.1 — Rank Math Bridge

Deliverables:

```text
includes/integrations/rank-math/rank-math-bridge.php
includes/integrations/rank-math/schema-mapper.php
includes/integrations/rank-math/content-provider.php
includes/admin/integrations-panel.php
```

Required behavior:

- Detect Rank Math activation safely.
- Runtime bridge is default.
- Optional sync mode is behind a feature setting.
- Prevent duplicate metadata/schema output.
- Provide Rank Math with title, description, canonical, robots, OG data, schema graph, breadcrumbs, and analysis content where supported.
- Fall back to IGP direct SEO output when Rank Math is inactive.

Gate mapping:

- Gate 41 — Rank Math Bridge

---

### Step 13.2 — Link Whisper Companion Bridge

Deliverables:

```text
includes/integrations/link-whisper/link-whisper-bridge.php
includes/integrations/link-whisper/content-provider.php
includes/integrations/link-whisper/opportunity-mapper.php
```

Required behavior:

- Detect Link Whisper safely.
- Provide content projection where possible.
- Do not directly auto-write links by default.
- Map suggestions to IGP-approved internal link opportunities.
- Degrade safely if Link Whisper provides no public integration API.

Gate mapping:

- Gate 42 — Link Whisper Companion

---

### Step 13.3 — Internal Link Intelligence

Deliverables:

```text
includes/seo/internal-linking.php
includes/admin/seo-panel.php updates
```

Required behavior:

- Suggest links based on relationships, headings, destinations, tours, itinerary locations, FAQ topics, and orphan risks.
- Store approved links in Content Graph.
- Avoid excessive repeated anchor text.
- Avoid hidden links.
- Include links in projection and frontend render.

Gate mapping:

- Gate 43 — Internal Link Intelligence

---

### Phase 13 Exit Criteria

Phase 13 is complete only if:

- Rank Math can be active without duplicate SEO output
- Rank Math inactive fallback works
- Link Whisper absence does not break IGP
- link opportunities are reviewable
- approved links persist through import/export

---

## 13. Phase 14 — REST API and MCP Bridge

### Goal

Expose IGP content operations to AI through a safe REST API and MCP bridge.

### Dependencies

- Phase 6 capabilities, logging, snapshots complete.
- Phase 7 projection complete.
- Phase 8 validation complete.
- Phase 11 importer complete if template tools are exposed.

### Step 14.1 — REST API Foundation

Deliverables:

```text
includes/api/rest-routes.php
includes/api/rest-permissions.php
includes/api/controllers/content-controller.php
includes/api/controllers/relationship-controller.php
includes/api/controllers/seo-controller.php
includes/api/controllers/media-controller.php
includes/api/controllers/template-controller.php
```

Required endpoints:

```text
GET    /igp/v1/pages
GET    /igp/v1/pages/{id}/graph
POST   /igp/v1/pages/{id}/validate
POST   /igp/v1/pages/{id}/draft-update
POST   /igp/v1/pages/{id}/sections/reorder
POST   /igp/v1/pages/{id}/sections/delete
GET    /igp/v1/block-schemas
GET    /igp/v1/seo-audit/{id}
GET    /igp/v1/media-audit/{id}
GET    /igp/v1/templates
POST   /igp/v1/templates/import-dry-run
POST   /igp/v1/templates/import
```

Required behavior:

- Use WordPress REST API.
- Enforce IGP capabilities.
- Validate all request payloads.
- Return structured errors.
- Create snapshots before destructive writes.
- Log all writes.

Gate mapping:

- Gate 44 — REST API Safety

---

### Step 14.2 — Changeset Workflow

Deliverables:

```text
includes/content/changeset.php
includes/admin/content-editor.php updates
includes/admin/recovery-panel.php updates
```

Required behavior:

- Store proposed AI/API changes separately from published data.
- Show diff between current graph and proposed graph.
- Allow approve/reject.
- Require `igp_publish_ai_changes` to publish AI-generated changes.

Gate mapping:

- Gate 45 — Changeset Workflow

---

### Step 14.3 — MCP Tool Registry Inside Plugin

Deliverables:

```text
includes/mcp/tool-registry.php
includes/mcp/tool-schemas.php
includes/mcp/tool-permissions.php
includes/mcp/audit.php
includes/api/controllers/mcp-controller.php
```

Required behavior:

- Define allowed IGP operations as tool schemas.
- Map MCP tool names to REST operations.
- Enforce feature flag and capabilities.
- Do not execute arbitrary code.
- Do not expose secrets.

Gate mapping:

- Gate 46 — MCP Tool Registry

---

### Step 14.4 — External MCP Server Wrapper

Deliverables:

```text
tools/mcp-server/README.md
tools/mcp-server/package.json
tools/mcp-server/src/server.ts
tools/mcp-server/src/igp-client.ts
tools/mcp-server/src/tools.ts
tools/mcp-server/src/schemas.ts
```

Required behavior:

- MCP server authenticates to WordPress through configured credentials.
- MCP tools call IGP REST API only.
- Production mode defaults to draft/changeset operations.
- Destructive tools require explicit confirmation in client workflow where possible.
- No WordPress file editing, SQL execution, or PHP execution tools are exposed.

Gate mapping:

- Gate 47 — MCP / AI Bridge Safety

---

### Phase 14 Exit Criteria

Phase 14 is complete only if:

- REST reads/writes are permission-gated
- MCP tools cannot bypass validation
- destructive changes create snapshots
- AI edits are reviewable before publish in production mode
- logs identify actor/source/action
- rate limiting or abuse mitigation exists

---

## 14. Phase 15 — Production Hardening and Release Readiness

### Goal

Run a full-system regression and confirm V2 is production-ready.

### Dependencies

- Phases 6–14 complete.

### Step 15.1 — Full Gate Regression

Deliverables:

```text
manual test report
updated current_status.json after approval
updated STRUCTURE.md after approval
```

Required behavior:

- Re-run core V1 gates affected by V2.
- Run V2 gates.
- Confirm no PHP warnings.
- Confirm no JS console errors in admin workflows.
- Confirm frontend renders correctly with Rank Math active/inactive.
- Confirm Link Whisper active/inactive does not break system.
- Confirm MCP disabled has no public exposure.

Gate mapping:

- Gate 48 — Full System Regression

---

### Step 15.2 — Production Readiness Checklist

Required checklist:

```text
feature flags documented
admin panels permission-gated
schema versions documented
migration paths documented
snapshots tested
rollback tested
SEO duplicate output tested
media audit tested
starter import rollback tested
MCP disabled by default on production
REST endpoints permission-tested
cache invalidation tested
```

Gate mapping:

- Gate 49 — Production Readiness

---

## 15. Required Documentation Updates After Each Phase

After each phase passes its gate and the user approves:

1. Update `current_status.json`.
2. Update `STRUCTURE.md`.
3. Record completed phase, completed files, known limitations, and next phase.
4. Do not mark a phase complete before user approval.

Suggested `current_status.json` shape:

```json
{
  "project": "IGP Pro",
  "baseline": "V1 Phase 5 complete",
  "active_version": "V2",
  "current_phase": "Phase 6",
  "completed_phases": ["V1 Phase 1", "V1 Phase 2", "V1 Phase 3", "V1 Phase 4", "V1 Phase 5"],
  "last_gate_passed": "Gate 13 or V1 Phase 5 gate set",
  "next_required_gate": "Gate 19",
  "notes": []
}
```

---

## 16. Source of Truth

This blueprint controls implementation sequence, dependencies, deliverables, and phase progression for V2. If a proposed implementation conflicts with this blueprint, stop and resolve the conflict before coding.

