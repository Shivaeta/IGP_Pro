# IGP Pro Plugin — Project Charter V2

**Document role:** Definitive source of truth for the next platform-hardening stage of IGP Pro.  
**Version:** V2.0  
**Baseline state:** V1 implementation through Phase 5 is complete and gate-passed. Phase 6 has not started.  
**Scope of this document:** All structural upgrades discussed for relationships, semantic SEO, starter templates, media optimization, smart blocks, brand styling, Rank Math Pro, Link Whisper Pro, high-utility travel blocks, REST/MCP automation, safety, recovery, and production hardening.

---

## 1. Project Identity

IGP Pro is a schema-driven WordPress travel website engine. It combines custom post types, structured content graphs, reusable server-rendered blocks, booking and enquiry workflows, SEO generation, performance controls, branding systems, starter templates, media optimization, relationship intelligence, and controlled AI automation into one governed system.

The authoritative product identity remains:

```text
Product system: IGP Pro plugin + IGP Travel Pro theme
Primary plugin: IGP Pro
Repository identity: Shivaeta/IGP_Pro
Current implementation baseline: V1 through Phase 5 complete
Next active implementation stage: V2 Phase 6 — Safety and Scale Foundation
```

---

## 2. Purpose of V2

V2 exists to turn the working V1 plugin into a production-grade, scalable travel-site platform without breaking the existing pipeline.

V2 must solve these core problems:

1. Tours and destinations must become structurally related entities.
2. Block output must become crawler-friendly, semantic, accessible, and SEO-controlled.
3. The system must support controlled visual diversity through smart variants, design tokens, and brand profiles.
4. Starter content must become an industry-template system, not a demo-data importer.
5. Media must become auditable, optimizable, and SEO-aware.
6. Rank Math Pro and Link Whisper Pro must be leveraged without becoming hard dependencies or creating duplicate SEO output.
7. AI/MCP automation must operate through safe APIs, validation, permissions, logging, snapshots, and review workflows.
8. All new features must be introduced through migrations, feature flags, rollback paths, and gates.

---

## 3. Vision

Build a repeatable travel-site platform that can be deployed across many client sites while preserving:

- structured data quality
- SEO correctness
- layout variation
- performance discipline
- editor safety
- brand consistency
- import/export reproducibility
- AI-assisted content operations
- rollback and recovery
- maintainable code architecture

V2 must make IGP Pro suitable for scale, not merely functional for one website.

---

## 4. Current Baseline and Continuity Rules

### 4.1 V1 baseline

The system is assumed to already contain working implementations for:

- plugin scaffold
- CPT layer
- block registry
- central renderer
- initial block library
- content graph backbone
- content editor
- import/export
- booking and enquiry workflow
- pricing/payment adapter layer as implemented in V1
- SEO engine
- JSON-LD generator
- CWV integration
- cache layer

### 4.2 V2 continuity rule

V2 must not rewrite stable V1 systems unless required for compatibility, safety, or documented migration.

### 4.3 Backward compatibility rule

Existing V1 content graphs must continue to:

- load in the editor
- validate or migrate cleanly
- render on the frontend
- export without data loss
- import without corruption
- produce equivalent or improved SEO output

### 4.4 Phase discipline

V2 begins at Phase 6. Phases 1–5 remain binding and must not be broken.

---

## 5. Governing Principles

1. **Structured data over free-form editing.**
2. **Content Graph remains the source of truth for page/section content.**
3. **Blocks remain schema-driven and server-rendered.**
4. **Central registry and central renderer remain mandatory.**
5. **SEO is derived from structured content, not hand-written markup hacks.**
6. **Visual variation must be handled through block variants and design tokens, not arbitrary CSS overrides.**
7. **Automation must not bypass validation, permissions, snapshots, or logging.**
8. **Integrations must be optional and adapter-driven.**
9. **No plugin integration may become a single point of failure.**
10. **Every data-changing operation must be traceable, reversible where practical, and gate-tested.**

---

## 6. Non-Negotiables

### 6.1 Existing V1 non-negotiables remain binding

