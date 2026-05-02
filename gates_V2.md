# IGP Pro Plugin — Gates V2

**Document role:** Acceptance-test source of truth for V2 implementation.  
**Version:** V2.0  
**Baseline:** V1 through Phase 5 is complete and gate-passed. V1 gates remain binding.  
**Next gate:** Gate 19 — Safety Foundation.

---

## 1. Purpose

This document defines the exact validations required before any V2 phase or step can be considered complete.

A phase is complete only when its assigned gates pass on the real plugin in a real WordPress environment, preferably LocalWP first and staging before production.

---

## 2. General Gate Rules

1. No gate, no progress.
2. Gates must be tested on the real plugin, not only by reading code.
3. Static checks are required but not sufficient.
4. Visual output alone is not enough.
5. Data correctness, security, recovery, and stability matter equally.
6. A later gate cannot excuse an earlier failure.
7. If a gate fails, stop immediately.
8. A failed gate must be isolated, fixed, retested, and passed before continuing.
9. User approval is required before marking a phase complete in `current_status.json`.
10. `STRUCTURE.md` must be updated only after gate pass and user approval.

---

## 3. Required Test Environment

Minimum test environment:

```text
WordPress latest stable or project-approved version
PHP version matching production target
IGP Pro plugin active
IGP Travel Pro theme or compatible test theme active
Pretty permalinks enabled
WP_DEBUG enabled during development testing
WP_DEBUG_LOG enabled during development testing
Browser console visible for admin/editor testing
Rank Math Pro active/inactive scenarios tested where relevant
Link Whisper Pro active/inactive scenarios tested where relevant
```

Optional but recommended:

```text
Query Monitor plugin
PageSpeed Insights API key for CWV checks
staging copy before production use
```

---

## 4. Static Validation Required for Every Phase

Before any phase-specific gate is tested, run static validation.

Pass if:

- all PHP files pass syntax lint
- all JSON schemas parse successfully
- all block IDs are unique
- no direct access vulnerability is introduced in new PHP files
- no fatal error occurs on plugin activation
- no obvious secrets/API keys are committed
- no new file is outside the approved structure unless documented

Fail if:

- any PHP parse error exists
- any schema JSON is invalid
- duplicate block IDs exist
- new business logic is placed in the main plugin file
- new feature bypasses central service/registry rules
- secret credentials are committed

---

## 5. V1 Regression Rule

Because V2 builds on V1, the following V1 areas must be spot-checked after every major V2 phase:

```text
plugin activation
CPT admin visibility
block registry
central renderer
content graph load/save
content editor load/save
import/export
booking/enquiry flow
SEO output
CWV/cache behavior
```

Fail if any previously passed V1 behavior breaks.

---

## 6. V2 Gate List

---

# Gate 19: Safety Foundation

Covers:

- feature flags
- safe module loading
- no V1 regression

Pass if:

- feature flags are stored in a controlled option
- default flags load without warnings
- each V2 module can check its flag
- disabling a module prevents that module's admin panel/routes/output from loading unnecessarily
- plugin activates with all flags disabled
- plugin activates with each new flag enabled individually
- no Phase 1–5 feature breaks

Fail if:

- disabled modules still execute write logic
- enabling a flag causes fatal error
- flag values are not sanitized
- feature checks are duplicated inconsistently
- V1 behavior regresses

Required manual tests:

1. Activate plugin with all V2 flags disabled.
2. Enable one flag at a time.
3. Visit admin dashboard, content editor, frontend page, and settings page.
4. Confirm no PHP warning/fatal occurs.

---

# Gate 20: Permissions and Roles

Covers:

- capabilities
- admin access
- REST permission foundations

Pass if:

- administrators have full IGP capabilities
- non-admin users do not receive privileged capabilities automatically
- content editors can be granted restricted IGP access
- restricted users cannot access settings, integrations, recovery, media optimization, template import, or MCP controls unless granted
- REST permission callbacks use IGP capabilities
- admin menu items are hidden from unauthorized users

