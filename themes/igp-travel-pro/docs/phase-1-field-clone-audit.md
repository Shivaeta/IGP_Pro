# IGP Travel Pro — Phase 1 Field-Level Clone Audit

This audit maps the 32 schema-backed IGP Pro UI blocks to the exact six-file HTML variation reference library and the actual PHP render classes emitted by the plugin.

## Summary

- UI block schemas audited: **32 / 32**
- Schema field paths audited: **620**
- Renderable/semantic field paths: **396**
- Exact or semantic class-mapped fields: **396**
- Parent-class mapped fields: **0**
- Style token/control fields mapped to wrapper classes: **224**
- Query/layout/control-only fields mapped to block root/context: **0**
- Renderer classes missing in previous CSS: **47**
- Renderer classes missing after replacement CSS: **0**

## Status definitions

- `field_class_mapped`: the schema field has a direct renderer selector styled in the replacement CSS.
- `semantic_heading_mapped`: heading fields use the central semantic wrapper selectors plus legacy block title selectors.
- `parent_class_mapped`: the field is rendered inside an item/card/table parent where the renderer does not emit a unique child class.
- `style_class_mapped`: style schema controls map to canonical `igp-variant`, `igp-density`, `igp-theme`, `igp-container`, `igp-surface`, and `igp-media-position` classes.
- `control_or_data_source_mapped`: schema value controls query/layout/visibility and is mapped to the nearest stable block root/context selector.

## Block summary

