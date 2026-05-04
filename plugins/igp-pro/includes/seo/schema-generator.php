<?php
/**
 * JSON-LD schema generator for IGP Pro.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get Content Graph data for SEO/schema contexts.
 *
 * Saved Content Graph meta is the canonical source of truth. Parsed
 * post_content is used only as a recovery fallback when no canonical graph
 * sections or SEO object exist yet.
 */
function igp_pro_seo_get_content_graph( int $post_id ): array {
	if ( function_exists( 'igp_pro_load_content_graph' ) ) {
		$graph = igp_pro_load_content_graph( $post_id );
		if ( is_array( $graph ) ) {
			$has_sections = ! empty( $graph['sections'] ) && is_array( $graph['sections'] );
			$has_seo      = ! empty( $graph['seo'] ) && is_array( $graph['seo'] );

			if ( $has_sections || $has_seo ) {
				return $graph;
			}
		}
	}

	if ( function_exists( 'igp_pro_content_graph_from_post_content' ) ) {
		$graph = igp_pro_content_graph_from_post_content( $post_id );
		if ( is_array( $graph ) && ! empty( $graph['sections'] ) ) {
			return $graph;
		}
	}

	return function_exists( 'igp_pro_get_empty_content_graph' ) ? igp_pro_get_empty_content_graph() : array( 'version' => 'v1', 'sections' => array() );
}

/**
 * Return all sections with a block ID.
 */
function igp_pro_seo_get_sections_by_block( array $graph, string $block_id ): array {
	$found = array();
	foreach ( $graph['sections'] ?? array() as $section ) {
		if ( is_array( $section ) && isset( $section['block_id'] ) && sanitize_key( (string) $section['block_id'] ) === sanitize_key( $block_id ) ) {
			$found[] = $section;
		}
	}
	return $found;
}

/**
 * Extract clean text from a section data key list.
 */
function igp_pro_seo_first_section_text( array $graph, array $block_ids, array $keys ): string {
	foreach ( $block_ids as $block_id ) {
		foreach ( igp_pro_seo_get_sections_by_block( $graph, $block_id ) as $section ) {
			$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
			foreach ( $keys as $key ) {
				if ( ! isset( $data[ $key ] ) ) {
					continue;
				}

				$value = $data[ $key ];
				if ( 'heading' === $key && is_array( $value ) ) {
					$value = ! empty( $value['visible'] ) ? (string) ( $value['text'] ?? '' ) : '';
				}

				if ( is_scalar( $value ) && '' !== trim( wp_strip_all_tags( (string) $value ) ) ) {
					return trim( wp_strip_all_tags( (string) $value ) );
				}
			}
		}
	}
	return '';
}

/**
 * Find a primary image URL from featured image or graph sections.
 */
function igp_pro_seo_get_primary_image( int $post_id, array $graph ): string {
	$image = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( $image ) {
		return esc_url_raw( $image );
	}

	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'hero' ) as $section ) {
		$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		if ( isset( $data['background_image'] ) ) {
			$url = function_exists( 'igp_pro_get_image_url' ) ? igp_pro_get_image_url( $data['background_image'] ) : '';
			if ( '' !== $url ) {
				return $url;
			}
		}
	}

	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'gallery' ) as $section ) {
		$data   = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$images = isset( $data['images'] ) ? $data['images'] : array();
		if ( is_string( $images ) ) {
			$decoded = json_decode( $images, true );
			$images  = is_array( $decoded ) ? $decoded : array();
		}
		if ( is_array( $images ) ) {
			foreach ( $images as $item ) {
				$url = is_array( $item ) && isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '';
				if ( '' !== $url ) {
					return $url;
				}
			}
		}
	}

	return '';
}

/**
 * Extract FAQ structured data from the Content Graph.
 */
