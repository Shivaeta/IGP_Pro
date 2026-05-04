# IGP Travel Pro — Tour Layout / Booking Template Implementation Report

## Scope

This pass adds theme modules without changing the UI Block variation clone renderer.

Implemented:

- Production header shell with fallback navigation.
- Production footer with placeholder link columns.
- Typography alignment to the reference library (`Inter` stack, loaded via Google Fonts and enforced on theme/rendering containers).
- Dedicated `single-tour.php` layout.
- Theme-owned visual booking form template at `igp-pro/booking/booking-panel.php`.
- Integrated tour layout that places gallery/title above the content+booking grid.
- Booking card in the right rail and availability dock in the left content column after Quick Tour Facts.

## Booking Ownership Boundary

The theme only renders visual markup from the plugin contract.

Preserved plugin-owned responsibilities:

- Booking Ajax actions.
- Availability/checkout processing.
- Payment adapter handoff.
- Booking/enquiry storage.
- Dashboard records.
- Pricing calculation logic in JS/plugin runtime.

## Preserved POST Names

- `action`
- `nonce`
- `tour_id`
- `trip_id`
- `booking_date`
- `tour_date`
- `guest_qty[type]`
- `maximum_guests`
- `minimum_guests`
- `tour_option`
- `addons[]`
- `addon_qty[id]`
- `first_name`
- `last_name`
- `email`
- `phone`
- `question`

## Preserved Ajax Action Values

- `igp_pro_submit_booking`
- `igp_pro_submit_enquiry`

The checkout action value `igp_pro_complete_checkout` is not moved into the theme because checkout remains plugin-owned.

## Preserved Critical Data Attributes

All requested booking/date/traveler/addon/enquiry data attributes are present in the theme visual template or integrated tour layout.

## Files Added / Updated

- `single-tour.php`
- `inc/tour-layout.php`
- `igp-pro/booking/booking-panel.php`
- `assets/js/tour-layout.js`
- `header.php`
- `footer.php`
- `functions.php`
- `assets/css/igp-travel-pro.css`
- `style.css`

## Validation

- PHP syntax lint: passed.
- JSON parse validation: passed.
- CSS brace validation: passed.
- ZIP integrity: passed.
