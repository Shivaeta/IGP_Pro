# IGP Travel Pro — Booking Freeze, Add-ons, Checkout Patch

## Root cause 1: page scroll freeze

The IGP Pro booking JavaScript opens the availability UI by adding `igp-availability-panel-open` to the `<html>` element. The plugin CSS uses that class for drawer/modal behavior and sets the page overflow to hidden. In IGP Travel Pro, the availability panel is an inline left-column deck, not a drawer. The result was a locked page until the deck was closed.

### Fix

The theme now treats the tour availability deck as inline UI:

- `assets/js/tour-layout.js` removes `igp-availability-panel-open` when the active panel belongs to `.igp-theme-tour-layout`.
- CSS also guards the inline tour layout against modal/drawer positioning.
- Plugin fallback drawer behavior remains available outside the IGP Travel Pro tour layout.

## Root cause 2: tour options/add-ons missing or poorly formatted

The uploaded plugin context does not include the newer `igp_pro_get_booking_form_contract()` function, although the theme was expecting it. The previous theme therefore fell back to an empty local contract, which only created a minimal `standard` option and no add-ons. The deck did not receive the plugin's configured `_igp_booking_options` / `_igp_booking_addons` data.

### Fix

`igp_travel_pro_get_booking_contract()` now uses this order:

1. `igp_pro_get_booking_form_contract()` when available.
2. `igp_pro_get_tour_booking_config()` as compatibility fallback.
3. Theme-safe empty fallback only if neither plugin function exists.

This preserves plugin ownership of booking logic while allowing the theme template to receive real options, add-ons, guest rows, prices, and currency.

The deck CSS now forces a reliable option/add-on grid, prevents one-word-per-line wrapping, hides zero-price option price cells, and stacks responsively.

## Root cause 3: checkout showed `u20b9`

Some booking/checkout display paths can receive currency as escaped text (`u20b9` or `\\u20b9`) instead of the rupee glyph. The plugin owns checkout rendering, so the theme now normalizes only the frontend display output and theme money formatting.

### Fix

- `igp_travel_pro_normalize_currency_symbol()` normalizes `u20b9`, `\\u20b9`, `&#8377;`, and `&#x20b9;` to `₹`.
- Checkout output buffer normalizes public checkout/confirmation pages.
- Theme JS also normalizes visible money text after booking UI updates.
- Checkout CSS now provides a production-grade layout for plugin checkout pages.

## Validation

- PHP lint: passed.
- Theme JavaScript syntax check: passed.
- CSS brace check: passed.
- ZIP integrity: passed.