Fail if:

- unauthorized user can edit restricted modules
- REST endpoint accepts write request based only on logged-in status
- capability checks are missing
- editor can manage integrations or recovery without permission
- admin UI hides button but backend still allows action

Required tests:

1. Test as administrator.
2. Test as editor/content editor.
3. Test as subscriber or unauthenticated user for REST endpoints.
4. Attempt restricted writes directly through REST.

---

# Gate 21: Logging

Covers:

- structured logging
- diagnostics visibility
- failure traceability

Pass if:

- logger records successful critical operations
- logger records failed critical operations
- logs include actor, operation, object, source module, status, and timestamp
- logs do not expose credentials or secrets
- diagnostics panel can show recent relevant logs
- logging failure does not break frontend rendering

Fail if:

- failures are silent
- log entries are unstructured
- secrets are logged
- logs are publicly accessible
- logger causes fatal errors when storage is unavailable

Required tests:

1. Trigger a successful settings update.
2. Trigger a validation failure.
3. Trigger a permission failure.
4. Confirm logs exist and are readable only to authorized users.

---

# Gate 22: Snapshot and Recovery

Covers:

- snapshots
- rollback
- conflict detection

Pass if:

- snapshots can be created before data-changing operations
- snapshot includes sufficient before-state to restore content
- rollback restores a previous Content Graph state
- rollback logs actor and result
- rollback detects if current content changed since snapshot
- failed rollback does not corrupt current data

Fail if:

- destructive operations occur without snapshots
- rollback overwrites newer edits silently
- snapshot data is incomplete
- snapshot storage is publicly exposed
- rollback breaks schema versioning

Required tests:

1. Save a page graph.
2. Create a snapshot.
3. Modify the graph.
4. Roll back.
5. Confirm old graph returns exactly or with documented migration-safe normalization.
6. Test conflict warning after additional edit.

---

# Gate 23: Migration Safety

Covers:

- block migrations
- Content Graph migrations
- schema version handling

Pass if:

- migration registry loads
- old V1 Content Graph data still renders
- old V1 block data receives defaults for new V2 fields
- missing migration path fails safely with clear error
- migration creates snapshot before permanent update
- migrated graph validates after migration

Fail if:

- old pages break
- old block versions cause fatal errors
- migration silently drops fields
- schema version mismatch is ignored
- migration mutates data without snapshot

Required tests:

1. Load a V1 page graph with no V2 heading/style fields.
2. Migrate or render with fallback defaults.
3. Confirm frontend still renders.
4. Confirm editor can save migrated graph.
5. Export and re-import migrated graph.

---

# Gate 24: Entity Relationship Layer

Covers:

- tour/destination relationships
- relationship queries
- relationship admin UI

Pass if:

- a tour can store one primary destination
- a tour can store multiple secondary destinations
- a tour can store route stops where applicable
- destination page can query related tours
- related tour/destination blocks can consume relationship data
- invalid post IDs are rejected
- wrong post types are rejected
- deleted referenced posts do not fatal rendering
- relationships export/import correctly

Fail if:

- relationship data saves without validation
- tour points to invalid destination without warning
- deleting destination corrupts tour data
- related blocks run unbounded queries
- relationship UI bypasses service layer

Required tests:

1. Create two destinations and three tours.
2. Assign primary/secondary destinations.
3. Query tours from destination.
4. Render related block.
5. Delete one destination and reload tour/frontend.
6. Export/import relationship data.

---

# Gate 25: Content Projection

Covers:

- projection service
- SEO/link/AI readable content

Pass if:

- projection service returns clean semantic output from Content Graph
- headings, paragraphs, FAQs, itinerary labels, tour facts, and destination names are included
- decorative-only fields are excluded
- projections do not mutate original graph
- projections are available for SEO audit, Rank Math bridge, Link Whisper bridge, and MCP context

Fail if:

- projection returns blank content for valid page
- projection contains unsafe markup
- projection mutates graph
- projection omits major semantic fields
- projection triggers block rendering side effects