- Gutenberg-compatible blocks with PHP server-side rendering.
- Central block registry and renderer.
- Versioned block schemas.
- Content validation before save.
- Modular folder structure.
- No business logic in the main plugin file.
- Booking logic separated from presentation.
- SEO derived from structured content.
- Cache and recovery support.

### 6.2 V2 non-negotiables

1. **Migration before schema expansion**  
   Any schema change affecting saved content must include a migration path.

2. **Snapshots before destructive writes**  
   Deletes, section reorders, imports, AI edits, MCP writes, and bulk operations must create snapshots.

3. **Feature flags for major subsystems**  
   Rank Math bridge, Link Whisper bridge, MCP bridge, starter importer, media optimizer, smart variants, and brand engine must be separately controllable.

4. **No direct database writes from MCP**  
   MCP must call controlled REST endpoints. REST endpoints must call IGP services. IGP services must validate data before storage.

5. **No duplicate SEO output**  
   If Rank Math is active and the bridge is enabled, IGP must provide data to Rank Math and suppress duplicate direct frontend SEO output where appropriate.

6. **No hard dependency on Rank Math or Link Whisper**  
   IGP must work if those plugins are absent, inactive, outdated, or disabled.

7. **No arbitrary CSS importer as the primary brand system**  
   Branding must be driven by tokens, presets, generated CSS variables, and controlled overrides.

8. **Exactly one frontend H1 per rendered IGP page**  
   H1 assignment is page-level policy, not block-level guesswork.

9. **Block headings must be semantic**  
   Heading-enabled blocks may expose H2–H4 controls, but H1 is reserved for page-level SEO policy.

10. **Media optimization must not run during normal frontend rendering**  
    Conversion and heavy audits must occur on upload, admin action, scheduled task, or import queue.

11. **Starter content imports must be idempotent**  
    Re-running an import must not create uncontrolled duplicates.

12. **AI-generated content must remain inspectable**  
    AI/MCP edits must be represented as changesets or draft updates unless explicitly configured otherwise for local/dev use.

13. **Runtime failure must degrade safely**  
    Integration failure, API failure, image conversion failure, or MCP failure must not white-screen the site.

---

## 7. Scope

### 7.1 In scope for V2

- Block migration framework
- Feature flag system
- Permissions and role capabilities
- Logging system
- Snapshot and rollback system
- Tour–destination relationship layer
- Relationship-aware blocks and queries
- Content projection service
- Semantic SEO outline service
- Page-level H1 policy
- Block heading level controls
- Semantic DOM wrappers
- Smart block variants
- Design tokens
- Brand profile engine
- Generated CSS layer
- Additional high-utility travel blocks
- Starter content / industry template importer
- Media inventory, audit, and optimization layer
- Rank Math Pro bridge
- Link Whisper Pro companion bridge
- SEO health dashboard
- Internal linking intelligence
- IGP REST API for content operations
- MCP bridge via controlled REST tools
- Import/export compatibility with new V2 data
- Updated admin panels
- Updated gate test procedures

### 7.2 Out of scope for V2 unless explicitly added later

- Full marketplace distribution workflow
- Multilingual content system
- Complex inventory/yield-management engine
- AI agent runtime embedded directly inside WordPress
- Raw page-builder replacement
- Arbitrary unvalidated CSS/JS editor
- Direct MCP database mutation
- Automatic publishing from AI on production sites by default
- Heavy frontend JavaScript rendering of primary content

---

## 8. Core Modules in V2

The V2 system consists of these modules:

1. Safety / Scale Foundation
2. Migration Engine
3. Feature Flags
4. Permissions / Capabilities
5. Logging / Audit Trail
6. Snapshot / Recovery Engine
7. Entity Relationship Layer
8. Content Projection Service
9. Semantic SEO / Outline Engine
10. Smart Block Variant System
11. Brand Token / CSS Engine
12. Starter Content Template System
13. Media SEO / Optimization Layer
14. Rank Math Bridge
15. Link Whisper Companion Bridge
16. Internal Link Intelligence Engine
17. Travel Block Expansion Layer
18. REST API Layer
19. MCP Bridge Layer
20. Health / Diagnostics Dashboard

---

## 9. Entity Relationship Rules

### 9.1 Relationship philosophy

