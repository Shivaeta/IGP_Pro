# IGP Travel Pro — Exact Clone Root-Cause Audit and Fix Report

## Result

| Audit area | Count |
|---|---:|
| UI block schemas audited | 32 |
| Schema variants audited | 134 |
| Schema field paths inspected | 620 |
| Renderer classes detected from PHP render files | 213 |
| Renderer classes directly styled, bridged, or explicitly bound | 213 / 213 |
| Explicit schema variant wrapper bindings in replacement CSS | 134 / 134 |

## Exact culprit found

The previous package failed visually for three concrete reasons:

1. **The rendered graph was trapped inside `.igp-page-shell`.**  
   That wrapper constrained all IGP blocks to a narrow page shell, so full-width heroes, wide galleries, split panels, and booking bars could not match the HTML reference scale.

2. **The theme assumed `igp-block--*` and `igp-variant--*` wrappers existed.**  
   IGP Pro only emits those semantic/style wrappers when `enable_semantic_outline` and `enable_smart_block_variants` are enabled. On sites where those flags were stored as `false`, the browser received only inner `igp-pro-*` markup, so most variation selectors never matched.

3. **The reference classes were not bound to actual renderable elements.**  
   The HTML reference styles target classes such as `hero-bg`, `hero-split`, `listing-card`, `gallery-item`, `day-card`, `price-card`, `faq-item`, `tab-btn`, and `stat-card`. The plugin renders classes such as `igp-pro-hero`, `igp-pro-card`, `igp-pro-gallery__item`, `igp-pro-itinerary__day`, `igp-pro-package-tier`, and `igp-pro-tabs__tab`. CSS was cloned, but the DOM did not carry the exact reference vocabulary.

4. **There was a class-name mismatch for several actual wrapper IDs.**  
   Example: the registry emits `igp-block--section`, not `igp-block--section-wrapper`; the plugin emits `igp-media--left/right`, not `igp-media-position--left/right`. The replacement binds to the actual renderer contract.

## Fix applied

| Fix | File |
|---|---|
| Force required rendering flags while the theme is active: semantic outline, smart block variants, brand engine | `inc/clone-adapter.php` |
| Remove the graph-width cage for IGP Content Graph pages | `page.php`, `single.php`, `singular.php`, `front-page.php`, `home.php`, `index.php`, `archive.php`, `template-parts/content.php` |
| Import the exact CSS from the supplied HTML UI Block reference library | `assets/css/igp-travel-pro.css` |
| Add clone bridge selectors for real `igp-pro-*` render classes | `assets/css/igp-travel-pro.css` |
| Inject reference vocabulary classes into renderable elements after the central renderer outputs HTML | `inc/clone-adapter.php`, `inc/template-tags.php` |
| Add explicit 134/134 schema variant bindings for auditability | `assets/css/igp-travel-pro.css` |
| Add explicit 213/213 renderer-class bindings for field-level auditability | `assets/css/igp-travel-pro.css` |

## What changed from the last ZIP

The last ZIP mainly had static CSS selectors. This replacement fixes the rendering path itself:

```text
IGP Pro Content Graph
→ central IGP renderer
→ semantic/style wrappers forced on
→ theme clone adapter maps igp-pro-* elements to reference classes
→ exact reference CSS applies
→ no .igp-page-shell width cage
```

## Audit output files

- `docs/exact-clone-root-cause-audit.csv`
- `docs/exact-clone-root-cause-audit.md`
- `docs/renderer-class-bridge-audit.csv`

## Remaining note

This package has passed static code/CSS/selector audits. I could not run a LocalWP browser screenshot diff in this environment, so final visual acceptance should still be checked in a live WordPress page with IGP Pro active.