Required tests:

1. Create page with hero, cards, FAQ, itinerary, gallery, CTA.
2. Generate all required projections.
3. Compare projection text against expected semantic content.
4. Confirm original Content Graph unchanged.

---

# Gate 26: Semantic SEO Outline

Covers:

- H1 policy
- H2–H4 controls
- heading validation

Pass if:

- exactly one H1 renders on frontend IGP page
- H1 resolution follows explicit SEO H1 → post title → primary hero fallback
- block heading controls allow H2, H3, H4 only
- invalid heading hierarchy is detected before save or audit flags it clearly
- empty required visible heading is rejected
- existing V1 pages receive safe heading defaults

Fail if:

- page renders zero H1
- page renders multiple H1s
- Hero block independently creates duplicate H1
- block allows H1 selection
- H2 → H4 jumps are allowed without warning
- hidden heading conflicts with visible heading semantics

Required tests:

1. Test page title as H1.
2. Test explicit SEO H1 override.
3. Test hero fallback only when allowed.
4. Test block heading levels.
5. Inspect frontend DOM.
6. Run SEO audit panel.

---

# Gate 27: Crawler-Friendly DOM

Covers:

- semantic wrappers
- accessibility attributes
- server-rendered content

Pass if:

- content blocks render meaningful server-side HTML
- section blocks use semantic wrappers where appropriate
- heading-enabled sections use stable IDs
- `aria-labelledby` is present when appropriate
- CTA/decorative blocks do not create fake headings
- primary content is visible in initial HTML without requiring frontend JS rendering

Fail if:

- core content is hidden from crawlers
- block output is empty until JavaScript runs
- sections lack meaningful semantic structure
- fake headings are used for visual styling only
- IDs duplicate on the page

Required tests:

1. View page source, not only browser inspector.
2. Confirm meaningful text exists in HTML source.
3. Confirm section IDs are unique.
4. Disable JavaScript and confirm primary content remains visible.

---

# Gate 28: SEO Health Dashboard

Covers:

- SEO audit panel
- structured checks

Pass if:

- dashboard reports H1 count
- dashboard reports heading hierarchy issues
- dashboard reports missing/weak meta title and description
- dashboard reports schema presence/status
- dashboard reports missing image alt text
- dashboard reports internal link counts
- dashboard reports orphan risk where data exists
- dashboard reports CWV/cache summary where available

Fail if:

- dashboard shows false pass for broken page
- dashboard crashes on incomplete graph
- dashboard duplicates Rank Math output without clarity
- dashboard requires external plugin to function

Required tests:

1. Test a healthy page.
2. Test page with missing meta description.
3. Test page with bad heading hierarchy.
4. Test page with missing image alt.
5. Test Rank Math active and inactive.

---

# Gate 29: Smart Block Variant System

Covers:

- style schema
- variants
- density/theme/container classes

Pass if:

- visual blocks expose schema-defined style object
- variants are defined per block
- invalid variants are rejected
- old V1 block data renders with defaults
- renderer outputs stable class names
- variant changes do not mutate content fields
- no arbitrary CSS is required for normal variation

Fail if:

- style values are freeform without validation
- invalid variant renders broken layout
- block renderer hardcodes client-specific styling
- old pages lose layout
- variants require duplicated render files without shared support

Required tests:

1. Edit Hero variant.
2. Edit CTA variant.
3. Edit card block layout.
4. Save and reload editor.
5. Inspect frontend class output.
6. Try invalid variant in JSON import and confirm rejection.

---

# Gate 30: Brand Token Engine

Covers:

- design tokens
- brand profiles
- validation

Pass if:

- default plugin tokens load
- brand profile can be created/selected
- token values validate
- invalid colors/sizes are rejected
- token cascade works: default → brand → template/page/block
- brand changes affect frontend output through generated variables
- no raw CSS importer is required for normal brand control

Fail if:

