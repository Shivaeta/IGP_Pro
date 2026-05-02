# IGP Pro Plugin — Blueprint

## 1. Purpose
This document is the execution source of truth for building IGP Pro Plugin. It defines implementation order, dependencies, forward wiring, backward wiring, and deliverables.

## 2. Execution Rules
1. Build in sequence.
2. Do not skip gates.
3. Do not expand into later modules before the current module is validated.
4. Keep folder structure aligned with the active phase only.
5. Every step must produce a testable artifact.

## 3. Phase Map
### Phase 1: Foundation
- Plugin scaffold
- CPT layer
- Block registry
- Central renderer
- Hero block
- Content graph backbone

### Phase 2: Block Library
- First 6 blocks in production detail
- Shared resolver system

### Phase 3: Content Editor
- Field rendering engine
- Auto-generated UI
- Import/export

### Phase 4: Booking and Enquiry
- Booking engine
- Pricing engine
- Payment adapters
- Admin panel

### Phase 5: SEO and Performance
- SEO engine
- JSON-LD generator
- CWV integration
- Cache layer

### Phase 6: Safety and Scale
- Block migrations
- Multi-client config
- Permissions
- Logging and recovery

## 4. Canonical Folder Structure by Phase
### Phase 1
- igp-pro.php
- uninstall.php
- includes/core/loader.php
- includes/core/helpers.php
- includes/cpt/register-cpt.php
- includes/cpt/taxonomies.php
- includes/blocks/registry.php
- includes/blocks/renderer.php
- includes/blocks/resolver.php
- includes/blocks/hero/schema.json
- includes/blocks/hero/render.php
- includes/content/content-graph.php

### Phase 2
- includes/blocks/section-wrapper/
- includes/blocks/destination-cards/
- includes/blocks/tour-cards/
- includes/blocks/featured-listings/
- includes/blocks/cta/
- includes/blocks/trust/
- includes/blocks/pricing-summary/
- includes/blocks/rich-text/
- includes/blocks/itinerary/
- includes/blocks/gallery/
- includes/blocks/faq/
- includes/blocks/map/
- includes/blocks/breadcrumb/
- includes/blocks/icon-list/
- includes/blocks/stats/
- includes/blocks/tabs/
- includes/blocks/accordions/
- includes/blocks/related-tours/
- includes/blocks/related-destinations/

### Phase 3
- includes/admin/content-editor.php
- includes/content/validator.php
- includes/content/sanitizer.php
- includes/content/importer.php
- includes/content/exporter.php
- assets/js/content-editor.js

### Phase 4
- includes/booking/booking-engine.php
- includes/booking/pricing-engine.php
- includes/booking/payment-adapters/
- includes/admin/booking-panel.php

### Phase 5
- includes/seo/seo-engine.php
- includes/seo/schema-generator.php
- includes/performance/cache.php
- includes/performance/cwv.php
- includes/admin/seo-panel.php

### Phase 6
- includes/migration/block-migrations.php
- includes/admin/settings.php
- includes/core/logger.php
- includes/api/rest-routes.php
- storage/logs/
- storage/cache/

## 5. Phase 1 Steps
### Step 1: Plugin Scaffold
Deliverables:
- main plugin file
- constants
- loader
- safe activation

Exit:
- plugin activates without fatal errors

### Step 2: CPT Layer
Deliverables:
- Tours and Destinations CPTs
- taxonomies
- rewrite-friendly slugs

Exit:
- both post types visible and URLs resolve

### Step 3: Block Registry
Deliverables:
- registry functions
- unique block registration
- schema path references

Exit:
- registry returns accurate block list

### Step 4: Central Renderer
Deliverables:
- render_block function
- graceful missing-block fallback
- render path loading

Exit:
- blocks render via one controller

### Step 5: Hero Block
Deliverables:
- schema.json
- render.php
- registry entry

Exit:
- hero block renders with required fields

### Step 6: Content Graph Backbone
Deliverables:
- save/load functions
- validation
- post-meta storage strategy

Exit:
- JSON round-trips without corruption

## 6. Phase 2 Steps
Implement first 6 and supporting blocks:
- Section Wrapper
- Destination Cards
- Tour Cards
- CTA
- Itinerary
- Gallery
Then extend to:
- FAQ
- Trust / Social Proof
- Pricing Summary
- Breadcrumb
- Map
- Icon List
- Stats / Highlights
- Tabs
- Accordions
- Related Tours
- Related Destinations

Each block must define:
- schema
- defaults
- variants
- validation
- render path

## 7. Phase 3 Steps
### Step 7: Field Rendering Engine
- Map schema field types to UI controls
- support recursion for objects and repeaters

### Step 8: Content Editor UI
- page selector
- load
- collapsible sections
- save
- meta description editor

### Step 9: Import / Export
- structured JSON import/export
- version validation

## 8. Phase 4 Steps
### Step 10: Booking Engine Core
- booking state model
- form data capture
- admin storage

### Step 11: Pricing Engine
- base price
- add-ons
- person counts

### Step 12: Payment Adapters
- Razorpay
- Stripe
- PayPal

### Step 13: Booking / Enquiry Panel
- review submissions
- filter by status

## 9. Phase 5 Steps
### Step 14: SEO Engine
- meta generation
- technical SEO checks
- internal linking hints

### Step 15: JSON-LD Generator
- derive schema from page data

### Step 16: CWV Integration
- PageSpeed Insights fetch
- cached results

### Step 17: Cache Layer
- block cache
- page cache
- query cache
- invalidation rules

## 10. Phase 6 Steps
### Step 18: Block Migrations
- version resolver
- explicit migrations
- fallback behavior

### Step 19: Multi-client Config
- branding tokens
- layout defaults
- SEO defaults
- booking rules
- feature flags

### Step 20: Permissions
- admin vs content editor access

### Step 21: Logging / Recovery
- logs
- snapshots
- rollback support

## 11. Forward and Backward Wiring
Every step must state:
- what it enables next
- what it depends on
- what fails if it breaks
- how to trace the failure backward

## 12. Deliverable Rules
Each implementation step should output:
- file list
- file content
- integration points
- tests
- rollback notes
- validation gate mapping

## 13. Source of Truth
This blueprint controls build order, dependencies, and step sequencing. It wins over ad hoc implementation decisions.