Tours are usually related to destinations, but they are not always strict children of one destination. A tour may include multiple destinations, route stops, related destinations, or thematic relationships.

Therefore, IGP must not rely only on WordPress `post_parent` for tour/destination relationships.

### 9.2 Required relationship fields

Tours must support:

```text
primary_destination_id
destination_ids[]
route_stop_ids[]
related_tour_ids[]
related_destination_ids[]
```

Destinations must support derived queries:

```text
tours_for_destination
featured_tours_for_destination
related_destinations
child_or_nearby_destinations
```

### 9.3 Storage rule

The first implementation may use post meta. Access must go through a service abstraction so storage can later move to a custom indexed table without changing block or editor code.

Required service style:

```text
IGP_Relationships::get_primary_destination( $tour_id )
IGP_Relationships::get_destination_ids_for_tour( $tour_id )
IGP_Relationships::get_tours_for_destination( $destination_id, $args )
IGP_Relationships::get_related_tours( $post_id, $context )
IGP_Relationships::validate_relationship_payload( $payload )
```

### 9.4 Relationship consumers

The relationship layer must feed:

- breadcrumbs
- related tours
- related destinations
- tour cards
- destination cards
- starter template imports
- JSON-LD
- Rank Math bridge
- Link Whisper companion logic
- MCP content context
- SEO health dashboard

---

## 10. Semantic SEO Rules

### 10.1 H1 policy

IGP must enforce exactly one frontend H1 for IGP-rendered pages.

H1 resolution order:

```text
explicit seo.h1 override
↓
WordPress post title
↓
hero heading only when hero is marked as primary and no better H1 source exists
```

The Hero block must not independently decide to output H1 unless instructed by the page-level outline service.

### 10.2 Block heading policy

Blocks with headings must use a structured heading object:

```json
{
  "heading": {
    "text": "Popular Rajasthan Tours",
    "level": "h2",
    "eyebrow": "Featured Packages",
    "visible": true
  }
}
```

Allowed block heading levels:

```text
h2, h3, h4
```

H1 is reserved for the page-level SEO object.

### 10.3 Semantic DOM policy

Blocks must render semantic wrappers when appropriate:

```html
<section class="igp-block igp-block--tour-cards" aria-labelledby="igp-section-123-heading">
  <h2 id="igp-section-123-heading">Popular Tours</h2>
  ...
</section>
```

Decorative blocks must not create fake headings. CTA-only sections may use `aria-label` or hidden semantic labels only when justified.

### 10.4 SEO output ownership

IGP owns the structured SEO source data. If Rank Math is inactive, IGP may output SEO metadata directly. If Rank Math is active and the bridge is enabled, IGP must provide data to Rank Math through the bridge and suppress duplicate direct output.

### 10.5 Required SEO fields

Content Graph or page meta must support:

```text
seo.h1
seo.title
seo.description
seo.canonical_url
seo.robots
seo.og_title
seo.og_description
seo.og_image_id
seo.schema_policy
seo.focus_topics[]
seo.internal_link_targets[]
```

---

## 11. Content Projection Rules

IGP must maintain a service that projects structured Content Graph data into multiple target representations.

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

The projection service must not replace the renderer. It exists to make structured content understandable to SEO tools, link tools, audits, and AI systems.

---

## 12. Smart Block Rules

### 12.1 Shared style object

Visual blocks must support a shared `style` object where relevant:

```json
{
  "style": {
    "variant": "split-overlay",
    "density": "comfortable",
    "theme": "brand",
    "container": "wide",
    "surface": "elevated",
    "media_position": "left"
  }
}
```

### 12.2 Controlled values only

Block style fields must be schema-defined enums. Arbitrary CSS is not permitted as the primary styling mechanism.

### 12.3 Renderer output rule

The central renderer must output predictable classes:

```text
igp-block
igp-block--{block-id}
igp-variant--{variant}
igp-density--{density}
igp-theme--{theme}
igp-container--{container}
```

### 12.4 Migration rule

Existing V1 block data that lacks `style` must render using safe defaults and must be migratable to V2 schema shape.

---

## 13. Brand Token / CSS Engine Rules

### 13.1 Brand profile purpose

