# IGP Pro Plugin — Block System Specification (V1: First 6 Blocks)

This document defines the first 6 production-grade blocks with full schema, field definitions, validation rules, and rendering logic.

---

# 1. HERO BLOCK (hero_v1)

## Purpose
Primary above-the-fold section with optional search capability.

## Schema
{
  "id": "hero",
  "version": "v1",
  "category": "core",
  "data_source": "manual",
  "fields": {
    "heading": {"type": "string", "required": true},
    "subheading": {"type": "string"},
    "background_image": {"type": "image", "required": true},
    "cta": {
      "type": "object",
      "fields": {
        "label": {"type": "string"},
        "url": {"type": "string"}
      }
    },
    "enable_search": {"type": "boolean", "default": false}
  },
  "variants": ["full", "split", "overlay"]
}

## Validation
- heading required
- background_image required

## Render Logic (PHP)
render_hero($data)

---

# 2. SECTION WRAPPER (section_v1)

## Purpose
Controls layout, spacing, and grouping.

## Schema
{
  "id": "section",
  "version": "v1",
  "category": "layout",
  "fields": {
    "title": {"type": "string"},
    "layout": {"type": "enum", "values": ["grid","list","carousel"], "default": "grid"},
    "columns": {"type": "number", "min": 1, "max": 4},
    "spacing": {"type": "enum", "values": ["compact","normal","relaxed"]},
    "background": {"type": "string"}
  }
}

## Render Logic
render_section_wrapper($data, $children)

---

# 3. DESTINATION CARDS (destination_cards_v1)

## Purpose
Display destinations (manual or auto)

## Schema
{
  "id": "destination_cards",
  "version": "v1",
  "category": "discovery",
  "data_source": "hybrid",
  "fields": {
    "source": {"type": "enum", "values": ["manual","query"], "default": "query"},
    "items": {"type": "relationship", "post_type": "destination"},
    "layout": {"type": "enum", "values": ["grid","slider"]},
    "limit": {"type": "number", "default": 6}
  }
}

## Validation
- min_items: 1

## Render Logic
render_destination_cards($data)

---

# 4. TOUR CARDS (tour_cards_v1)

## Purpose
Core revenue block displaying tours

## Schema
{
  "id": "tour_cards",
  "version": "v1",
  "category": "discovery",
  "data_source": "query",
  "fields": {
    "destination": {"type": "relationship", "post_type": "destination"},
    "layout": {"type": "enum", "values": ["grid","slider","list"]},
    "limit": {"type": "number", "default": 6},
    "show_price": {"type": "boolean", "default": true},
    "show_rating": {"type": "boolean", "default": true}
  }
}

## Render Logic
render_tour_cards($data)

---

# 5. ITINERARY BLOCK (itinerary_v1)

## Purpose
Structured day-wise itinerary

## Schema
{
  "id": "itinerary",
  "version": "v1",
  "category": "content",
  "fields": {
    "days": {
      "type": "repeater",
      "fields": {
        "day_title": {"type": "string"},
        "description": {"type": "text"},
        "meals": {"type": "string"},
        "stay": {"type": "string"}
      }
    }
  }
}

## Validation
- at least 1 day required

## Render Logic
render_itinerary($data)

---

# 6. CTA BLOCK (cta_v1)

## Purpose
Conversion-focused call to action

## Schema
{
  "id": "cta",
  "version": "v1",
  "category": "conversion",
  "fields": {
    "heading": {"type": "string"},
    "subheading": {"type": "string"},
    "button": {
      "type": "object",
      "fields": {
        "label": {"type": "string"},
        "url": {"type": "string"}
      }
    },
    "alignment": {"type": "enum", "values": ["center","left","right"]}
  }
}

## Render Logic
render_cta($data)

---

# FINAL NOTES

- All blocks must pass schema validation before save
- All rendering must be SSR (PHP)
- All data flows through Content Graph JSON
- No direct HTML editing allowed

