<?php
/**
 * AI Copilot YAML contract data.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/** Return current AI YAML draft contract. */
function igp_ai_copilot_get_yaml_contract(): array {
	$aliases = function_exists( 'igp_ai_copilot_get_block_aliases' ) ? igp_ai_copilot_get_block_aliases() : array();
	return array(
		'contract_version'        => '14.A.1',
		'yaml_version'            => 1,
		'required_fields'         => array( 'version', 'content_type', 'title', 'blocks' ),
		'optional_fields'         => array( 'slug', 'primary_destination', 'audience', 'tone', 'cta_goal', 'seo', 'relationships', 'blocks.*.heading', 'blocks.*.text', 'blocks.*.items', 'blocks.*.cta', 'blocks.*.media' ),
		'supported_content_types' => array( 'tour_page', 'destination_page', 'landing_page', 'blog_support_page', 'industry_template_page' ),
		'supported_value_types'   => array( 'string', 'number', 'boolean', 'array', 'object', 'plain_multiline_text' ),
		'supported_block_aliases' => $aliases,
		'disallowed_features'    => array( 'php_tags', 'script_tags', 'inline_event_handlers', 'dangerous_protocols', 'yaml_anchors', 'yaml_aliases', 'custom_yaml_tags', 'base64_media_blobs', 'binary_payloads', 'executable_file_references', 'arbitrary_html_embeds', 'unsafe_shortcodes' ),
		'valid_example'          => igp_ai_copilot_get_valid_yaml_example(),
		'invalid_example'        => igp_ai_copilot_get_invalid_yaml_example(),
		'error_policy'           => array(
			'invalid_yaml'    => 'return_wp_error_no_fatal',
			'unsafe_content'  => 'reject_before_compile',
			'unknown_blocks'  => 'needs_review_no_silent_drop',
			'write_actions'   => 'validated_compile_then_draft_only_no_publish',
		),
	);
}

/** Valid example YAML from the current contract. */
function igp_ai_copilot_get_valid_yaml_example(): string {
	return <<<'YAML'
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
YAML;
}

/** Invalid example YAML. */
function igp_ai_copilot_get_invalid_yaml_example(): string {
	return <<<'YAML'
version: 1
content_type: tour_page
title: Unsafe Example
blocks:
  - block: hero
    heading: "<script>alert('x')</script>"
    text: "<?php echo 'bad'; ?>"
YAML;
}