function igp_pro_seo_get_faq_entities( array $graph ): array {
	$entities = array();
	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'faq' ) as $section ) {
		$data  = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$items = $data['items'] ?? array();
		if ( is_string( $items ) ) {
			$decoded = json_decode( $items, true );
			$items   = is_array( $decoded ) ? $decoded : array();
		}
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$question = trim( wp_strip_all_tags( (string) ( $item['question'] ?? '' ) ) );
			$answer   = trim( wp_strip_all_tags( (string) ( $item['answer'] ?? '' ) ) );
			if ( '' !== $question && '' !== $answer ) {
				$entities[] = array(
					'@type'          => 'Question',
					'name'           => $question,
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $answer,
					),
				);
			}
		}
	}
	return $entities;
}


/**
 * Extract aggregate rating data from the P0 Reviews Summary block.
 */
function igp_pro_schema_get_aggregate_rating( array $graph ): ?array {
	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'reviews_summary' ) as $section ) {
		$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$rating = isset( $data['average_rating'] ) ? (float) $data['average_rating'] : 0.0;
		$count  = isset( $data['review_count'] ) ? absint( $data['review_count'] ) : 0;
		if ( $rating > 0 && $count > 0 ) {
			return array(
				'@type'       => 'AggregateRating',
				'ratingValue' => max( 0, min( 5, $rating ) ),
				'bestRating'  => 5,
				'worstRating' => 1,
				'reviewCount' => $count,
			);
		}
	}

	return null;
}

/**
 * Extract package-tier or departure-date offers for the TouristTrip schema.
 */
function igp_pro_schema_get_p0_offers( array $graph, string $url, string $fallback_currency = 'INR' ): ?array {
	$offers = array();

	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'package_tiers' ) as $section ) {
		$data  = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$tiers = $data['tiers'] ?? array();
		if ( is_string( $tiers ) ) {
			$decoded = json_decode( $tiers, true );
			$tiers   = is_array( $decoded ) ? $decoded : array();
		}

		foreach ( is_array( $tiers ) ? $tiers : array() as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}
			$price = function_exists( 'igp_pro_parse_money' ) ? igp_pro_parse_money( $tier['price'] ?? '' ) : (float) preg_replace( '/[^0-9.]/', '', (string) ( $tier['price'] ?? '' ) );
			if ( $price <= 0 ) {
				continue;
			}
			$currency = igp_pro_schema_currency_from_symbol( (string) ( $tier['currency'] ?? $fallback_currency ) );
			$offers[] = array_filter(
				array(
					'@type'         => 'Offer',
					'name'          => trim( wp_strip_all_tags( (string) ( $tier['name'] ?? '' ) ) ),
					'price'         => $price,
					'priceCurrency' => $currency,
					'url'           => ! empty( $tier['cta_url'] ) ? esc_url_raw( (string) $tier['cta_url'] ) : $url,
					'availability'  => 'https://schema.org/InStock',
				)
			);
		}
	}

	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'departure_dates' ) as $section ) {
		$data  = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$dates = $data['dates'] ?? array();
		if ( is_string( $dates ) ) {
			$decoded = json_decode( $dates, true );
			$dates   = is_array( $decoded ) ? $decoded : array();
		}

		foreach ( is_array( $dates ) ? $dates : array() as $date ) {
			if ( ! is_array( $date ) ) {
				continue;
			}
			$price = function_exists( 'igp_pro_parse_money' ) ? igp_pro_parse_money( $date['price'] ?? '' ) : (float) preg_replace( '/[^0-9.]/', '', (string) ( $date['price'] ?? '' ) );
			$status = sanitize_key( (string) ( $date['status'] ?? 'available' ) );
			if ( $price <= 0 && 'on_request' !== $status ) {
				continue;
			}
			$availability = 'sold_out' === $status ? 'https://schema.org/SoldOut' : ( 'limited' === $status ? 'https://schema.org/LimitedAvailability' : 'https://schema.org/InStock' );
			$offers[] = array_filter(
				array(
					'@type'              => 'Offer',
					'name'               => trim( wp_strip_all_tags( (string) ( $date['start_date'] ?? '' ) ) ),
					'price'              => $price > 0 ? $price : null,
					'priceCurrency'      => igp_pro_schema_currency_from_symbol( (string) ( $date['currency'] ?? $fallback_currency ) ),
					'url'                => ! empty( $date['booking_url'] ) ? esc_url_raw( (string) $date['booking_url'] ) : $url,
					'availability'       => $availability,
					'availabilityStarts' => ! empty( $date['start_date'] ) ? (string) $date['start_date'] : null,
				)
			);
		}
	}

	if ( empty( $offers ) ) {
		return null;
	}

	return count( $offers ) > 1 ? array( '@type' => 'AggregateOffer', 'offers' => $offers ) : $offers[0];
}