| Block | Folder | Schema fields | Renderable | Exact/Semantic | Parent mapped | Style controls | Control fields | References |
|---|---:|---:|---:|---:|---:|---:|---:|---|
| `accordions` | `accordions` | 15 | 8 | 8 | 0 | 7 | 0 | 06-interactive-utility.html#accordions:default; 06-interactive-utility.html#accordions:accordion; 06-interactive-utility.html#accordions:grouped; 06-interactive-utility.html#accordions:compact |
| `best_time_to_visit` | `best-time-to-visit` | 23 | 16 | 16 | 0 | 7 | 0 | 03-visual-proof-local-context.html#best-time-to-visit:default; 03-visual-proof-local-context.html#best-time-to-visit:seasons; 03-visual-proof-local-context.html#best-time-to-visit:monthly; 03-visual-proof-local-context.html#best-time-to-visit:compact |
| `breadcrumb` | `breadcrumb` | 12 | 5 | 5 | 0 | 7 | 0 | 05-pricing-location-booking.html#breadcrumb:default; 05-pricing-location-booking.html#breadcrumb:compact |
| `brochure_cta` | `brochure-cta` | 18 | 11 | 11 | 0 | 7 | 0 | 01-foundation-conversion.html#brochure-cta:default; 01-foundation-conversion.html#brochure-cta:banner; 01-foundation-conversion.html#brochure-cta:card; 01-foundation-conversion.html#brochure-cta:inline |
| `cta` | `cta` | 22 | 15 | 15 | 0 | 7 | 0 | 01-foundation-conversion.html#cta:default; 01-foundation-conversion.html#cta:inline; 01-foundation-conversion.html#cta:banner; 01-foundation-conversion.html#cta:split; 01-foundation-conversion.html#cta:card |
| `departure_dates` | `departure-dates` | 23 | 16 | 16 | 0 | 7 | 0 | 04-journey-logistics.html#departure-dates:default; 04-journey-logistics.html#departure-dates:table; 04-journey-logistics.html#departure-dates:cards; 04-journey-logistics.html#departure-dates:compact |
| `destination_cards` | `destination-cards` | 21 | 14 | 14 | 0 | 7 | 0 | 02-listing-card-systems.html#destination-cards:default; 02-listing-card-systems.html#destination-cards:grid; 02-listing-card-systems.html#destination-cards:carousel-safe; 02-listing-card-systems.html#destination-cards:list; 02-listing-card-systems.html#destination-cards:featured |
| `expert_box` | `expert-box` | 22 | 15 | 15 | 0 | 7 | 0 | 03-visual-proof-local-context.html#expert-box:default; 03-visual-proof-local-context.html#expert-box:card; 03-visual-proof-local-context.html#expert-box:profile; 03-visual-proof-local-context.html#expert-box:compact |
| `faq` | `faq` | 15 | 8 | 8 | 0 | 7 | 0 | 06-interactive-utility.html#faq:default; 06-interactive-utility.html#faq:accordion; 06-interactive-utility.html#faq:grouped; 06-interactive-utility.html#faq:compact |
| `featured_listings` | `featured-listings` | 22 | 15 | 15 | 0 | 7 | 0 | 02-listing-card-systems.html#featured-listings:default; 02-listing-card-systems.html#featured-listings:grid; 02-listing-card-systems.html#featured-listings:carousel-safe; 02-listing-card-systems.html#featured-listings:list; 02-listing-card-systems.html#featured-listings:featured |
| `gallery` | `gallery` | 18 | 11 | 11 | 0 | 7 | 0 | 03-visual-proof-local-context.html#gallery:default; 03-visual-proof-local-context.html#gallery:grid; 03-visual-proof-local-context.html#gallery:masonry-safe; 03-visual-proof-local-context.html#gallery:slider-safe |
| `hero` | `hero` | 18 | 11 | 11 | 0 | 7 | 0 | 01-foundation-conversion.html#hero:default; 01-foundation-conversion.html#hero:full-width; 01-foundation-conversion.html#hero:image-left; 01-foundation-conversion.html#hero:image-right; 01-foundation-conversion.html#hero:split-overlay; 01-foundation-conversion.html#hero:centered-minimal |
| `icon_list` | `icon-list` | 17 | 10 | 10 | 0 | 7 | 0 | 06-interactive-utility.html#icon-list:default; 06-interactive-utility.html#icon-list:grid; 06-interactive-utility.html#icon-list:compact; 06-interactive-utility.html#icon-list:cards |
| `inclusions_exclusions` | `inclusions-exclusions` | 20 | 13 | 13 | 0 | 7 | 0 | 04-journey-logistics.html#inclusions-exclusions:default; 04-journey-logistics.html#inclusions-exclusions:two-column; 04-journey-logistics.html#inclusions-exclusions:compact; 04-journey-logistics.html#inclusions-exclusions:cards |
| `itinerary` | `itinerary` | 17 | 10 | 10 | 0 | 7 | 0 | 04-journey-logistics.html#itinerary:default; 04-journey-logistics.html#itinerary:timeline; 04-journey-logistics.html#itinerary:cards; 04-journey-logistics.html#itinerary:compact |
| `map` | `map` | 16 | 9 | 9 | 0 | 7 | 0 | 05-pricing-location-booking.html#map:default; 05-pricing-location-booking.html#map:wide; 05-pricing-location-booking.html#map:card |
| `nearby_attractions` | `nearby-attractions` | 22 | 15 | 15 | 0 | 7 | 0 | 03-visual-proof-local-context.html#nearby-attractions:default; 03-visual-proof-local-context.html#nearby-attractions:grid; 03-visual-proof-local-context.html#nearby-attractions:list; 03-visual-proof-local-context.html#nearby-attractions:compact |
| `package_tiers` | `package-tiers` | 25 | 18 | 18 | 0 | 7 | 0 | 05-pricing-location-booking.html#package-tiers:default; 05-pricing-location-booking.html#package-tiers:comparison; 05-pricing-location-booking.html#package-tiers:cards; 05-pricing-location-booking.html#package-tiers:compact |
| `pricing_summary` | `pricing-summary` | 18 | 11 | 11 | 0 | 7 | 0 | 05-pricing-location-booking.html#pricing-summary:default; 05-pricing-location-booking.html#pricing-summary:compact; 05-pricing-location-booking.html#pricing-summary:featured; 05-pricing-location-booking.html#pricing-summary:comparison |
| `related_destinations` | `related-destinations` | 15 | 8 | 8 | 0 | 7 | 0 | 02-listing-card-systems.html#related-destinations:default; 02-listing-card-systems.html#related-destinations:grid; 02-listing-card-systems.html#related-destinations:carousel-safe; 02-listing-card-systems.html#related-destinations:list; 02-listing-card-systems.html#related-destinations:featured |
| `related_tours` | `related-tours` | 15 | 8 | 8 | 0 | 7 | 0 | 02-listing-card-systems.html#related-tours:default; 02-listing-card-systems.html#related-tours:grid; 02-listing-card-systems.html#related-tours:carousel-safe; 02-listing-card-systems.html#related-tours:list; 02-listing-card-systems.html#related-tours:featured |
| `reviews_summary` | `reviews-summary` | 24 | 17 | 17 | 0 | 7 | 0 | 03-visual-proof-local-context.html#reviews-summary:default; 03-visual-proof-local-context.html#reviews-summary:summary; 03-visual-proof-local-context.html#reviews-summary:cards; 03-visual-proof-local-context.html#reviews-summary:compact |
| `rich_text` | `rich-text` | 15 | 8 | 8 | 0 | 7 | 0 | 01-foundation-conversion.html#rich-text:default; 01-foundation-conversion.html#rich-text:article; 01-foundation-conversion.html#rich-text:lead; 01-foundation-conversion.html#rich-text:panel; 01-foundation-conversion.html#rich-text:quote |
| `route_timeline` | `route-timeline` | 21 | 14 | 14 | 0 | 7 | 0 | 04-journey-logistics.html#route-timeline:default; 04-journey-logistics.html#route-timeline:timeline; 04-journey-logistics.html#route-timeline:cards; 04-journey-logistics.html#route-timeline:compact |
| `section` | `section-wrapper` | 18 | 11 | 11 | 0 | 7 | 0 | 01-foundation-conversion.html#section-wrapper:default; 01-foundation-conversion.html#section-wrapper:band; 01-foundation-conversion.html#section-wrapper:split; 01-foundation-conversion.html#section-wrapper:grid |
| `stats` | `stats` | 17 | 10 | 10 | 0 | 7 | 0 | 06-interactive-utility.html#stats:default; 06-interactive-utility.html#stats:grid; 06-interactive-utility.html#stats:strip; 06-interactive-utility.html#stats:cards |
| `sticky_booking_cta` | `sticky-booking-cta` | 23 | 16 | 16 | 0 | 7 | 0 | 05-pricing-location-booking.html#sticky-booking-cta:default; 05-pricing-location-booking.html#sticky-booking-cta:bottom-bar; 05-pricing-location-booking.html#sticky-booking-cta:side-card; 05-pricing-location-booking.html#sticky-booking-cta:inline |
| `tabs` | `tabs` | 15 | 8 | 8 | 0 | 7 | 0 | 06-interactive-utility.html#tabs:default; 06-interactive-utility.html#tabs:pills; 06-interactive-utility.html#tabs:underline; 06-interactive-utility.html#tabs:boxed |
| `tour_cards` | `tour-cards` | 25 | 18 | 18 | 0 | 7 | 0 | 02-listing-card-systems.html#tour-cards:default; 02-listing-card-systems.html#tour-cards:grid; 02-listing-card-systems.html#tour-cards:carousel-safe; 02-listing-card-systems.html#tour-cards:list; 02-listing-card-systems.html#tour-cards:featured |
| `tour_facts` | `tour-facts` | 18 | 11 | 11 | 0 | 7 | 0 | 04-journey-logistics.html#tour-facts:default; 04-journey-logistics.html#tour-facts:grid; 04-journey-logistics.html#tour-facts:compact; 04-journey-logistics.html#tour-facts:icons |
| `trust` | `trust` | 25 | 18 | 18 | 0 | 7 | 0 | 01-foundation-conversion.html#trust:default; 01-foundation-conversion.html#trust:logo-strip; 01-foundation-conversion.html#trust:testimonial-cards; 01-foundation-conversion.html#trust:stats |
| `visa_requirements` | `visa-requirements` | 25 | 18 | 18 | 0 | 7 | 0 | 04-journey-logistics.html#visa-requirements:default; 04-journey-logistics.html#visa-requirements:checklist; 04-journey-logistics.html#visa-requirements:cards; 04-journey-logistics.html#visa-requirements:compact |

## Renderer class coverage

- Total renderer classes detected: **205**
- Previous CSS class coverage: **158 / 205**
- Replacement CSS class coverage: **205 / 205**

Remaining missing classes: **0**

## Field-level CSVs

- `schema-field-class-audit.csv` — every schema field path, mapped selector, status, and reference ID.
- `block-element-clone-audit.csv` — block-level rollup.
- `render-class-coverage-audit.csv` — all emitted renderer classes and CSS coverage before/after.