- invalid token breaks frontend CSS
- brand override leaks to unrelated site sections unexpectedly
- changing profile requires editing block content
- raw custom CSS becomes primary styling mechanism
- token storage is inconsistent

Required tests:

1. Create brand profile.
2. Change primary color/radius/spacing scale.
3. Save.
4. Confirm generated CSS changes.
5. Confirm old pages still render.

---

# Gate 31: Generated CSS Cache

Covers:

- generated CSS
- cache invalidation
- frontend loading

Pass if:

- CSS variables are generated from tokens
- generated CSS is cached
- cache invalidates on brand/token change
- frontend loads correct CSS
- missing generated CSS regenerates safely
- CSS is scoped to IGP classes where practical

Fail if:

- stale CSS persists after brand update
- generated CSS file missing causes broken layout without fallback
- CSS generation runs repeatedly on every frontend request
- CSS is globally destructive to theme styles

Required tests:

1. Generate CSS.
2. Change token.
3. Confirm cache invalidation.
4. Delete generated file/cache and reload safely.
5. Inspect frontend network/source.

---

# Gate 32: Travel Block Expansion

Covers:

- P0/P1 high-utility travel blocks
- schema/render/SEO integration

Pass if:

- each new block has schema, defaults, variants, validation, and render path
- each new block registers centrally
- each new block renders through central renderer
- heading/style support works where applicable
- block data exports/imports correctly
- block contributes to SEO/schema only through approved service
- frontend output is accessible and performant

Fail if:

- block bypasses registry or renderer
- schema is incomplete
- render path fatals on missing data
- block introduces heavy JS without fallback
- block creates duplicate schema/meta output
- block cannot be edited through schema UI

Required tests:

1. Add each new block to a page graph.
2. Save/load in editor.
3. Render frontend.
4. Export/import graph.
5. Validate SEO audit output.
6. Inspect PHP/JS errors.

---

# Gate 33: Starter Template Registry

Covers:

- template manifest
- template discovery
- template validation base

Pass if:

- template registry discovers available templates
- each template has valid manifest
- required blocks/features are declared
- version is declared
- brand profile is declared or explicitly omitted
- invalid manifest is rejected

Fail if:

- malformed template appears as importable
- missing required blocks are ignored
- template version is absent
- registry hardcodes templates without manifest validation

Required tests:

1. Register valid template.
2. Register intentionally invalid template.
3. Confirm invalid template is not importable.
4. Confirm admin panel shows template metadata.

---

# Gate 34: Starter Template Dry Run

Covers:

- preview
- validation without writes

Pass if:

- dry run performs no database writes
- preview shows pages/tours/destinations/sections/media/SEO/relationships to be created or updated
- missing blocks are reported
- invalid content graphs are rejected
- duplicate template UUIDs are detected
- relationship mapping problems are detected

Fail if:

- dry run creates posts/meta/options
- invalid graphs pass preview
- duplicate import risk is hidden
- preview output is misleading

Required tests:

1. Run dry run on valid template.
2. Check database object count before/after.
3. Run dry run on invalid template.
4. Confirm validation errors are explicit.

---

# Gate 35: Starter Template Importer

Covers:

- real import
- idempotency
- structured content creation

Pass if:

- import creates expected pages
- import creates expected tours/destinations
- import stores Content Graphs
- import stores SEO fields
- import stores relationships
- import applies brand profile/defaults
- import stores template source UUIDs
- re-import does not create uncontrolled duplicates
- merge mode snapshots existing objects before update

Fail if:

- import accepts invalid template
- content graph corruption occurs
- relationships are lost
- re-import duplicates everything
- user content is overwritten without snapshot
- imported content is not editable in IGP editor

Required tests:

1. Import luxury template.
2. Verify object counts and metadata.
3. Re-import same template.
4. Confirm idempotency.
5. Test merge on modified existing object.

---

# Gate 36: Starter Template Rollback

Covers:

- import rollback
- batch tracking

Pass if:

