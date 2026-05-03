<?php
/**
 * AI Copilot content mapper.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

class IGP_AI_Copilot_Content_Mapper {
	/** Map one normalized AI block into a registered Content Graph section data payload. */
	public static function map_block( array $ai_block, array $context ): array|WP_Error {
		$mapping = class_exists( 'IGP_AI_Copilot_Block_Map' ) ? IGP_AI_Copilot_Block_Map::resolve_block( (string) ( $ai_block['block'] ?? '' ) ) : array( 'status' => 'unknown' );
		if ( 'mapped' !== ( $mapping['status'] ?? '' ) || empty( $mapping['block_id'] ) ) {
			return new WP_Error( 'igp_ai_block_unmapped', __( 'AI block requires a registered mapping before compilation.', 'igp-pro' ) );
		}

		$block_id = sanitize_key( (string) $mapping['block_id'] );
		$block = function_exists( 'igp_pro_get_registered_block' ) ? igp_pro_get_registered_block( $block_id ) : null;
		if ( ! $block ) {
			return new WP_Error( 'igp_ai_block_not_registered', __( 'Mapped block is not registered in this plugin.', 'igp-pro' ) );
		}
		$schema = function_exists( 'igp_pro_get_block_schema' ) ? igp_pro_get_block_schema( $block ) : new WP_Error( 'igp_ai_schema_unavailable', __( 'Block schema service is unavailable.', 'igp-pro' ) );
		if ( is_wp_error( $schema ) ) { return $schema; }

		$data = isset( $schema['defaults'] ) && is_array( $schema['defaults'] ) ? $schema['defaults'] : array();
		$fields = isset( $schema['fields'] ) && is_array( $schema['fields'] ) ? $schema['fields'] : array();
		$warnings = array();

		$data = self::map_common_fields( $block_id, $ai_block, $data, $fields, $context );
		$data = self::map_specific_fields( $block_id, $ai_block, $data, $fields, $context, $warnings );
		$data = self::filter_to_schema( $data, $fields );

		if ( function_exists( 'igp_pro_resolve_block_data' ) ) {
			$data = igp_pro_resolve_block_data( $block, $data, $context );
		}
		$validation = function_exists( 'igp_pro_validate_block_data' ) ? igp_pro_validate_block_data( $block, $data ) : true;
		if ( is_wp_error( $validation ) ) { return $validation; }

		return array( 'block_id' => $block_id, 'data' => $data, 'warnings' => $warnings );
	}

	private static function map_common_fields( string $block_id, array $ai_block, array $data, array $fields, array $context ): array {
		if ( isset( $fields['heading'] ) ) {
			$heading = self::first_string( $ai_block, array( 'heading', 'title', 'name' ) );
			if ( '' === $heading ) { $heading = self::default_heading( $block_id, $data, $context ); }
			$data['heading'] = array_merge(
				array( 'text' => '', 'level' => 'h2', 'eyebrow' => '', 'visible' => true ),
				isset( $data['heading'] ) && is_array( $data['heading'] ) ? $data['heading'] : array(),
				array( 'text' => $heading, 'level' => 'h2' )
			);
			$eyebrow = self::first_string( $ai_block, array( 'eyebrow' ) );
			if ( '' !== $eyebrow ) { $data['heading']['eyebrow'] = $eyebrow; }
		}

		$text = self::first_string( $ai_block, array( 'text', 'content', 'description', 'intro', 'body', 'summary' ) );
		if ( '' !== $text ) {
			foreach ( array( 'content', 'intro', 'subheading', 'summary', 'description', 'bio' ) as $field_name ) {
				if ( isset( $fields[ $field_name ] ) && ( ! isset( $data[ $field_name ] ) || '' === (string) $data[ $field_name ] ) ) {
					$data[ $field_name ] = $text;
					break;
				}
			}
		}

		$intent = self::first_string( $ai_block, array( 'cta.intent', 'intent' ) );
		$label = self::first_string( $ai_block, array( 'cta.label', 'label', 'button.label', 'cta_label' ) );
		$url = self::safe_url( self::first_string( $ai_block, array( 'cta.url', 'url', 'button.url', 'cta_url' ) ) );
		if ( '' === $label ) { $label = self::cta_label( $intent, (string) ( $context['cta_goal'] ?? '' ) ); }

		if ( isset( $fields['cta'] ) && ( '' !== $label || '' !== $url ) ) {
			$data['cta'] = array_merge( isset( $data['cta'] ) && is_array( $data['cta'] ) ? $data['cta'] : array(), array_filter( array( 'label' => $label, 'url' => $url ), static fn( $v ) => '' !== $v ) );
		}
		if ( isset( $fields['button'] ) && ( '' !== $label || '' !== $url ) ) {
			$data['button'] = array_merge( isset( $data['button'] ) && is_array( $data['button'] ) ? $data['button'] : array(), array_filter( array( 'label' => $label, 'url' => $url ), static fn( $v ) => '' !== $v ) );
		}
		if ( isset( $fields['cta_label'] ) && '' !== $label ) { $data['cta_label'] = $label; }
		if ( isset( $fields['cta_url'] ) && '' !== $url ) { $data['cta_url'] = $url; }
		return $data;
	}

	private static function map_specific_fields( string $block_id, array $ai_block, array $data, array $fields, array $context, array &$warnings ): array {
		switch ( $block_id ) {
			case 'hero':
				$media = isset( $ai_block['media'] ) && is_array( $ai_block['media'] ) ? $ai_block['media'] : array();
				$url = self::safe_url( self::first_string( $media, array( 'url', 'image_url' ) ) );
				if ( '' === $url ) {
					$url = self::placeholder_url();
					$warnings[] = self::issue( 'igp_ai_media_placeholder_used', __( 'Hero media prompt was preserved as pending media; placeholder used for safe preview.', 'igp-pro' ), 'media' );
				}
				$data['background_image'] = array( 'url' => $url, 'alt' => self::first_string( $media, array( 'alt', 'alt_text' ) ) );
				break;
			case 'rich_text':
				$text = self::first_string( $ai_block, array( 'text', 'content', 'description', 'body' ) );
				if ( '' !== $text ) { $data['content'] = $text; }
				break;
			case 'tour_facts':
				$items = self::normalize_label_value_items( self::first_list( $ai_block, array( 'facts', 'items', 'quick_info' ) ), 'label', 'value' );
				if ( ! empty( $items ) ) { $data['facts'] = $items; }
				break;
			case 'itinerary':
				$days = self::normalize_itinerary( self::first_list( $ai_block, array( 'items', 'days' ) ) );
				if ( ! empty( $days ) ) { $data['days'] = $days; }
				break;
			case 'faq':
				$faqs = self::normalize_faq( self::first_list( $ai_block, array( 'items', 'questions' ) ) );
				if ( ! empty( $faqs ) ) { $data['items'] = $faqs; }
				break;
			case 'inclusions_exclusions':
				$inc = self::normalize_item_note( self::first_list( $ai_block, array( 'inclusions', 'included', 'items' ) ) );
				$exc = self::normalize_item_note( self::first_list( $ai_block, array( 'exclusions', 'excluded' ) ) );
				if ( ! empty( $inc ) ) { $data['inclusions'] = $inc; }
				if ( ! empty( $exc ) ) { $data['exclusions'] = $exc; }
				break;
			case 'package_tiers':
				$tiers = self::normalize_package_tiers( self::first_list( $ai_block, array( 'tiers', 'packages', 'items' ) ) );
				if ( ! empty( $tiers ) ) { $data['tiers'] = $tiers; }
				break;
			case 'gallery':
				$images = self::normalize_gallery( self::first_list( $ai_block, array( 'images', 'media', 'items' ) ) );
				if ( ! empty( $images ) ) { $data['images'] = $images; }
				break;
			case 'trust':
				$items = self::normalize_label_text( self::first_list( $ai_block, array( 'items', 'badges', 'proof' ) ) );
				if ( ! empty( $items ) ) { $data['items'] = $items; }
				break;
			case 'reviews_summary':
				$testimonials = self::normalize_testimonials( self::first_list( $ai_block, array( 'testimonials', 'reviews', 'items' ) ) );
				if ( ! empty( $testimonials ) ) { $data['testimonials'] = $testimonials; }
				break;
			case 'route_timeline':
				$stops = self::normalize_route( self::first_list( $ai_block, array( 'stops', 'route', 'items' ) ) );
				if ( ! empty( $stops ) ) { $data['stops'] = $stops; }
				break;
			case 'best_time_to_visit':
				$seasons = self::normalize_seasons( self::first_list( $ai_block, array( 'seasons', 'items' ) ) );
				if ( ! empty( $seasons ) ) { $data['seasons'] = $seasons; }
				if ( '' !== self::first_string( $ai_block, array( 'best_months', 'months' ) ) ) { $data['best_months'] = self::first_string( $ai_block, array( 'best_months', 'months' ) ); }
				break;
			case 'expert_box':
				foreach ( array( 'name', 'role', 'phone', 'email', 'bio' ) as $field ) {
					$value = self::first_string( $ai_block, array( $field ) );
					if ( '' !== $value && isset( $fields[ $field ] ) ) { $data[ $field ] = $value; }
				}
				break;
		}
		return $data;
	}

	private static function filter_to_schema( array $data, array $fields ): array {
		$out = array();
		foreach ( $fields as $name => $field_schema ) {
			if ( ! array_key_exists( $name, $data ) ) { continue; }
			if ( is_array( $field_schema ) && 'object' === ( $field_schema['type'] ?? '' ) && isset( $field_schema['fields'] ) && is_array( $field_schema['fields'] ) && is_array( $data[ $name ] ) ) {
				$out[ $name ] = self::filter_to_schema( $data[ $name ], $field_schema['fields'] );
			} else {
				$out[ $name ] = $data[ $name ];
			}
		}
		return $out;
	}

	private static function first_string( array $source, array $paths ): string { foreach ( $paths as $path ) { $v = self::read_path( $source, $path ); if ( is_scalar( $v ) && '' !== trim( (string) $v ) ) { return trim( (string) $v ); } } return ''; }
	private static function first_list( array $source, array $paths ): array { foreach ( $paths as $path ) { $v = self::read_path( $source, $path ); if ( is_array( $v ) ) { return $v; } if ( is_string( $v ) && '' !== trim( $v ) ) { return preg_split( '/\r\n|\r|\n|,/', $v ) ?: array(); } } return array(); }
	private static function read_path( array $source, string $path ) { $v = $source; foreach ( explode( '.', $path ) as $part ) { if ( is_array( $v ) && array_key_exists( $part, $v ) ) { $v = $v[ $part ]; } else { return null; } } return $v; }
	private static function default_heading( string $block_id, array $data, array $context ): string { if ( 'hero' === $block_id && ! empty( $context['title'] ) ) { return (string) $context['title']; } return (string) ( $data['heading']['text'] ?? ucwords( str_replace( '_', ' ', $block_id ) ) ); }
	private static function cta_label( string $intent, string $fallback ): string { $intent = sanitize_key( $intent ?: $fallback ); $map = array( 'enquiry' => __( 'Send Enquiry', 'igp-pro' ), 'booking' => __( 'Book Now', 'igp-pro' ), 'contact' => __( 'Contact Us', 'igp-pro' ), 'download' => __( 'Download Brochure', 'igp-pro' ), 'quote' => __( 'Request Quote', 'igp-pro' ), 'call' => __( 'Call Now', 'igp-pro' ), 'whatsapp' => __( 'Chat on WhatsApp', 'igp-pro' ), 'learn_more' => __( 'Learn More', 'igp-pro' ) ); return $map[ $intent ] ?? __( 'Send Enquiry', 'igp-pro' ); }
	private static function placeholder_url(): string { return function_exists( 'igp_pro_url' ) ? igp_pro_url( 'assets/images/ai-media-placeholder.svg' ) : ''; }
	private static function safe_url( string $url ): string { return function_exists( 'esc_url_raw' ) ? esc_url_raw( $url ) : $url; }
	private static function issue( string $code, string $message, string $field ): array { return array( 'code' => sanitize_key( $code ), 'message' => $message, 'field' => $field ); }

	private static function normalize_label_value_items( array $items, string $label_key, string $value_key ): array { $out = array(); foreach ( $items as $item ) { if ( is_array( $item ) ) { $label = self::first_string( $item, array( 'label', 'name', 'title', 'key' ) ); $value = self::first_string( $item, array( 'value', 'text', 'description' ) ); } else { $parts = explode( ':', (string) $item, 2 ); $label = trim( $parts[0] ?? '' ); $value = trim( $parts[1] ?? (string) $item ); } if ( '' !== $label || '' !== $value ) { $out[] = array( $label_key => $label ?: __( 'Fact', 'igp-pro' ), $value_key => $value, 'icon' => '', 'note' => '' ); } } return $out; }
	private static function normalize_itinerary( array $items ): array { $out = array(); foreach ( $items as $i => $item ) { if ( is_array( $item ) ) { $out[] = array( 'day_title' => self::first_string( $item, array( 'day_title', 'title', 'day' ) ) ?: sprintf( __( 'Day %d', 'igp-pro' ), $i + 1 ), 'description' => self::first_string( $item, array( 'description', 'text', 'details' ) ), 'meals' => self::first_string( $item, array( 'meals' ) ), 'stay' => self::first_string( $item, array( 'stay', 'hotel' ) ) ); } elseif ( '' !== trim( (string) $item ) ) { $out[] = array( 'day_title' => sprintf( __( 'Day %d', 'igp-pro' ), $i + 1 ), 'description' => trim( (string) $item ), 'meals' => '', 'stay' => '' ); } } return $out; }
	private static function normalize_faq( array $items ): array { $out = array(); foreach ( $items as $item ) { if ( is_array( $item ) ) { $q = self::first_string( $item, array( 'question', 'q', 'title' ) ); $a = self::first_string( $item, array( 'answer', 'a', 'text', 'description' ) ); if ( '' !== $q || '' !== $a ) { $out[] = array( 'question' => $q, 'answer' => $a ); } } } return $out; }
	private static function normalize_item_note( array $items ): array { $out = array(); foreach ( $items as $item ) { if ( is_array( $item ) ) { $text = self::first_string( $item, array( 'item', 'label', 'text', 'title' ) ); $note = self::first_string( $item, array( 'note', 'description' ) ); } else { $text = trim( (string) $item ); $note = ''; } if ( '' !== $text ) { $out[] = array( 'item' => $text, 'note' => $note ); } } return $out; }
	private static function normalize_item_list( array $items ): array { $out = array(); foreach ( $items as $item ) { $text = is_array( $item ) ? self::first_string( $item, array( 'item', 'label', 'text', 'title' ) ) : trim( (string) $item ); if ( '' !== $text ) { $out[] = array( 'item' => $text ); } } return $out; }
	private static function normalize_package_tiers( array $items ): array { $out = array(); foreach ( $items as $item ) { if ( ! is_array( $item ) ) { continue; } $out[] = array( 'name' => self::first_string( $item, array( 'name', 'title' ) ) ?: __( 'Package', 'igp-pro' ), 'price' => self::first_string( $item, array( 'price' ) ), 'currency' => self::first_string( $item, array( 'currency' ) ) ?: '₹', 'duration' => self::first_string( $item, array( 'duration' ) ), 'description' => self::first_string( $item, array( 'description', 'text' ) ), 'features' => self::normalize_item_list( self::first_list( $item, array( 'features', 'items', 'includes' ) ) ), 'highlight' => ! empty( $item['highlight'] ), 'cta_label' => self::first_string( $item, array( 'cta_label', 'cta.label' ) ) ?: __( 'Enquire now', 'igp-pro' ), 'cta_url' => self::safe_url( self::first_string( $item, array( 'cta_url', 'cta.url' ) ) ) ); } return $out; }
	private static function normalize_gallery( array $items ): array { $out = array(); foreach ( $items as $item ) { $url = is_array( $item ) ? self::safe_url( self::first_string( $item, array( 'url', 'image_url' ) ) ) : self::safe_url( trim( (string) $item ) ); if ( '' !== $url ) { $out[] = array( 'url' => $url, 'alt' => is_array( $item ) ? self::first_string( $item, array( 'alt', 'alt_text' ) ) : '', 'caption' => is_array( $item ) ? self::first_string( $item, array( 'caption', 'text' ) ) : '' ); } } return $out; }
	private static function normalize_label_text( array $items ): array { $out = array(); foreach ( $items as $item ) { $out[] = is_array( $item ) ? array( 'label' => self::first_string( $item, array( 'label', 'title', 'name' ) ), 'text' => self::first_string( $item, array( 'text', 'description', 'value' ) ) ) : array( 'label' => trim( (string) $item ), 'text' => '' ); } return array_values( array_filter( $out, static fn( $i ) => '' !== ( $i['label'] ?? '' ) || '' !== ( $i['text'] ?? '' ) ) ); }
	private static function normalize_testimonials( array $items ): array { $out = array(); foreach ( $items as $item ) { if ( is_array( $item ) ) { $out[] = array( 'quote' => self::first_string( $item, array( 'quote', 'text', 'review' ) ), 'name' => self::first_string( $item, array( 'name', 'author' ) ), 'location' => self::first_string( $item, array( 'location' ) ), 'rating' => is_numeric( $item['rating'] ?? null ) ? (float) $item['rating'] : 5 ); } } return array_values( array_filter( $out, static fn( $i ) => '' !== ( $i['quote'] ?? '' ) ) ); }
	private static function normalize_route( array $items ): array { $out = array(); foreach ( $items as $i => $item ) { if ( is_array( $item ) ) { $out[] = array( 'day' => self::first_string( $item, array( 'day' ) ) ?: sprintf( __( 'Day %d', 'igp-pro' ), $i + 1 ), 'title' => self::first_string( $item, array( 'title', 'name' ) ), 'location' => self::first_string( $item, array( 'location', 'place' ) ), 'duration' => self::first_string( $item, array( 'duration' ) ), 'description' => self::first_string( $item, array( 'description', 'text' ) ), 'highlights' => self::normalize_item_list( self::first_list( $item, array( 'highlights', 'items' ) ) ) ); } } return $out; }
	private static function normalize_seasons( array $items ): array { $out = array(); foreach ( $items as $item ) { if ( is_array( $item ) ) { $out[] = array( 'name' => self::first_string( $item, array( 'name', 'title' ) ), 'months' => self::first_string( $item, array( 'months' ) ), 'temperature' => self::first_string( $item, array( 'temperature' ) ), 'description' => self::first_string( $item, array( 'description', 'text' ) ), 'best_for' => self::first_string( $item, array( 'best_for' ) ) ); } } return $out; }
}