Brand profiles provide controlled visual identity across clients and templates. They are not raw CSS dumps.

### 13.2 Token categories

Brand profiles must support:

```text
color tokens
typography tokens
spacing tokens
radius tokens
shadow tokens
button tokens
container tokens
surface tokens
motion tokens, optional and minimal
```

### 13.3 Token cascade

Brand resolution order:

```text
default plugin tokens
↓
active brand profile
↓
starter template defaults
↓
page-level override
↓
block-level style config
```

### 13.4 CSS generation

The engine must generate CSS variables and cache the result. Cache invalidation must occur when brand settings change.

### 13.5 Theme compatibility

The plugin must not assume exclusive control over the entire theme. CSS must be scoped using IGP classes where practical.

---

## 14. Starter Content / Industry Template Rules

### 14.1 Purpose

Starter content must become an industry-template system that accelerates client-site creation while producing varied, structured, SEO-ready content.

### 14.2 Required starter templates

Initial template families:

1. Luxury tours
2. Budget tours
3. Pilgrimage travel
4. International inbound travel

### 14.3 Template package structure

Each template must be versioned and self-describing:

```text
starter-content/templates/{template-id}/
  manifest.json
  brand.json
  pages.json
  tours.json
  destinations.json
  media.json
  seo.json
  link-map.json
  content-graphs/
    home.json
    tours-index.json
    destination-template.json
    tour-template.json
```

### 14.4 Import modes

The importer must support:

```text
preview
dry_run
create_new
merge_existing
rollback_last_import
```

### 14.5 Idempotency rule

Every imported object must store:

```text
_igp_template_source
_igp_template_uuid
_igp_import_batch_id
_igp_template_version
```

Re-importing the same template must not create uncontrolled duplicates.

---

## 15. Media SEO / Optimization Rules

### 15.1 Media scope

The media layer must inspect and manage images used in:

- featured images
- hero images
- block image fields
- galleries
- tour cards
- destination cards
- Open Graph images
- JSON-LD images
- imported starter content
- inline rich text images where applicable

### 15.2 Required capabilities

The media layer must support:

```text
page-level media inventory
missing alt text report
filename audit
image dimension audit
WebP generation adapter
lazy-loading policy
LCP image detection/override
responsive size audit
bulk alt update with permissions
media schema mapping
```

### 15.3 Frontend performance rule

No heavy optimization task may run during ordinary frontend rendering.

### 15.4 LCP image rule

The likely hero/LCP image must not be lazy-loaded by default. Below-fold images should be lazy-loaded unless explicitly excluded.

---

## 16. Rank Math Pro Integration Rules

### 16.1 Integration philosophy

IGP owns structured SEO data. Rank Math may output, enhance, and analyze that data when active.

### 16.2 Dependency rule

Rank Math Pro is optional. IGP must not fatal if Rank Math is inactive or absent.

### 16.3 Duplicate-output rule

When Rank Math bridge is active:

- IGP computes SEO data.
- IGP passes data to Rank Math through a bridge.
- Rank Math outputs frontend meta/schema where appropriate.
- IGP suppresses duplicate direct meta/schema output.

### 16.4 Bridge targets

The bridge should support:

```text
SEO title
meta description
canonical URL
robots
Open Graph title
Open Graph description
Open Graph image
schema graph data
breadcrumb data
analysis content projection
image analysis content projection
link analysis content projection
```

### 16.5 Sync mode

Runtime bridge is default. Optional sync mode may write Rank Math-compatible post meta only if explicitly enabled.

---

## 17. Link Whisper Pro Integration Rules

### 17.1 Integration philosophy

Link Whisper is a companion opportunity tool, not the source of truth for IGP links.

### 17.2 Content projection requirement

Because IGP content lives in structured JSON, the Content Projection Service must expose meaningful text/HTML to internal-link analysis tools.

### 17.3 Link approval rule

Internal links suggested by IGP or Link Whisper must be reviewed and stored in the Content Graph before output. Blind auto-linking is not allowed by default.

### 17.4 Internal link consumers

Internal link intelligence must use:

- tour/destination relationships
- content graph headings
- FAQ questions
- itinerary locations
- destination clusters
- related tours
- orphan content reports
- inbound/outbound link reports where available

