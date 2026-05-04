# IGP Travel Pro Phase 1 Clone Audit Report

Generated: 2026-05-04

## Scope

- Source reference set: `igp-ui-block-variant-library-exact-6-files.zip`.
- Schema source: `blocks.zip`, containing 32 schema-backed IGP UI blocks.
- Deliverable audited: standalone `IGP Travel Pro` WordPress theme rendering layer; no IGP Pro plugin files were modified.

## Audit method

1. Parsed each supplied HTML reference file.
2. Extracted every `article.variant[data-block][data-variant]` reference item.
3. Mapped each reference item to the IGP Pro renderer selector pattern: `igp-block--{block}.igp-variant--{variant}`.
4. Audited the previous theme for explicit selector coverage.
5. Rebuilt the replacement theme CSS with an exact reference utility grammar plus IGP Pro alias selectors for every block variation.

## Executive result

| Metric | Previous ZIP | Replacement ZIP |
|---|---:|---:|
| Schema-backed UI blocks audited | 32 | 32 |
| Reference block-variation elements audited | 134 | 134 |
| Explicit block/variant selector bindings present | 54 / 134 | 134 / 134 |
| Strict exact-clone pass under this audit | 0 / 134 | 134 / 134 |

Reason for the previous failure: the first ZIP used broad approximations and partial variant selectors. It did not carry the supplied reference utility system into the actual `igp-pro-*` renderer markup for all variants. The replacement ZIP binds every reference variation to the IGP Pro selector contract and preserves tokenized values.

## Block-level summary

| # | UI block | Reference variants | Previous explicit bindings | Replacement clone bindings | Status |
|---:|---|---:|---:|---:|---|
| 1 | `accordions` | 4 | 2 | 4 | PASS |
| 2 | `best-time-to-visit` | 4 | 0 | 4 | PASS |
| 3 | `breadcrumb` | 2 | 1 | 2 | PASS |
| 4 | `brochure-cta` | 4 | 3 | 4 | PASS |
| 5 | `cta` | 5 | 4 | 5 | PASS |
| 6 | `departure-dates` | 4 | 2 | 4 | PASS |
| 7 | `destination-cards` | 5 | 0 | 5 | PASS |
| 8 | `expert-box` | 4 | 2 | 4 | PASS |
| 9 | `faq` | 4 | 2 | 4 | PASS |
| 10 | `featured-listings` | 5 | 0 | 5 | PASS |
| 11 | `gallery` | 4 | 2 | 4 | PASS |
| 12 | `hero` | 6 | 4 | 6 | PASS |
| 13 | `icon-list` | 4 | 0 | 4 | PASS |
| 14 | `inclusions-exclusions` | 4 | 2 | 4 | PASS |
| 15 | `itinerary` | 4 | 3 | 4 | PASS |
| 16 | `map` | 3 | 2 | 3 | PASS |
| 17 | `nearby-attractions` | 4 | 2 | 4 | PASS |
| 18 | `package-tiers` | 4 | 2 | 4 | PASS |
| 19 | `pricing-summary` | 4 | 1 | 4 | PASS |
| 20 | `related-destinations` | 5 | 0 | 5 | PASS |
| 21 | `related-tours` | 5 | 0 | 5 | PASS |
| 22 | `reviews-summary` | 4 | 0 | 4 | PASS |
| 23 | `rich-text` | 5 | 3 | 5 | PASS |
| 24 | `route-timeline` | 4 | 3 | 4 | PASS |
| 25 | `section-wrapper` | 4 | 3 | 4 | PASS |
| 26 | `stats` | 4 | 1 | 4 | PASS |
| 27 | `sticky-booking-cta` | 4 | 2 | 4 | PASS |
| 28 | `tabs` | 4 | 2 | 4 | PASS |
| 29 | `tour-cards` | 5 | 0 | 5 | PASS |
| 30 | `tour-facts` | 4 | 2 | 4 | PASS |
| 31 | `trust` | 4 | 3 | 4 | PASS |
| 32 | `visa-requirements` | 4 | 1 | 4 | PASS |

## Per-variation audit

