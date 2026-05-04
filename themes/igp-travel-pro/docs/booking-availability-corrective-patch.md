# IGP Travel Pro — Booking Availability Corrective Patch

## Scope
This patch only changes the IGP Travel Pro theme. It does not move booking logic, AJAX, checkout, payment adapters, booking storage, or dashboard records into the theme.

## Issues corrected

1. Detached availability controls were outside the booking `<form>` and lacked `form="{form_id}"` on `tour_option`, `addons[]`, and `addon_qty[id]` controls. Those fields could render visually but were not guaranteed to serialize into the plugin booking request.
2. The theme override used a visual booking card without an availability panel when the plugin rendered `igp-pro/booking/booking-panel.php` directly. The override now includes the availability deck so plugin fallback behaviour remains intact.
3. The availability deck relied on the plugin toggling the panel and a delayed theme class sync. The bridge script now synchronizes the dock state reliably without replacing the plugin's validation or AJAX logic.
4. The add-on and option rows used loose nested spans that were hard to scan/select. The markup now follows the plugin-compatible row grammar: option dot/copy/price and add-on check/copy/price/quantity.

## Preserved plugin contract

The theme preserves the required POST field names:

- action
- nonce
- tour_id
- trip_id
- booking_date
- tour_date
- guest_qty[type]
- maximum_guests
- minimum_guests
- tour_option
- addons[]
- addon_qty[id]
- first_name
- last_name
- email
- phone
- question

The theme preserves required data attributes, including:

- data-igp-booking-panel
- data-igp-booking-form
- data-igp-date-picker
- data-igp-date-toggle
- data-igp-date-label
- data-igp-date-popover
- data-igp-date-calendar
- data-igp-tour-date
- data-igp-traveler-picker
- data-igp-traveler-toggle
- data-igp-traveler-summary
- data-igp-traveler-panel
- data-igp-traveler-apply
- data-igp-guest-wrap
- data-igp-guest-row
- data-igp-quantity
- data-igp-qty-minus
- data-igp-qty-plus
- data-igp-check-availability
- data-igp-availability-panel
- data-igp-tour-option
- data-igp-addons-wrap
- data-igp-addon-row
- data-igp-addon-check
- data-igp-addon-qty
- data-igp-total
- data-igp-form-message
- data-igp-open-enquiry
- data-igp-close-enquiry
- data-igp-enquiry-modal
- data-igp-enquiry-form

## Validation

- PHP lint passed for theme PHP files.
- Required booking fields are present in the theme booking template/render helpers.
- Required booking data attributes are present.
- The patch is additive to the existing UI block variation rendering layer.