---

## 18. High-Utility Travel Block Expansion Rules

New blocks must only be added when they improve SEO, usability, user experience, conversion, or structured travel content.

### 18.1 Priority blocks

P0 blocks:

```text
Tour Facts / Quick Info
Inclusions / Exclusions
Departure Dates / Availability
Package Tiers / Price Comparison
Reviews Summary / Aggregate Trust
```

P1 blocks:

```text
Visa / Travel Requirements
Best Time to Visit
Route / Stops Timeline
Expert / Travel Consultant Box
Sticky Booking CTA
Nearby Attractions
Download Brochure CTA
```

### 18.2 Blocks to avoid unless justified

```text
arbitrary HTML block
heavy carousel block
freeform page-builder block
unvalidated script/embed block
complex inventory grid
```

---

## 19. MCP / AI Automation Rules

### 19.1 Architecture rule

IGP must expose controlled REST endpoints first. MCP tools must call those REST endpoints. MCP must not directly mutate WordPress data.

Required flow:

```text
AI client
↓
MCP server
↓
IGP REST API
↓
IGP permission layer
↓
IGP validator
↓
IGP service layer
↓
snapshot/logger
↓
WordPress storage
```

### 19.2 Tool safety rule

Every MCP write operation must:

- authenticate
- check capabilities
- validate input schema
- create a snapshot where applicable
- log actor/source/timestamp
- return structured success/error results
- avoid publishing directly on production by default

### 19.3 Production approval rule

Production mode must default to draft/changeset workflow:

```text
AI proposes change
IGP validates change
IGP stores changeset/draft
human reviews diff
human approves publish
IGP logs and snapshots final write
```

### 19.4 Allowed MCP operations

Allowed after REST safety layer exists:

```text
list pages/tours/destinations
read content graph
validate content graph
create draft content graph update
reorder sections
delete section with snapshot
import template dry run
run SEO audit
run media audit
suggest internal links
create starter content import preview
```

### 19.5 Disallowed MCP operations by default

```text
direct database writes
plugin/theme file editing
arbitrary PHP execution
arbitrary SQL execution
direct publish on production without approval
credential reading
payment setting modification
capability modification
log deletion
snapshot deletion
```

---

## 20. Permissions and Roles

V2 must define explicit capabilities:

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

Administrators may receive all capabilities. Content editors should receive only content-editing capabilities unless explicitly configured.

---

## 21. Logging and Recovery

### 21.1 Required log fields

Every critical action must log:

```text
timestamp
actor_user_id
actor_type: human | import | rest | mcp | system
operation
object_type
object_id
source_module
status
error_code
summary
snapshot_id, if applicable
```

### 21.2 Required snapshot fields

Snapshots must include:

```text
snapshot_id
object_type
object_id
before_data
after_data, optional
actor_user_id
source_module
created_at
rollback_status
```

### 21.3 Recovery rules

Rollback must never silently overwrite newer edits without warning. If the current content has changed since snapshot creation, the rollback UI must show a conflict notice.

---

## 22. Performance Rules

1. Frontend rendering must remain server-rendered and cache-aware.
2. External API responses must be cached.
3. Brand CSS must be generated and cached.
4. Media audits must not run during frontend page load.
5. Relationship queries must avoid unbounded loops.
6. Internal link analysis must not run on every frontend request.
7. MCP operations must be rate-limited.
8. REST write endpoints must avoid large uncontrolled payloads.
9. Starter imports must be batchable and recoverable.
10. Block rendering must degrade safely if optional modules fail.

---

## 23. Canonical Target File Structure

This is the V2 target structure. Do not create every file upfront. Create files phase-by-phase according to the active blueprint step.