- importer stores import batch ID
- rollback identifies created vs modified objects
- rollback can remove created template objects where safe
- rollback can restore modified objects from snapshot
- rollback does not delete unrelated user-created content
- rollback logs result

Fail if:

- rollback deletes wrong content
- modified content cannot be restored
- batch ID missing
- rollback fails silently
- rollback ignores conflicts

Required tests:

1. Import template.
2. Modify one imported page manually.
3. Run rollback.
4. Confirm conflict behavior.
5. Confirm unrelated content remains.

---

# Gate 37: System Stability Regression

Covers:

- recurring full stability checks

Pass if:

- no PHP warnings/notices in core workflows
- no JS console errors in admin panels
- no broken admin navigation
- no unbounded frontend query loops
- no major performance regression from disabled optional modules
- plugin can deactivate/reactivate safely

Fail if:

- intermittent fatal errors occur
- admin panels break each other
- disabled modules still run heavy operations
- frontend performance degrades without active feature use
- cache invalidation breaks content display

Required tests:

1. Navigate all IGP admin panels.
2. Render several frontend pages.
3. Save content graph.
4. Import/export graph.
5. Activate/deactivate plugin in LocalWP.

---

# Gate 38: Media Inventory

Covers:

- page/tour/destination media detection

Pass if:

- inventory detects featured image
- inventory detects hero image
- inventory detects block images
- inventory detects gallery images
- inventory detects OG/schema images
- inventory identifies likely LCP image
- inventory handles missing/deleted images without fatal

Fail if:

- major images are missing from inventory
- deleted attachment causes fatal
- media fields outside featured image are ignored
- inventory runs expensive queries repeatedly without caching

Required tests:

1. Create page with featured image, hero image, gallery, card images.
2. Run inventory.
3. Delete one image.
4. Run inventory again.
5. Confirm safe degraded report.

---

# Gate 39: Media SEO Audit

Covers:

- alt text
- filenames
- dimensions
- responsive image policy

Pass if:

- missing alt text is detected
- weak/generic alt text is flagged
- bad filename patterns are detected
- oversized images are flagged
- missing dimensions are flagged
- missing OG/schema image is flagged
- audit can be run per page/tour/destination
- unauthorized users cannot bulk-edit media SEO data

Fail if:

- missing alt text passes silently
- audit crashes on external/missing media
- bulk alt update bypasses capability checks
- audit requires frontend load to run

Required tests:

1. Add images with no alt text.
2. Add image with generic filename.
3. Run audit.
4. Bulk update alt as authorized user.
5. Attempt bulk update as unauthorized user.

---

# Gate 40: Media Optimization

Covers:

- WebP conversion
- lazy loading policy
- recovery

Pass if:

- WebP generation works where server supports it
- original images remain available
- unsupported server fails safely
- conversion does not run on normal frontend request
- hero/LCP image is not lazy-loaded by default
- below-fold images lazy-load where appropriate
- optimized image paths do not break schema/OG output

Fail if:

- original image is destroyed
- frontend page load triggers heavy conversion
- WebP failure breaks rendering
- LCP image lazy-loads incorrectly
- image URLs become invalid

Required tests:

1. Convert image to WebP.
2. Confirm original remains.
3. Disable required PHP image capability if possible and test failure.
4. Inspect frontend image attributes.
5. Confirm hero image eager/default behavior.

---

# Gate 41: Rank Math Bridge

Covers:

- optional Rank Math integration
- duplicate SEO prevention
- fallback behavior

Pass if:

- IGP works when Rank Math is inactive
- IGP detects Rank Math active safely
- bridge can pass SEO title/description/canonical/robots/OG/schema/breadcrumb data where supported
- duplicate meta/schema output is prevented
- runtime bridge works without writing Rank Math post meta
- optional sync mode is disabled by default
- disabling bridge returns control to IGP fallback or normal Rank Math behavior

Fail if:

- Rank Math absence causes fatal
- duplicate title/meta/schema appears
- IGP overwrites Rank Math fields unexpectedly
- bridge writes post meta without explicit sync setting
- Rank Math active causes IGP SEO audit to break