/**
 * Extract a named tour fact value for schema enrichment.
 */
function igp_pro_schema_get_tour_fact_value( array $graph, array $labels ): string {
	$labels = array_map( 'strtolower', $labels );
	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'tour_facts' ) as $section ) {
		$data  = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$facts = $data['facts'] ?? array();
		if ( is_string( $facts ) ) {
			$decoded = json_decode( $facts, true );
			$facts   = is_array( $decoded ) ? $decoded : array();
		}
		foreach ( is_array( $facts ) ? $facts : array() as $fact ) {
			if ( ! is_array( $fact ) ) {
				continue;
			}
			$label = strtolower( trim( wp_strip_all_tags( (string) ( $fact['label'] ?? '' ) ) ) );
			$value = trim( wp_strip_all_tags( (string) ( $fact['value'] ?? '' ) ) );
			if ( '' !== $value && in_array( $label, $labels, true ) ) {
				return $value;
			}
		}
	}
	return '';
}

/**
 * Extract P1 route timeline entries as TouristTrip itinerary items.
 */
function igp_pro_schema_get_route_timeline_items( array $graph ): array {
	$items = array();
	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'route_timeline' ) as $section ) {
		$data  = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$stops = $data['stops'] ?? array();
		if ( is_string( $stops ) ) {
			$decoded = json_decode( $stops, true );
			$stops   = is_array( $decoded ) ? $decoded : array();
		}
		foreach ( is_array( $stops ) ? $stops : array() as $stop ) {
			if ( ! is_array( $stop ) ) {
				continue;
			}
			$name = trim( wp_strip_all_tags( (string) ( $stop['title'] ?? '' ) ) );
			$text = trim( wp_strip_all_tags( (string) ( $stop['description'] ?? '' ) ) );
			$loc  = trim( wp_strip_all_tags( (string) ( $stop['location'] ?? '' ) ) );
			if ( '' !== $name || '' !== $text || '' !== $loc ) {
				$items[] = array_filter(
					array(
						'@type'       => 'TouristAttraction',
						'name'        => '' !== $name ? $name : $loc,
						'description' => $text,
					)
				);
			}
		}
	}
	return $items;
}

/**
 * Extract lightweight P1 informational fields as schema PropertyValue objects.
 */
function igp_pro_schema_get_p1_travel_properties( array $graph ): array {
	$properties = array();

	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'best_time_to_visit' ) as $section ) {
		$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$best = trim( wp_strip_all_tags( (string) ( $data['best_months'] ?? '' ) ) );
		if ( '' !== $best ) {
			$properties[] = array(
				'@type' => 'PropertyValue',
				'name'  => __( 'Best time to visit', 'igp-pro' ),
				'value' => $best,
			);
		}
	}

	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'visa_requirements' ) as $section ) {
		$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$requirements = $data['requirements'] ?? array();
		if ( is_string( $requirements ) ) {
			$decoded      = json_decode( $requirements, true );
			$requirements = is_array( $decoded ) ? $decoded : array();
		}
		$labels = array();
		foreach ( is_array( $requirements ) ? $requirements : array() as $requirement ) {
			if ( ! is_array( $requirement ) ) {
				continue;
			}
			$title = trim( wp_strip_all_tags( (string) ( $requirement['title'] ?? '' ) ) );
			if ( '' !== $title ) {
				$labels[] = $title;
			}
		}
		if ( ! empty( $labels ) ) {
			$properties[] = array(
				'@type' => 'PropertyValue',
				'name'  => __( 'Travel requirements', 'igp-pro' ),
				'value' => implode( ', ', array_slice( $labels, 0, 5 ) ),
			);
		}
	}

	return $properties;
}


