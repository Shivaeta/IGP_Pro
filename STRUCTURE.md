# Repository Structure

Last updated: 2026-05-01  
Source: prepared control-file package for `Shivaeta/IGP_Pro`  
Status: repository must still be inspected after these files are installed.

---

## Current Control Files in This Package

```text
.
├── PROJECT_CHARTER.md
├── BLUEPRINT.md
├── GATES.md
├── current_status.json
└── STRUCTURE.md
```

---

## Current Functional Implementation State

```text
No plugin business logic implemented yet.
No theme rendering implemented yet.
No booking handler implemented yet.
No CPT registration implemented yet.
No registry implemented yet.
No schema implemented yet.
No import/export implemented yet.
No payment functionality implemented.
```

---

## Target Structure After Future Phases

This is the intended structure, not a claim that the files currently exist.

```text
.
├── PROJECT_CHARTER.md
├── BLUEPRINT.md
├── GATES.md
├── current_status.json
├── STRUCTURE.md
├── docs/
│   ├── benchmarks/
│   │   ├── homepage-benchmark.txt
│   │   ├── tour-page-benchmark.txt
│   │   ├── destination-page-benchmark.txt
│   │   ├── seo-cwv-benchmark.txt
│   │   └── editor-content-benchmark.txt
│   └── phase-audit-checklists/
├── tools/
│   ├── check-static.sh
│   ├── check-registry.php
│   └── check-booking-json.php
├── plugins/
│   └── igp-pro/
│       ├── igp-pro.php
│       ├── uninstall.php
│       ├── includes/
│       │   ├── core/
│       │   │   ├── module-registry.php
│       │   │   ├── diagnostics.php
│       │   │   ├── capabilities.php
│       │   │   └── helpers.php
│       │   ├── cpt/
│       │   │   └── register-cpts.php
│       │   ├── taxonomies/
│       │   │   └── register-taxonomies.php
│       │   ├── booking/
│       │   │   ├── booking-records.php
│       │   │   ├── booking-handler.php
│       │   │   ├── booking-calculator.php
│       │   │   └── booking-admin.php
│       │   ├── registry/
│       │   │   ├── section-registry.php
│       │   │   ├── core-field-registry.php
│       │   │   ├── design-token-registry.php
│       │   │   └── json-schema-registry.php
│       │   ├── acf/
│       │   │   ├── acf-loader.php
│       │   │   └── acf-field-groups.php
│       │   ├── admin/
│       │   │   ├── admin-menu.php
│       │   │   ├── dashboard.php
│       │   │   ├── diagnostics-screen.php
│       │   │   ├── editor-schema.php
│       │   │   ├── editor-renderers.php
│       │   │   └── editor-save.php
│       │   ├── import-export/
│       │   │   ├── exporter.php
│       │   │   └── importer.php
│       │   └── schema/
│       │       ├── schema-source-map.php
│       │       └── schema-renderer.php
│       └── assets/
│           ├── admin/
│           │   ├── igp-admin.css
│           │   ├── igp-editor.css
│           │   └── igp-editor.js
│           └── js/
│               ├── booking.js
│               └── booking-flow.js
└── themes/
    └── igp-travel-pro/
        ├── style.css
        ├── functions.php
        ├── header.php
        ├── footer.php
        ├── index.php
        ├── front-page.php
        ├── single-igp_tour.php
        ├── taxonomy-igp_destination.php
        ├── inc/
        │   ├── setup.php
        │   ├── assets.php
        │   ├── render-sections.php
        │   └── template-tags.php
        ├── template-parts/
        │   └── sections/
        │       ├── hero-search.php
        │       ├── hero-media.php
        │       ├── split-image-text.php
        │       ├── feature-cards.php
        │       ├── stats-bar.php
        │       ├── destination-grid.php
        │       ├── tour-grid.php
        │       ├── tour-categories.php
        │       ├── trust-badges.php
        │       ├── ota-review-summary.php
        │       ├── testimonial-cards.php
        │       ├── faq-accordion.php
        │       ├── gallery-grid.php
        │       ├── cta-banner.php
        │       ├── contact-section.php
        │       ├── map-section.php
        │       ├── legal-content.php
        │       ├── blog-grid.php
        │       ├── comparison-table.php
        │       ├── process-steps.php
        │       ├── pricing-cards.php
        │       ├── booking-enquiry.php
        │       ├── availability-selector.php
        │       ├── participant-selector.php
        │       ├── addon-selector.php
        │       ├── itinerary-timeline.php
        │       ├── important-info.php
        │       ├── includes-excludes.php
        │       ├── operator-profile.php
        │       ├── meeting-point-map.php
        │       ├── tour-facts-bar.php
        │       ├── related-tours.php
        │       ├── tour-reviews.php
        │       ├── tour-faq.php
        │       ├── destination-overview.php
        │       ├── best-time-to-visit.php
        │       ├── how-to-reach.php
        │       ├── top-attractions.php
        │       ├── things-to-do.php
        │       ├── popular-tours-in-destination.php
        │       ├── local-tips.php
        │       ├── destination-map.php
        │       ├── destination-reviews.php
        │       ├── destination-faq.php
        │       └── related-destinations.php
        └── assets/
            ├── css/
            │   └── main.css
            ├── js/
            │   ├── main.js
            │   └── booking-flow.js
            └── img/
```

---

## Update Rule

After every approved subphase, regenerate this file from the actual repository structure.

Recommended command:

```bash
find . -maxdepth 6 -type f | sort
```

Do not update this file from memory.