Required tests:

1. Test with Rank Math inactive.
2. Test with Rank Math active and bridge disabled.
3. Test with Rank Math active and bridge enabled.
4. Inspect page source for duplicate tags/schema.
5. Test sync mode only if explicitly enabled.

---

# Gate 42: Link Whisper Companion

Covers:

- optional Link Whisper integration
- projection compatibility
- safe degradation

Pass if:

- IGP works when Link Whisper is inactive
- Link Whisper active does not break IGP
- IGP projection content is available for link analysis workflows where possible
- suggestions are mapped into reviewable opportunities
- links are not blindly auto-inserted by default
- approved links persist in Content Graph

Fail if:

- Link Whisper absence causes fatal
- links are inserted without review
- hidden/cloaked links are generated
- Content Graph is modified by external plugin without validation
- repeated anchor spam is allowed

Required tests:

1. Test Link Whisper inactive.
2. Test Link Whisper active.
3. Generate link opportunities.
4. Approve one link.
5. Export/import page and confirm link persists.

---

# Gate 43: Internal Link Intelligence

Covers:

- IGP-native link suggestions
- relationship-aware internal links
- orphan risk

Pass if:

- relationship-aware links are suggested
- orphan pages can be identified or approximated
- anchor text duplication is flagged
- approved links are stored in Content Graph
- frontend output includes approved links naturally
- suggestions do not create circular or irrelevant spam patterns

Fail if:

- suggestions ignore relationships
- hidden links are output
- auto-linking creates poor UX
- links disappear after save/export/import
- link suggestions run expensive scans on every frontend request

Required tests:

1. Create destination with related tours.
2. Generate suggestions.
3. Approve one contextual link.
4. Reject another.
5. Confirm approved link renders and rejected link does not.

---

# Gate 44: REST API Safety

Covers:

- REST endpoints
- validation
- permissions
- structured errors

Pass if:

- read endpoints return expected data to authorized users
- write endpoints reject unauthorized users
- write endpoints validate payloads
- invalid Content Graph update is rejected
- section reorder validates section IDs
- section delete creates snapshot
- structured error responses are returned
- endpoints do not expose secrets

Fail if:

- unauthenticated user can write
- subscriber can write restricted data
- invalid graph is saved
- REST endpoint writes directly to post meta bypassing service layer
- endpoint reveals credentials/options/secrets

Required tests:

1. Call endpoints as admin.
2. Call endpoints as editor.
3. Call endpoints unauthenticated.
4. Submit invalid graph.
5. Submit valid draft update.
6. Confirm snapshot/log.

---

# Gate 45: Changeset Workflow

Covers:

- AI/API draft updates
- diff/review
- approval

Pass if:

- REST/MCP proposed edits can be stored as changesets
- changeset does not immediately overwrite published data in production mode
- diff is visible to authorized user
- approve applies validated data
- reject discards proposed data
- publish requires `igp_publish_ai_changes`
- action is logged and snapshotted

Fail if:

- AI/API update publishes immediately by default on production
- changeset bypasses validation
- unauthorized user can approve
- diff is inaccurate
- rejected change still mutates content

Required tests:

1. Submit AI-style draft update.
2. Confirm published graph unchanged.
3. View diff.
4. Approve as authorized user.
5. Reject another changeset.
6. Confirm logs.

---

# Gate 46: MCP Tool Registry

Covers:

- tool definitions
- tool schemas
- permission mapping

Pass if:

- MCP tool registry lists only approved IGP operations
- each tool has input schema
- each write tool maps to REST/service validation
- destructive tools require snapshot support
- tools do not expose credentials
- MCP feature flag can disable all MCP tooling

Fail if:

- arbitrary PHP/SQL/file tools exist
- write tools lack input schema
- tool can bypass REST permissions
- MCP tools remain active while feature flag disabled
- secrets are included in tool/resource output

Required tests:

