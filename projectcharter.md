# IGP Pro Plugin — Project Charter

## 1. Project Identity
IGP Pro is a schema-driven travel website engine for WordPress. It combines tours, destinations, reusable page sections, booking and enquiry workflows, SEO generation, and performance controls into one controlled system.

## 2. Vision
Build a repeatable travel site engine that can be deployed across many client sites with consistent layout quality, structured content, and low maintenance.

## 3. Philosophy
- Structured data over free-form editing
- Blocks as components
- JSON Content Graph as source of truth
- Separation of layout, data, and logic
- Progressive completeness over rushed complexity

## 4. Non-Negotiables
- Gutenberg-compatible blocks with PHP server-side rendering
- Central block registry and renderer
- Versioned block schemas
- Content validation before save
- Modular folder structure
- No business logic in the main plugin file
- Booking logic separated from presentation
- SEO derived from structured content
- Cache and recovery support

## 5. Scope
### In scope
- Tours CPT
- Destinations CPT
- Taxonomies
- Block system
- Content graph
- Content editor
- Booking and enquiry panel
- Payment adapter layer
- SEO engine
- CWV integration
- Starter content
- Itinerary builder
- Media optimization
- Multi-client configuration
- Permissions
- Logging and migration

### Out of scope for initial release
- Marketplace distribution
- Multilingual support
- Heavy AI automation inside the plugin
- Complex inventory management unless added later

## 6. Core Modules
1. CPT Layer
2. Block System
3. Content Graph Engine
4. Content Editor
5. Booking / Enquiry Engine
6. SEO Engine
7. CWV Engine
8. Starter Content Engine
9. Media Optimization Layer
10. Multi-client Config Layer
11. Permissions / Roles
12. Logging / Recovery / Migration

## 7. Block System Rules
Every block must define:
- block ID
- version
- category
- data source
- fields
- defaults
- validation rules
- variants
- render callback

Blocks must be schema-driven and render through the central renderer.

## 8. Block Library
Initial blocks:
- Hero
- Section Wrapper
- Destination Cards
- Tour Cards
- Featured Listings
- CTA
- Trust / Social Proof
- Pricing Summary
- Rich Text
- Itinerary
- Gallery
- FAQ
- Map
- Breadcrumb
- Icon List
- Stats / Highlights
- Tabs
- Accordions
- Related Tours
- Related Destinations

## 9. Content Graph Rules
- Stored as structured JSON
- Versioned
- Validated before save
- Round-trip safe
- Reproducible from import/export

## 10. Booking Rules
- Booking states must be explicit
- Pricing must be separated from UI
- Payment must use adapter layer
- Booking and enquiry must be inspectable in admin

## 11. Performance Rules
- Cache block output where possible
- Cache external API responses
- Avoid repeated queries
- Keep frontend output lean

## 12. Version Control Rules
- One change set at a time
- Tagged milestones
- Reversible commits
- No untracked architecture drift

## 13. Source of Truth
This charter defines scope, architecture, constraints, and module definitions. If implementation conflicts with this document, the charter wins.
