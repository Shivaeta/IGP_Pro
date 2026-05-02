# IGP Pro Plugin — Gates

## 1. Purpose
This document defines the acceptance tests and progress gates for IGP Pro Plugin. A step is complete only when its gate passes.

## 2. General Rules
- No gate, no progress.
- A gate must be tested on the real plugin.
- Visual output alone is not enough.
- Data correctness, recovery, and stability matter equally.
- A later gate cannot excuse an earlier failure.

## 3. Gate List

### Gate 1: Plugin Activation
Pass if:
- plugin activates without fatal error
- constants and loader are valid
- no white screen

Fail if:
- activation crashes
- include path breaks
- direct access error occurs

### Gate 2: CPT Layer
Pass if:
- Tours visible in admin
- Destinations visible in admin
- slugs and archives work

Fail if:
- post types missing
- rewrite failures
- archive pages broken

### Gate 3: Block Registry
Pass if:
- registry loads
- block list is accurate
- duplicate IDs are prevented

Fail if:
- invalid schema paths
- duplicate IDs
- registry returns incorrect data

### Gate 4: Central Renderer
Pass if:
- render_block outputs HTML
- missing blocks fail safely
- no bypassed rendering path

Fail if:
- fatal render errors
- blank output without fallback
- direct rendering outside controller

### Gate 5: Hero Block
Pass if:
- schema exists
- required fields are enforced
- block renders correctly

Fail if:
- schema mismatch
- image or CTA data fails
- unsafe markup appears

### Gate 6: Content Graph Backbone
Pass if:
- JSON saves and reloads correctly
- invalid JSON is rejected
- round-trip preserves data

Fail if:
- data corruption
- broken serialization
- missing section data

### Gate 7: Schema-to-UI Mapping
Pass if:
- fields render from schema
- nested objects work
- repeaters work when used

Fail if:
- controls mis-map
- field types break
- editor state loses data

### Gate 8: Content Editor Workflow
Pass if:
- page selection works
- load works
- sections are editable
- save persists
- meta description updates

Fail if:
- editor desync
- save failure
- missing section panels

### Gate 9: Import / Export
Pass if:
- export file is valid
- import validates version and schema
- re-import reproduces structure

Fail if:
- invalid file accepted
- data lost on import
- schema mismatch ignored

### Gate 10: Booking Engine
Pass if:
- booking mode works
- enquiry mode works
- submissions stored
- admin can inspect records

Fail if:
- forms fail
- state not saved
- payment path broken

### Gate 11: SEO Engine
Pass if:
- meta output exists
- JSON-LD validates
- schema is derived from content

Fail if:
- invalid structured data
- duplicate meta
- incorrect content mapping

### Gate 12: CWV Integration
Pass if:
- PageSpeed data fetched
- cached results reused
- fallback exists on API failure

Fail if:
- repeated API calls on every request
- no caching
- broken display

### Gate 13: Cache Discipline
Pass if:
- static block cache works
- dynamic cache invalidates correctly
- no stale content after edits

Fail if:
- stale pages persist
- cache never invalidates
- dynamic data cached unsafely

### Gate 14: Block Migration Safety
Pass if:
- old block versions still render
- migration function executes
- schema upgrade does not break pages

Fail if:
- old pages break
- migration missing
- version mismatch causes fatal errors

### Gate 15: Multi-client Configuration
Pass if:
- global defaults load
- page overrides work
- feature flags apply correctly

Fail if:
- settings collide
- brand overrides leak
- per-site config ignored

### Gate 16: Permissions and Roles
Pass if:
- admin has full control
- content editor has restricted access
- settings are protected

Fail if:
- unauthorized user can edit restricted modules
- capability checks missing

### Gate 17: Logging and Recovery
Pass if:
- errors are logged
- snapshots exist
- rollback path works

Fail if:
- silent failure
- no logs
- no recovery point

### Gate 18: System Stability
Pass if:
- no PHP warnings
- no JS errors
- no unbounded query loops
- no broken admin navigation

Fail if:
- intermittent fatal issues
- performance regressions
- unstable editor behavior

## 4. Gate Enforcement
If a gate fails:
1. stop
2. isolate the cause
3. fix the issue
4. retest
5. only then move forward

## 5. Validation Priority
Priority order:
1. data integrity
2. rendering correctness
3. editor correctness
4. booking correctness
5. SEO correctness
6. performance
7. scale and recovery

## 6. Source of Truth
This document is the authority for acceptance testing, step completion, and progression control.