1. Enable MCP flag and inspect available tools.
2. Disable MCP flag and confirm tools unavailable.
3. Attempt invalid tool payload.
4. Attempt destructive tool without permission.

---

# Gate 47: MCP / AI Bridge Safety

Covers:

- external MCP server wrapper
- REST-only mutation
- production safety

Pass if:

- MCP server calls IGP REST API only
- MCP server does not connect directly to database
- MCP server cannot edit plugin/theme files
- MCP write operations authenticate
- MCP write operations validate through IGP
- production mode defaults to draft/changeset
- destructive operations create snapshots
- operations are logged with actor/source
- rate limiting or equivalent abuse mitigation exists

Fail if:

- MCP can execute arbitrary code
- MCP can run arbitrary SQL
- MCP can write post meta directly
- MCP can publish/delete on production without approval
- MCP bypasses Content Graph validator
- logs do not identify MCP-originated actions

Required tests:

1. Start MCP server against LocalWP test site.
2. List pages.
3. Read graph.
4. Validate invalid graph and confirm rejection.
5. Submit draft update and confirm changeset.
6. Reorder sections and confirm snapshot.
7. Attempt forbidden operation and confirm rejection.

---

# Gate 48: Full System Regression

Covers:

- complete V1/V2 regression
- plugin stability
- optional integrations

Pass if:

- plugin activates without fatal error
- all V1 phase workflows still work
- all V2 modules pass their gates
- Rank Math active/inactive scenarios pass
- Link Whisper active/inactive scenarios pass
- MCP disabled exposes no public write surface
- frontend output remains server-rendered
- admin workflows show no console errors
- no PHP warnings/notices appear in normal workflows
- cache invalidation works
- rollback works

Fail if:

- any previously passed V1 gate regresses
- any V2 module fails its gate
- optional integration becomes required
- performance degrades severely with disabled features
- admin navigation breaks
- frontend markup becomes unstable

Required tests:

1. Run V1 spot-checks.
2. Run V2 gates 19–47.
3. Test with optional integrations off.
4. Test with optional integrations on.
5. Test plugin deactivate/reactivate.
6. Review debug log.

---

# Gate 49: Production Readiness

Covers:

- final release safety
- documentation
- operational readiness

Pass if:

- `current_status.json` accurately records completed phases
- `STRUCTURE.md` matches actual file structure
- feature flags are documented
- capabilities are documented
- migration paths are documented
- rollback procedures are documented
- admin panels are permission-gated
- REST endpoints are permission-tested
- MCP is disabled by default unless explicitly enabled
- starter import rollback is documented
- Rank Math duplicate-output behavior is documented
- Link Whisper integration limitations are documented
- media optimizer limitations are documented

Fail if:

- docs do not match code
- production defaults are unsafe
- MCP is enabled by default unexpectedly
- rollback procedure is missing
- feature flags are undocumented
- source-of-truth documents are stale

Required tests:

1. Compare `STRUCTURE.md` to actual tree.
2. Compare `current_status.json` to completed gates.
3. Review admin settings defaults.
4. Confirm production-sensitive features default safe.
5. Confirm final user approval before marking complete.

---

## 7. Gate Enforcement Procedure

If a gate fails:

1. Stop all forward implementation.
2. Identify the failing gate and exact condition.
3. Isolate the module and file responsible.
4. Fix the issue with the smallest safe change.
5. Re-run the failed gate.
6. Re-run nearby regression checks.
7. Do not update `current_status.json` until the user confirms the fix passes.

---

## 8. Validation Priority

When tradeoffs occur, prioritize in this order:

1. data integrity
2. security and permissions
3. recovery/rollback
4. rendering correctness
5. SEO correctness
6. editor correctness
7. accessibility
8. booking correctness
9. performance
10. integration compatibility
11. visual variation
12. AI automation convenience

AI automation must never outrank data integrity, security, or recovery.

---

## 9. Source of Truth

This document is the authority for V2 acceptance testing and progression control. If an implementation step cannot pass the relevant gate, that step is not complete.

