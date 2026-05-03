# IGP AI Copilot YAML Contract

This document defines the AI-facing YAML draft format for Phase 14.A. YAML is an intake format only. AI must not generate, save, or publish internal Content Graph JSON directly. IGP Pro parses YAML, normalizes it, validates it, maps block aliases to registered blocks, compiles it into Content Graph JSON, validates the compiled graph, renders a preview through the central renderer, and saves only as a draft through controlled services.

## Required top-level fields

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `version` | number | Yes | Must be `1`. AI cannot control internal Content Graph versions. |
| `content_type` | string | Yes | One of the supported content types below. |
| `title` | string | Yes | Plain text page/post title. |
| `blocks` | array | Yes | Non-empty ordered list of block objects. Each block requires `block`. |

## Optional top-level fields

| Field | Type | Notes |
| --- | --- | --- |
| `slug` | string | Optional draft slug suggestion. Sanitized by WordPress. |
| `primary_destination` | string | Relationship hint only; no post ID is inferred. |
| `audience` | string | Audience hint. |
| `tone` | string | Tone hint. |
| `cta_goal` | string | Normalized CTA intent such as `enquiry`, `booking`, `contact`, `download`, `quote`, `call`, `whatsapp`, `learn_more`. |
| `seo` | object | Plain text SEO suggestions only. |
| `relationships` | object | Optional plain text/list hints. |

## Supported content types

- `tour_page`
- `destination_page`
- `landing_page`
- `blog_support_page`
- `industry_template_page`

## Supported value types

- strings
- numbers
- booleans
- arrays
- objects
- plain multiline text

## Supported block aliases

| AI alias | Registered IGP block |
| --- | --- |
| `hero` | `hero` |
| `tour_facts`, `quick_info`, `facts` | `tour_facts` |
| `overview`, `rich_text`, `content` | `rich_text` |
| `itinerary`, `days` | `itinerary` |
| `faq`, `faqs`, `questions` | `faq` |
| `inclusions`, `exclusions`, `inclusions_exclusions` | `inclusions_exclusions` |
| `pricing`, `package_tiers`, `price_comparison` | `package_tiers` |
| `gallery` | `gallery` |
| `cta`, `call_to_action` | `cta` |
| `trust`, `social_proof` | `trust` |
| `reviews`, `reviews_summary` | `reviews_summary` |
| `route`, `route_timeline` | `route_timeline` |
| `best_time`, `best_time_to_visit` | `best_time_to_visit` |
| `expert`, `expert_box` | `expert_box` |

Unknown blocks are never silently discarded. They are marked as review-required and cannot be saved as draft until mapped or removed by a human.

## Supported block fields

Common fields:

```yaml
block: hero
heading: Plain heading text
text: Plain descriptive text
eyebrow: Optional small heading label
cta:
  label: Send Enquiry
  intent: enquiry
  url: /contact/
media:
  prompt: Image generation or sourcing brief
  alt: Accessible alt text suggestion
  url: Optional reviewed image URL
```

Block-specific examples:

```yaml
- block: itinerary
  heading: Day-wise Itinerary
  items:
    - day_title: Day 1 — Arrival
      description: Pickup, check-in, and evening orientation.
      meals: Dinner
      stay: Varanasi

- block: faq
  heading: Frequently Asked Questions
  items:
    - question: Is this senior-friendly?
      answer: Yes, pacing and transfers can be planned for senior travellers.

- block: inclusions
  inclusions:
    - item: Hotel accommodation
      note: Twin sharing basis
  exclusions:
    - item: Personal expenses

- block: pricing
  tiers:
    - name: Comfort
      price: "₹24,999"
      duration: 5 days
      features:
        - Hotel stay
        - Airport transfers
```

## Disallowed features

The parser and validator reject:

- PHP tags
- `<script>` content
- inline event handlers such as `onclick=`
- dangerous protocols such as `javascript:`, `vbscript:`, `data:`, `file:`, `phar:`
- YAML anchors, aliases, and merge keys
- custom YAML tags
- executable file references
- base64 media blobs
- binary payloads
- arbitrary HTML embeds
- unsafe shortcodes
- AI-provided WordPress attachment IDs as trusted media
- direct publish instructions
- direct Content Graph JSON
- SQL, PHP, or JavaScript code

## Valid example

```yaml
version: 1
content_type: tour_page
title: 5-Day Varanasi Pilgrimage Tour
slug: 5-day-varanasi-pilgrimage-tour
primary_destination: Varanasi
audience: Senior Indian families
tone: devotional, practical, trustworthy
cta_goal: enquiry
seo:
  primary_keyword: Varanasi pilgrimage tour
  secondary_keywords:
    - Kashi Vishwanath tour
    - Ganga Aarti Varanasi package
  meta_title: 5-Day Varanasi Pilgrimage Tour
  meta_description: Senior-friendly Varanasi pilgrimage package with Kashi Vishwanath darshan, Ganga Aarti, Sarnath, hotels, transfers, and guided support.
blocks:
  - block: hero
    heading: 5-Day Varanasi Pilgrimage Tour
    text: A comfortable spiritual journey covering Kashi Vishwanath Temple, Ganga Aarti, Sarnath, and guided local support.
    cta:
      label: Send Enquiry
      intent: enquiry
    media:
      prompt: Senior couple watching Ganga Aarti in Varanasi with warm evening light
      alt: Pilgrims attending Ganga Aarti in Varanasi
  - block: itinerary
    heading: Day-wise Itinerary
    items:
      - day_title: Day 1 — Arrival in Varanasi
        description: Airport pickup, hotel check-in, and evening Ganga Aarti.
      - day_title: Day 2 — Kashi Vishwanath Darshan
        description: Assisted temple visit, old-city walk, and local food stops.
  - block: faq
    heading: Frequently Asked Questions
    items:
      - question: Is this tour suitable for senior travellers?
        answer: Yes. Transfers, hotel selection, and pacing are planned for comfort.
```

## Invalid examples

Malformed YAML:

```yaml
version: 1
content_type: tour_page
title: Bad YAML
blocks:
  - block hero
    heading: Missing colon
```

Unsafe content:

```yaml
version: 1
content_type: tour_page
title: Unsafe Example
blocks:
  - block: hero
    heading: "<script>alert('x')</script>"
    text: "<?php echo 'bad'; ?>"
```

Unknown block requiring review:

```yaml
version: 1
content_type: tour_page
title: Unknown Block Test
blocks:
  - block: spiritual_experiences
    heading: Spiritual Experiences
    text: Attend rituals, temple visits, and guided cultural moments.
```

## Error handling expectations

- Empty or malformed YAML returns `WP_Error`; it must not fatal.
- Unsafe content returns `WP_Error`; it must not be normalized, compiled, previewed, or saved.
- Unknown blocks return structured mapping status and validation errors; they are not discarded.
- Valid but low-quality content may return warnings without failing, such as a FAQ block with fewer than five questions.
- Preview never saves post meta, publishes content, or alters existing pages.
- Save as Draft is the only Phase 14.A write action. It requires permissions, nonce validation, draft validation, compiled graph validation, and the existing Content Graph save path.