```text
igp-pro.php
uninstall.php
current_status.json
STRUCTURE.md

includes/
  core/
    loader.php
    helpers.php
    feature-flags.php
    logger.php
    capabilities.php
    diagnostics.php

  cpt/
    register-cpt.php
    taxonomies.php

  blocks/
    registry.php
    renderer.php
    resolver.php
    block-supports.php
    heading-support.php
    style-support.php
    hero/
    section-wrapper/
    destination-cards/
    tour-cards/
    featured-listings/
    cta/
    trust/
    pricing-summary/
    rich-text/
    itinerary/
    gallery/
    faq/
    map/
    breadcrumb/
    icon-list/
    stats/
    tabs/
    accordions/
    related-tours/
    related-destinations/
    tour-facts/
    inclusions-exclusions/
    departure-dates/
    package-tiers/
    visa-requirements/
    best-time-to-visit/
    route-timeline/
    reviews-summary/
    expert-box/
    sticky-booking-cta/
    nearby-attractions/
    brochure-cta/

  content/
    content-graph.php
    validator.php
    sanitizer.php
    importer.php
    exporter.php
    projection.php
    changeset.php

  relationships/
    relationships.php
    relationship-validator.php
    relationship-admin.php
    relationship-queries.php

  seo/
    seo-engine.php
    schema-generator.php
    outline-engine.php
    heading-policy.php
    seo-audit.php
    internal-linking.php

  design/
    design-tokens.php
    brand-profiles.php
    css-generator.php
    token-validator.php

  starter-content/
    template-registry.php
    template-validator.php
    template-importer.php
    template-preview.php
    template-rollback.php
    templates/
      luxury-tours/
      budget-tours/
      pilgrimage-travel/
      international-inbound/

  media/
    media-inventory.php
    media-audit.php
    image-optimizer.php
    alt-text-service.php
    webp-adapter.php
    lazy-loading-policy.php

  booking/
    booking-engine.php
    pricing-engine.php
    payment-adapters/

  performance/
    cache.php
    cwv.php

  migration/
    block-migrations.php
    content-graph-migrations.php
    schema-version-map.php

  recovery/
    snapshots.php
    rollback.php

  integrations/
    rank-math/
      rank-math-bridge.php
      schema-mapper.php
      content-provider.php
    link-whisper/
      link-whisper-bridge.php
      content-provider.php
      opportunity-mapper.php

  api/
    rest-routes.php
    rest-permissions.php
    controllers/
      content-controller.php
      template-controller.php
      media-controller.php
      seo-controller.php
      relationship-controller.php
      mcp-controller.php

  mcp/
    tool-registry.php
    tool-schemas.php
    tool-permissions.php
    audit.php

  admin/
    content-editor.php
    booking-panel.php
    seo-panel.php
    settings.php
    relationships-panel.php
    media-panel.php
    starter-content-panel.php
    brand-panel.php
    integrations-panel.php
    diagnostics-panel.php
    recovery-panel.php

assets/
  js/
    content-editor.js
    admin-relationships.js
    admin-media.js
    admin-starter-content.js
    admin-brand.js
    admin-integrations.js
    admin-recovery.js
  css/
    admin.css
    frontend-base.css
    frontend-generated.css

storage/
  logs/
  cache/
  snapshots/
  generated-css/

tools/
  mcp-server/
    README.md
    package.json
    src/
      server.ts
      igp-client.ts
      tools.ts
      schemas.ts
```

---

## 24. AI Implementation Context

When ChatGPT or any AI system implements this project, it must follow these rules:

1. Read `project_charter_V2.md`, `Blueprint_V2.md`, and `gates_V2.md` before writing code.
2. Treat V2 documents as controlling documents for Phase 6 onward.
3. Treat V1 documents as still binding for Phases 1–5.
4. Never skip a gate.
5. Never implement a later phase before the current phase is gate-passed.
6. Never introduce architecture drift outside the documented file structure without explicitly updating the documents.
7. Never bypass existing service layers.
8. Never store unvalidated Content Graph data.
9. Never allow MCP/AI writes to bypass REST permissions and validators.
10. Never create raw CSS/importer shortcuts in place of token systems.
11. Never assume Rank Math or Link Whisper exists.
12. Never duplicate SEO output when Rank Math bridge is active.
13. Always provide rollback notes for data-changing steps.
14. Always update `STRUCTURE.md` and `current_status.json` only after gate/user approval.
15. Always keep changes phase-scoped and reversible.

---

## 25. Source of Truth

This document defines V2 goals, scope, constraints, architecture, linkages, file structure, and AI context. If implementation conflicts with this document, this document wins unless the user explicitly updates it.

