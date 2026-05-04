# IGP Travel Pro Theme Renderer Root-Cause Fix Report

## Culprit fixed

The prior theme relied on the IGP Pro plugin renderer and attempted to post-process plugin-owned HTML. That cannot produce a 1:1 clone because the plugin DOM structure differs from the supplied HTML reference library. The fixed theme now reads the IGP Content Graph and renders theme-owned reference HTML directly.

## Static audit summary

- Schema-backed UI blocks audited: 32 / 32
- Blocks with theme-owned renderer cases: 32 / 32
- Dynamic variant source: `data.style.variant` from the IGP Pro Content Editor
- Field source: `section.data` from the IGP Pro Content Graph
- Fallback path: if the theme exact renderer returns empty output, the old plugin renderer remains available as fallback.

## What changed

- Added `inc/graph-renderer.php`.
- Updated `inc/template-tags.php` so frontend graph pages use the theme exact renderer first.
- Kept IGP Pro as the editor/content graph source of truth.
- Rendered exact reference grammar classes such as `hero-bg`, `hero-split`, `listing-card`, `gallery-grid`, `timeline`, `price-card`, `faq-item`, `tab-btn`, `icon-card`, and `stat-card` from graph fields.
- Preserved dynamic variant switching from the IGP Pro Content Editor: change `style.variant`, reload frontend, and the renderer chooses the matching reference layout.

## Block coverage

| Block | Variants | Renderer case |
|---|---:|---|
| `accordions` | 4 | yes |
| `best-time-to-visit` | 4 | yes |
| `breadcrumb` | 2 | yes |
| `brochure-cta` | 4 | yes |
| `cta` | 5 | yes |
| `departure-dates` | 4 | yes |
| `destination-cards` | 5 | yes |
| `expert-box` | 4 | yes |
| `faq` | 4 | yes |
| `featured-listings` | 5 | yes |
| `gallery` | 4 | yes |
| `hero` | 6 | yes |
| `icon-list` | 4 | yes |
| `inclusions-exclusions` | 4 | yes |
| `itinerary` | 4 | yes |
| `map` | 3 | yes |
| `nearby-attractions` | 4 | yes |
| `package-tiers` | 4 | yes |
| `pricing-summary` | 4 | yes |
| `related-destinations` | 5 | yes |
| `related-tours` | 5 | yes |
| `reviews-summary` | 4 | yes |
| `rich-text` | 5 | yes |
| `route-timeline` | 4 | yes |
| `section-wrapper` | 4 | yes |
| `stats` | 4 | yes |
| `sticky-booking-cta` | 4 | yes |
| `tabs` | 4 | yes |
| `tour-cards` | 5 | yes |
| `tour-facts` | 4 | yes |
| `trust` | 4 | yes |
| `visa-requirements` | 4 | yes |