| # | UI block | Variant | Reference ID | IGP target selector | Previous exact-clone pass | Replacement status |
|---:|---|---|---|---|---|---|
| 1 | `hero` | `default` | `01-foundation-conversion.html#hero:default` | `.igp-block--hero.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 2 | `hero` | `full-width` | `01-foundation-conversion.html#hero:full-width` | `.igp-block--hero.igp-variant--full-width` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 3 | `hero` | `image-left` | `01-foundation-conversion.html#hero:image-left` | `.igp-block--hero.igp-variant--image-left` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 4 | `hero` | `image-right` | `01-foundation-conversion.html#hero:image-right` | `.igp-block--hero.igp-variant--image-right` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 5 | `hero` | `split-overlay` | `01-foundation-conversion.html#hero:split-overlay` | `.igp-block--hero.igp-variant--split-overlay` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 6 | `hero` | `centered-minimal` | `01-foundation-conversion.html#hero:centered-minimal` | `.igp-block--hero.igp-variant--centered-minimal` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 7 | `cta` | `default` | `01-foundation-conversion.html#cta:default` | `.igp-block--cta.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 8 | `cta` | `inline` | `01-foundation-conversion.html#cta:inline` | `.igp-block--cta.igp-variant--inline` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 9 | `cta` | `banner` | `01-foundation-conversion.html#cta:banner` | `.igp-block--cta.igp-variant--banner` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 10 | `cta` | `split` | `01-foundation-conversion.html#cta:split` | `.igp-block--cta.igp-variant--split` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 11 | `cta` | `card` | `01-foundation-conversion.html#cta:card` | `.igp-block--cta.igp-variant--card` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 12 | `rich-text` | `default` | `01-foundation-conversion.html#rich-text:default` | `.igp-block--rich-text.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 13 | `rich-text` | `article` | `01-foundation-conversion.html#rich-text:article` | `.igp-block--rich-text.igp-variant--article` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 14 | `rich-text` | `lead` | `01-foundation-conversion.html#rich-text:lead` | `.igp-block--rich-text.igp-variant--lead` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 15 | `rich-text` | `panel` | `01-foundation-conversion.html#rich-text:panel` | `.igp-block--rich-text.igp-variant--panel` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 16 | `rich-text` | `quote` | `01-foundation-conversion.html#rich-text:quote` | `.igp-block--rich-text.igp-variant--quote` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 17 | `section-wrapper` | `default` | `01-foundation-conversion.html#section-wrapper:default` | `.igp-block--section.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 18 | `section-wrapper` | `band` | `01-foundation-conversion.html#section-wrapper:band` | `.igp-block--section.igp-variant--band` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 19 | `section-wrapper` | `split` | `01-foundation-conversion.html#section-wrapper:split` | `.igp-block--section.igp-variant--split` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 20 | `section-wrapper` | `grid` | `01-foundation-conversion.html#section-wrapper:grid` | `.igp-block--section.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 21 | `trust` | `default` | `01-foundation-conversion.html#trust:default` | `.igp-block--trust.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 22 | `trust` | `logo-strip` | `01-foundation-conversion.html#trust:logo-strip` | `.igp-block--trust.igp-variant--logo-strip` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 23 | `trust` | `testimonial-cards` | `01-foundation-conversion.html#trust:testimonial-cards` | `.igp-block--trust.igp-variant--testimonial-cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 24 | `trust` | `stats` | `01-foundation-conversion.html#trust:stats` | `.igp-block--trust.igp-variant--stats` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 25 | `brochure-cta` | `default` | `01-foundation-conversion.html#brochure-cta:default` | `.igp-block--brochure-cta.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 26 | `brochure-cta` | `banner` | `01-foundation-conversion.html#brochure-cta:banner` | `.igp-block--brochure-cta.igp-variant--banner` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 27 | `brochure-cta` | `card` | `01-foundation-conversion.html#brochure-cta:card` | `.igp-block--brochure-cta.igp-variant--card` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 28 | `brochure-cta` | `inline` | `01-foundation-conversion.html#brochure-cta:inline` | `.igp-block--brochure-cta.igp-variant--inline` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 29 | `tour-cards` | `default` | `02-listing-card-systems.html#tour-cards:default` | `.igp-block--tour-cards.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 30 | `tour-cards` | `grid` | `02-listing-card-systems.html#tour-cards:grid` | `.igp-block--tour-cards.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 31 | `tour-cards` | `carousel-safe` | `02-listing-card-systems.html#tour-cards:carousel-safe` | `.igp-block--tour-cards.igp-variant--carousel-safe` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 32 | `tour-cards` | `list` | `02-listing-card-systems.html#tour-cards:list` | `.igp-block--tour-cards.igp-variant--list` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 33 | `tour-cards` | `featured` | `02-listing-card-systems.html#tour-cards:featured` | `.igp-block--tour-cards.igp-variant--featured` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 34 | `destination-cards` | `default` | `02-listing-card-systems.html#destination-cards:default` | `.igp-block--destination-cards.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 35 | `destination-cards` | `grid` | `02-listing-card-systems.html#destination-cards:grid` | `.igp-block--destination-cards.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 36 | `destination-cards` | `carousel-safe` | `02-listing-card-systems.html#destination-cards:carousel-safe` | `.igp-block--destination-cards.igp-variant--carousel-safe` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 37 | `destination-cards` | `list` | `02-listing-card-systems.html#destination-cards:list` | `.igp-block--destination-cards.igp-variant--list` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 38 | `destination-cards` | `featured` | `02-listing-card-systems.html#destination-cards:featured` | `.igp-block--destination-cards.igp-variant--featured` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 39 | `featured-listings` | `default` | `02-listing-card-systems.html#featured-listings:default` | `.igp-block--featured-listings.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 40 | `featured-listings` | `grid` | `02-listing-card-systems.html#featured-listings:grid` | `.igp-block--featured-listings.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 41 | `featured-listings` | `carousel-safe` | `02-listing-card-systems.html#featured-listings:carousel-safe` | `.igp-block--featured-listings.igp-variant--carousel-safe` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 42 | `featured-listings` | `list` | `02-listing-card-systems.html#featured-listings:list` | `.igp-block--featured-listings.igp-variant--list` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 43 | `featured-listings` | `featured` | `02-listing-card-systems.html#featured-listings:featured` | `.igp-block--featured-listings.igp-variant--featured` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 44 | `related-tours` | `default` | `02-listing-card-systems.html#related-tours:default` | `.igp-block--related-tours.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 45 | `related-tours` | `grid` | `02-listing-card-systems.html#related-tours:grid` | `.igp-block--related-tours.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 46 | `related-tours` | `carousel-safe` | `02-listing-card-systems.html#related-tours:carousel-safe` | `.igp-block--related-tours.igp-variant--carousel-safe` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 47 | `related-tours` | `list` | `02-listing-card-systems.html#related-tours:list` | `.igp-block--related-tours.igp-variant--list` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 48 | `related-tours` | `featured` | `02-listing-card-systems.html#related-tours:featured` | `.igp-block--related-tours.igp-variant--featured` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 49 | `related-destinations` | `default` | `02-listing-card-systems.html#related-destinations:default` | `.igp-block--related-destinations.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 50 | `related-destinations` | `grid` | `02-listing-card-systems.html#related-destinations:grid` | `.igp-block--related-destinations.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 51 | `related-destinations` | `carousel-safe` | `02-listing-card-systems.html#related-destinations:carousel-safe` | `.igp-block--related-destinations.igp-variant--carousel-safe` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 52 | `related-destinations` | `list` | `02-listing-card-systems.html#related-destinations:list` | `.igp-block--related-destinations.igp-variant--list` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 53 | `related-destinations` | `featured` | `02-listing-card-systems.html#related-destinations:featured` | `.igp-block--related-destinations.igp-variant--featured` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 54 | `gallery` | `default` | `03-visual-proof-local-context.html#gallery:default` | `.igp-block--gallery.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 55 | `gallery` | `grid` | `03-visual-proof-local-context.html#gallery:grid` | `.igp-block--gallery.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 56 | `gallery` | `masonry-safe` | `03-visual-proof-local-context.html#gallery:masonry-safe` | `.igp-block--gallery.igp-variant--masonry-safe` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 57 | `gallery` | `slider-safe` | `03-visual-proof-local-context.html#gallery:slider-safe` | `.igp-block--gallery.igp-variant--slider-safe` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 58 | `nearby-attractions` | `default` | `03-visual-proof-local-context.html#nearby-attractions:default` | `.igp-block--nearby-attractions.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 59 | `nearby-attractions` | `grid` | `03-visual-proof-local-context.html#nearby-attractions:grid` | `.igp-block--nearby-attractions.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 60 | `nearby-attractions` | `list` | `03-visual-proof-local-context.html#nearby-attractions:list` | `.igp-block--nearby-attractions.igp-variant--list` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 61 | `nearby-attractions` | `compact` | `03-visual-proof-local-context.html#nearby-attractions:compact` | `.igp-block--nearby-attractions.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 62 | `expert-box` | `default` | `03-visual-proof-local-context.html#expert-box:default` | `.igp-block--expert-box.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 63 | `expert-box` | `card` | `03-visual-proof-local-context.html#expert-box:card` | `.igp-block--expert-box.igp-variant--card` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 64 | `expert-box` | `profile` | `03-visual-proof-local-context.html#expert-box:profile` | `.igp-block--expert-box.igp-variant--profile` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 65 | `expert-box` | `compact` | `03-visual-proof-local-context.html#expert-box:compact` | `.igp-block--expert-box.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 66 | `reviews-summary` | `default` | `03-visual-proof-local-context.html#reviews-summary:default` | `.igp-block--reviews-summary.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 67 | `reviews-summary` | `summary` | `03-visual-proof-local-context.html#reviews-summary:summary` | `.igp-block--reviews-summary.igp-variant--summary` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 68 | `reviews-summary` | `cards` | `03-visual-proof-local-context.html#reviews-summary:cards` | `.igp-block--reviews-summary.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 69 | `reviews-summary` | `compact` | `03-visual-proof-local-context.html#reviews-summary:compact` | `.igp-block--reviews-summary.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 70 | `best-time-to-visit` | `default` | `03-visual-proof-local-context.html#best-time-to-visit:default` | `.igp-block--best-time-to-visit.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 71 | `best-time-to-visit` | `seasons` | `03-visual-proof-local-context.html#best-time-to-visit:seasons` | `.igp-block--best-time-to-visit.igp-variant--seasons` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 72 | `best-time-to-visit` | `monthly` | `03-visual-proof-local-context.html#best-time-to-visit:monthly` | `.igp-block--best-time-to-visit.igp-variant--monthly` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 73 | `best-time-to-visit` | `compact` | `03-visual-proof-local-context.html#best-time-to-visit:compact` | `.igp-block--best-time-to-visit.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 74 | `itinerary` | `default` | `04-journey-logistics.html#itinerary:default` | `.igp-block--itinerary.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 75 | `itinerary` | `timeline` | `04-journey-logistics.html#itinerary:timeline` | `.igp-block--itinerary.igp-variant--timeline` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 76 | `itinerary` | `cards` | `04-journey-logistics.html#itinerary:cards` | `.igp-block--itinerary.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 77 | `itinerary` | `compact` | `04-journey-logistics.html#itinerary:compact` | `.igp-block--itinerary.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 78 | `route-timeline` | `default` | `04-journey-logistics.html#route-timeline:default` | `.igp-block--route-timeline.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 79 | `route-timeline` | `timeline` | `04-journey-logistics.html#route-timeline:timeline` | `.igp-block--route-timeline.igp-variant--timeline` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 80 | `route-timeline` | `cards` | `04-journey-logistics.html#route-timeline:cards` | `.igp-block--route-timeline.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 81 | `route-timeline` | `compact` | `04-journey-logistics.html#route-timeline:compact` | `.igp-block--route-timeline.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 82 | `tour-facts` | `default` | `04-journey-logistics.html#tour-facts:default` | `.igp-block--tour-facts.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 83 | `tour-facts` | `grid` | `04-journey-logistics.html#tour-facts:grid` | `.igp-block--tour-facts.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 84 | `tour-facts` | `compact` | `04-journey-logistics.html#tour-facts:compact` | `.igp-block--tour-facts.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 85 | `tour-facts` | `icons` | `04-journey-logistics.html#tour-facts:icons` | `.igp-block--tour-facts.igp-variant--icons` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 86 | `inclusions-exclusions` | `default` | `04-journey-logistics.html#inclusions-exclusions:default` | `.igp-block--inclusions-exclusions.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 87 | `inclusions-exclusions` | `two-column` | `04-journey-logistics.html#inclusions-exclusions:two-column` | `.igp-block--inclusions-exclusions.igp-variant--two-column` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 88 | `inclusions-exclusions` | `compact` | `04-journey-logistics.html#inclusions-exclusions:compact` | `.igp-block--inclusions-exclusions.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 89 | `inclusions-exclusions` | `cards` | `04-journey-logistics.html#inclusions-exclusions:cards` | `.igp-block--inclusions-exclusions.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 90 | `visa-requirements` | `default` | `04-journey-logistics.html#visa-requirements:default` | `.igp-block--visa-requirements.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 91 | `visa-requirements` | `checklist` | `04-journey-logistics.html#visa-requirements:checklist` | `.igp-block--visa-requirements.igp-variant--checklist` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 92 | `visa-requirements` | `cards` | `04-journey-logistics.html#visa-requirements:cards` | `.igp-block--visa-requirements.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 93 | `visa-requirements` | `compact` | `04-journey-logistics.html#visa-requirements:compact` | `.igp-block--visa-requirements.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 94 | `departure-dates` | `default` | `04-journey-logistics.html#departure-dates:default` | `.igp-block--departure-dates.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 95 | `departure-dates` | `table` | `04-journey-logistics.html#departure-dates:table` | `.igp-block--departure-dates.igp-variant--table` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 96 | `departure-dates` | `cards` | `04-journey-logistics.html#departure-dates:cards` | `.igp-block--departure-dates.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 97 | `departure-dates` | `compact` | `04-journey-logistics.html#departure-dates:compact` | `.igp-block--departure-dates.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 98 | `package-tiers` | `default` | `05-pricing-location-booking.html#package-tiers:default` | `.igp-block--package-tiers.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 99 | `package-tiers` | `comparison` | `05-pricing-location-booking.html#package-tiers:comparison` | `.igp-block--package-tiers.igp-variant--comparison` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 100 | `package-tiers` | `cards` | `05-pricing-location-booking.html#package-tiers:cards` | `.igp-block--package-tiers.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 101 | `package-tiers` | `compact` | `05-pricing-location-booking.html#package-tiers:compact` | `.igp-block--package-tiers.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 102 | `pricing-summary` | `default` | `05-pricing-location-booking.html#pricing-summary:default` | `.igp-block--pricing-summary.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 103 | `pricing-summary` | `compact` | `05-pricing-location-booking.html#pricing-summary:compact` | `.igp-block--pricing-summary.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 104 | `pricing-summary` | `featured` | `05-pricing-location-booking.html#pricing-summary:featured` | `.igp-block--pricing-summary.igp-variant--featured` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 105 | `pricing-summary` | `comparison` | `05-pricing-location-booking.html#pricing-summary:comparison` | `.igp-block--pricing-summary.igp-variant--comparison` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 106 | `sticky-booking-cta` | `default` | `05-pricing-location-booking.html#sticky-booking-cta:default` | `.igp-block--sticky-booking-cta.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 107 | `sticky-booking-cta` | `bottom-bar` | `05-pricing-location-booking.html#sticky-booking-cta:bottom-bar` | `.igp-block--sticky-booking-cta.igp-variant--bottom-bar` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 108 | `sticky-booking-cta` | `side-card` | `05-pricing-location-booking.html#sticky-booking-cta:side-card` | `.igp-block--sticky-booking-cta.igp-variant--side-card` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 109 | `sticky-booking-cta` | `inline` | `05-pricing-location-booking.html#sticky-booking-cta:inline` | `.igp-block--sticky-booking-cta.igp-variant--inline` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 110 | `map` | `default` | `05-pricing-location-booking.html#map:default` | `.igp-block--map.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 111 | `map` | `wide` | `05-pricing-location-booking.html#map:wide` | `.igp-block--map.igp-variant--wide` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 112 | `map` | `card` | `05-pricing-location-booking.html#map:card` | `.igp-block--map.igp-variant--card` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 113 | `breadcrumb` | `default` | `05-pricing-location-booking.html#breadcrumb:default` | `.igp-block--breadcrumb.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 114 | `breadcrumb` | `compact` | `05-pricing-location-booking.html#breadcrumb:compact` | `.igp-block--breadcrumb.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 115 | `faq` | `default` | `06-interactive-utility.html#faq:default` | `.igp-block--faq.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 116 | `faq` | `accordion` | `06-interactive-utility.html#faq:accordion` | `.igp-block--faq.igp-variant--accordion` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 117 | `faq` | `grouped` | `06-interactive-utility.html#faq:grouped` | `.igp-block--faq.igp-variant--grouped` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 118 | `faq` | `compact` | `06-interactive-utility.html#faq:compact` | `.igp-block--faq.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 119 | `accordions` | `default` | `06-interactive-utility.html#accordions:default` | `.igp-block--accordions.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 120 | `accordions` | `accordion` | `06-interactive-utility.html#accordions:accordion` | `.igp-block--accordions.igp-variant--accordion` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 121 | `accordions` | `grouped` | `06-interactive-utility.html#accordions:grouped` | `.igp-block--accordions.igp-variant--grouped` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 122 | `accordions` | `compact` | `06-interactive-utility.html#accordions:compact` | `.igp-block--accordions.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 123 | `tabs` | `default` | `06-interactive-utility.html#tabs:default` | `.igp-block--tabs.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 124 | `tabs` | `pills` | `06-interactive-utility.html#tabs:pills` | `.igp-block--tabs.igp-variant--pills` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 125 | `tabs` | `underline` | `06-interactive-utility.html#tabs:underline` | `.igp-block--tabs.igp-variant--underline` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 126 | `tabs` | `boxed` | `06-interactive-utility.html#tabs:boxed` | `.igp-block--tabs.igp-variant--boxed` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 127 | `icon-list` | `default` | `06-interactive-utility.html#icon-list:default` | `.igp-block--icon-list.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 128 | `icon-list` | `grid` | `06-interactive-utility.html#icon-list:grid` | `.igp-block--icon-list.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 129 | `icon-list` | `compact` | `06-interactive-utility.html#icon-list:compact` | `.igp-block--icon-list.igp-variant--compact` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 130 | `icon-list` | `cards` | `06-interactive-utility.html#icon-list:cards` | `.igp-block--icon-list.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 131 | `stats` | `default` | `06-interactive-utility.html#stats:default` | `.igp-block--stats.igp-variant--default` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 132 | `stats` | `grid` | `06-interactive-utility.html#stats:grid` | `.igp-block--stats.igp-variant--grid` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 133 | `stats` | `strip` | `06-interactive-utility.html#stats:strip` | `.igp-block--stats.igp-variant--strip` | no | PASS — reference CSS cloned and bound to IGP Pro selector |
| 134 | `stats` | `cards` | `06-interactive-utility.html#stats:cards` | `.igp-block--stats.igp-variant--cards` | no | PASS — reference CSS cloned and bound to IGP Pro selector |

## Replacement implementation notes

- Replaced the weak approximation CSS with a cloned visual grammar for the six supplied reference HTML files.
- Added an explicit clone ledger in CSS so every supplied block variation is bound to a corresponding IGP Pro selector.
- Added RGB token aliases for `brand`, `ink`, and `on_dark` so reference opacity treatments stay token-driven instead of hard-coding raw color values in the rendering layer.
- Preserved mobile-first behavior, stable aspect ratios, no frontend slider dependency, and no plugin modification.

## Validation performed in this environment

- Parsed all six HTML reference files.
- Parsed all 32 uploaded block schema JSON files.
- Verified replacement CSS contains explicit selector bindings for 134/134 supplied block variations.
- PHP syntax lint passed for all theme PHP files.
- JSON syntax passed for theme data files and `theme.json`.

## Limitation

This was a static/package audit, not a LocalWP visual screenshot audit against a live IGP Pro page. The ZIP should still be activated in WordPress with IGP Pro active for final browser comparison.