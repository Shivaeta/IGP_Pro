<?php
/**
 * AI Copilot block alias registry.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return AI-facing aliases mapped to canonical registry IDs used by this plugin.
 *
 * @return array<string,string>
 */
function igp_ai_copilot_get_block_aliases(): array {
	return array(
		'hero'             => 'hero',
		'tour_facts'       => 'tour_facts',
		'tour-facts'       => 'tour_facts',
		'quick_info'       => 'tour_facts',
		'quick-info'       => 'tour_facts',
		'facts'            => 'tour_facts',
		'overview'         => 'rich_text',
		'rich_text'        => 'rich_text',
		'rich-text'        => 'rich_text',
		'content'          => 'rich_text',
		'faq'              => 'faq',
		'faqs'             => 'faq',
		'questions'        => 'faq',
		'itinerary'        => 'itinerary',
		'days'             => 'itinerary',
		'inclusions'       => 'inclusions_exclusions',
		'exclusions'       => 'inclusions_exclusions',
		'inclusions_exclusions' => 'inclusions_exclusions',
		'inclusions-exclusions' => 'inclusions_exclusions',
		'pricing'          => 'package_tiers',
		'package_tiers'    => 'package_tiers',
		'package-tiers'    => 'package_tiers',
		'price_comparison' => 'package_tiers',
		'price-comparison' => 'package_tiers',
		'gallery'          => 'gallery',
		'cta'              => 'cta',
		'call_to_action'   => 'cta',
		'call-to-action'   => 'cta',
		'trust'            => 'trust',
		'social_proof'     => 'trust',
		'reviews'          => 'reviews_summary',
		'reviews_summary'  => 'reviews_summary',
		'reviews-summary'  => 'reviews_summary',
		'route'            => 'route_timeline',
		'route_timeline'   => 'route_timeline',
		'route-timeline'   => 'route_timeline',
		'best_time'        => 'best_time_to_visit',
		'best-time'        => 'best_time_to_visit',
		'best_time_to_visit' => 'best_time_to_visit',
		'best-time-to-visit' => 'best_time_to_visit',
		'expert'           => 'expert_box',
		'expert_box'       => 'expert_box',
		'expert-box'       => 'expert_box',
	);
}
