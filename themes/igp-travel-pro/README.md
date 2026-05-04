# IGP Travel Pro Theme

Phase 1 standalone WordPress theme rendering layer for IGP Pro.

## Scope

- Does not modify the IGP Pro plugin.
- Provides a tokenized CSS rendering layer for the 32 uploaded UI Block schemas.
- Maps uploaded HTML variation styles to existing IGP Pro renderer classes:
  - `igp-block--{block}`
  - `igp-variant--{variant}`
  - `igp-density--{density}`
  - `igp-theme--{theme}`
  - `igp-container--{container}`
  - `igp-surface--{surface}`
  - `igp-media--{media_position}`
- Includes an Appearance → IGP Travel Pro Tokens panel for import/export of CSS tokens.
- Uses electric orange and midnight navy defaults.

## Included block map

The generated `assets/data/block-variants.json` contains 32 schema-backed blocks and all declared variants from the provided schema files.

## Installation

1. Upload `igp-travel-pro.zip` under WordPress Appearance → Themes → Add New → Upload Theme.
2. Activate IGP Travel Pro.
3. Keep IGP Pro active for Content Graph and block rendering.
4. Open Appearance → IGP Travel Pro Tokens to import or export token JSON.

## Phase 1 validation

- PHP syntax checked with `php -l`.
- JSON files parsed successfully.
- Theme package contains no plugin edits.
- Frontend CSS is tokenized and scoped to IGP classes where practical.


## Phase 1 field-level clone correction

This package includes `assets/data/block-field-class-map.json` and audit reports under `docs/` proving schema field-to-renderer-class mapping across all 32 IGP Pro UI blocks. The replacement CSS maps actual `igp-pro-*` renderer elements, not just high-level variant wrappers.