/**
 * Build BreadcrumbList item data.
 */
function igp_pro_seo_get_breadcrumb_items( WP_Post $post ): array {
	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		),
	);

	if ( 'tour' === $post->post_type ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => __( 'Tours', 'igp-pro' ),
			'item'     => get_post_type_archive_link( 'tour' ) ?: home_url( '/tours/' ),
		);
	} elseif ( 'destination' === $post->post_type ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => __( 'Destinations', 'igp-pro' ),
			'item'     => get_post_type_archive_link( 'destination' ) ?: home_url( '/destinations/' ),
		);
	}

	$items[] = array(
		'@type'    => 'ListItem',
		'position' => count( $items ) + 1,
		'name'     => get_the_title( $post ),
		'item'     => get_permalink( $post ),
	);

	return $items;
}

/**
 * Generate JSON-LD payload derived from post data and the Content Graph.
 */
function igp_pro_generate_json_ld( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$graph       = igp_pro_seo_get_content_graph( $post_id );
	$url         = get_permalink( $post );
	$title       = get_the_title( $post );
	$description = function_exists( 'igp_pro_generate_meta_description' ) ? igp_pro_generate_meta_description( $post_id ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
	$image       = igp_pro_seo_get_primary_image( $post_id, $graph );
	$site_name   = get_bloginfo( 'name' );
	$settings    = function_exists( 'igp_pro_get_seo_settings' ) ? igp_pro_get_seo_settings() : array();
	$org_name    = ! empty( $settings['organization_name'] ) ? (string) $settings['organization_name'] : $site_name;
	$org_logo    = ! empty( $settings['organization_logo'] ) ? esc_url_raw( (string) $settings['organization_logo'] ) : '';

	$entities = array(
		array_filter(
			array(
				'@type' => 'TravelAgency',
				'@id'   => home_url( '/#organization' ),
				'name'  => $org_name,
				'url'   => home_url( '/' ),
				'logo'  => '' !== $org_logo ? array( '@type' => 'ImageObject', 'url' => $org_logo ) : null,
			)
		),
		array(
			'@type'     => 'WebSite',
			'@id'       => home_url( '/#website' ),
			'url'       => home_url( '/' ),
			'name'      => $site_name,
			'publisher' => array( '@id' => home_url( '/#organization' ) ),
		),
		array_filter(
			array(
				'@type'              => 'WebPage',
				'@id'                => trailingslashit( $url ) . '#webpage',
				'url'                => $url,
				'name'               => $title,
				'description'        => $description,
				'isPartOf'           => array( '@id' => home_url( '/#website' ) ),
				'primaryImageOfPage' => '' !== $image ? array( '@type' => 'ImageObject', 'url' => $image ) : null,
			)
		),
		array(
			'@type'           => 'BreadcrumbList',
			'@id'             => trailingslashit( $url ) . '#breadcrumb',
			'itemListElement' => igp_pro_seo_get_breadcrumb_items( $post ),
		),
	);

	if ( 'tour' === $post->post_type ) {
		$price       = function_exists( 'igp_pro_parse_money' ) ? igp_pro_parse_money( get_post_meta( $post_id, '_igp_booking_base_price', true ) ) : (float) get_post_meta( $post_id, '_igp_booking_base_price', true );
		if ( $price <= 0 ) {
			$price = function_exists( 'igp_pro_parse_money' ) ? igp_pro_parse_money( get_post_meta( $post_id, '_igp_price', true ) ) : (float) get_post_meta( $post_id, '_igp_price', true );
		}
		$currency_symbol = (string) get_post_meta( $post_id, '_igp_booking_currency', true );
		$currency        = igp_pro_schema_currency_from_symbol( $currency_symbol );
		$duration        = get_post_meta( $post_id, '_igp_duration', true );
		if ( '' === (string) $duration ) {
			$duration = igp_pro_schema_get_tour_fact_value( $graph, array( 'duration', 'trip duration', 'tour duration' ) );
		}

		$p0_offers = igp_pro_schema_get_p0_offers( $graph, $url, $currency );
		if ( null === $p0_offers && $price > 0 ) {
			$p0_offers = array(
				'@type'         => 'Offer',
				'price'         => $price,
				'priceCurrency' => $currency,
				'url'           => $url,
				'availability'  => 'https://schema.org/InStock',
			);
		}

		$entities[] = array_filter(
			array(
				'@type'           => 'TouristTrip',
				'@id'             => trailingslashit( $url ) . '#tour',
				'name'            => $title,
				'description'     => $description,
				'url'             => $url,
				'image'           => '' !== $image ? array( $image ) : null,
				'itinerary'       => array_values( array_merge( igp_pro_schema_itinerary_items( $graph ), igp_pro_schema_get_route_timeline_items( $graph ) ) ),
				'offers'          => $p0_offers,
				'aggregateRating' => igp_pro_schema_get_aggregate_rating( $graph ),
				'touristType'     => '' !== (string) $duration ? (string) $duration : null,
				'additionalProperty' => igp_pro_schema_get_p1_travel_properties( $graph ),
			)
		);
	} elseif ( 'destination' === $post->post_type ) {
		$entities[] = array_filter(
			array(
				'@type'       => 'TouristDestination',
				'@id'         => trailingslashit( $url ) . '#destination',
				'name'        => $title,
				'description' => $description,
				'url'         => $url,
				'image'       => '' !== $image ? array( $image ) : null,
			)
		);
	}

	$faq_entities = igp_pro_seo_get_faq_entities( $graph );
	if ( ! empty( $faq_entities ) ) {
		$entities[] = array(
			'@type'      => 'FAQPage',
			'@id'        => trailingslashit( $url ) . '#faq',
			'mainEntity' => $faq_entities,
		);
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( array_filter( $entities ) ),
	);
}

/**
 * Return itinerary graph values.
 */
function igp_pro_schema_itinerary_items( array $graph ): array {
	$items = array();
	foreach ( igp_pro_seo_get_sections_by_block( $graph, 'itinerary' ) as $section ) {
		$data = isset( $section['data'] ) && is_array( $section['data'] ) ? $section['data'] : array();
		$days = $data['days'] ?? array();
		if ( is_string( $days ) ) {
			$decoded = json_decode( $days, true );
			$days    = is_array( $decoded ) ? $decoded : array();
		}
		foreach ( is_array( $days ) ? $days : array() as $day ) {
			if ( ! is_array( $day ) ) {
				continue;
			}
			$name = trim( wp_strip_all_tags( (string) ( $day['day_title'] ?? '' ) ) );
			$text = trim( wp_strip_all_tags( (string) ( $day['description'] ?? '' ) ) );
			if ( '' !== $name || '' !== $text ) {
				$items[] = array_filter(
					array(
						'@type'       => 'TouristAttraction',
						'name'        => '' !== $name ? $name : __( 'Itinerary item', 'igp-pro' ),
						'description' => $text,
					)
				);
			}
		}
	}
	return $items;
}

/**
 * Convert common currency symbols to ISO-ish codes for schema.
 */
function igp_pro_schema_currency_from_symbol( string $symbol ): string {
	$symbol = trim( $symbol );
	$map    = array( '₹' => 'INR', '$' => 'USD', '€' => 'EUR', '£' => 'GBP' );
	if ( isset( $map[ $symbol ] ) ) {
		return $map[ $symbol ];
	}
	$upper = strtoupper( preg_replace( '/[^A-Z]/i', '', $symbol ) ?? '' );
	return '' !== $upper ? substr( $upper, 0, 3 ) : 'INR';
}
