<?php
/**
 * Tour/destination relationship service for IGP Pro V2.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IGP_Relationships' ) ) {
	/**
	 * Service abstraction for tour/destination relationships.
	 */
	class IGP_Relationships {
		/**
		 * Validate relationship payload.
		 *
		 * @param mixed $payload   Raw payload.
		 * @param int   $object_id Optional owner post ID.
		 * @return array|WP_Error
		 */
		public static function validate_relationship_payload( $payload, int $object_id = 0 ) {
			return igp_pro_validate_relationship_payload( $payload, $object_id );
		}

		/**
		 * Return canonical relationship data for an object.
		 *
		 * Deleted targets are filtered during reads so frontend rendering degrades safely.
		 *
		 * @param int  $object_id       Post ID.
		 * @param bool $include_deleted Whether to include raw deleted/missing refs.
		 * @return array<string,int|int[]>
		 */
		public static function get_relationships( int $object_id, bool $include_deleted = false ): array {
			$meta_keys = igp_pro_get_relationship_meta_keys();
			$payload   = array();

			foreach ( $meta_keys as $field => $meta_key ) {
				$value = get_post_meta( $object_id, $meta_key, true );

				if ( 'primary_destination_id' === $field ) {
					$payload[ $field ] = absint( $value );
				} else {
					$payload[ $field ] = igp_pro_normalize_post_ids( $value );
				}
			}

			$payload = igp_pro_sanitize_relationship_payload( $payload );

			return $include_deleted ? $payload : igp_pro_filter_existing_relationship_payload( $payload );
		}

		/**
		 * Save relationships for a tour or destination.
		 *
		 * @param int   $object_id Post ID.
		 * @param mixed $payload   Raw relationship payload.
		 * @param array $context   Optional context.
		 * @return true|WP_Error
		 */
		public static function save_relationships( int $object_id, $payload, array $context = array() ) {
			if ( $object_id <= 0 ) {
				return new WP_Error( 'igp_pro_invalid_post_id', __( 'A valid post ID is required.', 'igp-pro' ) );
			}

			$post_type = get_post_type( $object_id );
			if ( ! in_array( $post_type, array( 'tour', 'destination' ), true ) ) {
				return new WP_Error( 'igp_pro_relationship_invalid_owner_type', __( 'Relationships can be stored only for tours and destinations.', 'igp-pro' ) );
			}

			$validated = self::validate_relationship_payload( $payload, $object_id );
			if ( is_wp_error( $validated ) ) {
				self::log_relationship_event( 'relationship_validation_failed', $object_id, 'failure', $validated->get_error_message(), $validated->get_error_code(), '' );
				return $validated;
			}

			$before      = self::get_relationships( $object_id, true );
			$snapshot_id = '';
			if ( function_exists( 'igp_create_snapshot' ) ) {
				$snapshot = igp_create_snapshot(
					'relationship_data',
					$object_id,
					$before,
					array(
						'source_module' => 'relationships',
						'actor_type'    => isset( $context['actor_type'] ) ? sanitize_key( (string) $context['actor_type'] ) : 'human',
						'reason'        => isset( $context['reason'] ) ? sanitize_key( (string) $context['reason'] ) : 'relationship_update',
					)
				);
				if ( is_string( $snapshot ) ) {
					$snapshot_id = $snapshot;
				}
			}

			self::persist_relationships( $object_id, $validated );

			if ( '' !== $snapshot_id && function_exists( 'igp_pro_update_snapshot_after_data' ) ) {
				igp_pro_update_snapshot_after_data( $snapshot_id, self::get_relationships( $object_id, true ) );
			}

			self::log_relationship_event( 'relationships_updated', $object_id, 'success', 'Relationship data updated.', '', $snapshot_id );

			return true;
		}

		/**
		 * Persist canonical relationship data and index meta.
		 *
		 * @param int   $object_id Post ID.
		 * @param array $payload   Validated payload.
		 */
		private static function persist_relationships( int $object_id, array $payload ): void {
			$meta_keys = igp_pro_get_relationship_meta_keys();

			foreach ( $meta_keys as $field => $meta_key ) {
				$value = $payload[ $field ] ?? ( 'primary_destination_id' === $field ? 0 : array() );
				update_post_meta( $object_id, $meta_key, $value );
			}

			delete_post_meta( $object_id, IGP_PRO_REL_DESTINATION_INDEX_META_KEY );

			$index_ids = array();
			foreach ( array( 'primary_destination_id', 'destination_ids', 'route_stop_ids' ) as $field ) {
				$values = is_array( $payload[ $field ] ?? null ) ? (array) $payload[ $field ] : array( absint( $payload[ $field ] ?? 0 ) );
				foreach ( $values as $destination_id ) {
					$destination_id = absint( $destination_id );
					if ( $destination_id > 0 ) {
						$index_ids[] = $destination_id;
					}
				}
			}

			foreach ( array_values( array_unique( $index_ids ) ) as $destination_id ) {
				add_post_meta( $object_id, IGP_PRO_REL_DESTINATION_INDEX_META_KEY, $destination_id, false );
			}
		}

		/**
		 * Return a tour's primary destination ID.
		 *
		 * @param int $tour_id Tour ID.
		 * @return int
		 */
		public static function get_primary_destination( int $tour_id ): int {
			if ( 'tour' !== get_post_type( $tour_id ) ) {
				return 0;
			}

			$relationships = self::get_relationships( $tour_id );
			return absint( $relationships['primary_destination_id'] ?? 0 );
		}

		/**
		 * Return destination IDs for a tour.
		 *
		 * @param int $tour_id Tour ID.
		 * @return int[]
		 */
		public static function get_destination_ids_for_tour( int $tour_id ): array {
			if ( 'tour' !== get_post_type( $tour_id ) ) {
				return array();
			}

			$relationships = self::get_relationships( $tour_id );
			$ids           = array_merge(
				array( absint( $relationships['primary_destination_id'] ?? 0 ) ),
				(array) ( $relationships['destination_ids'] ?? array() ),
				(array) ( $relationships['route_stop_ids'] ?? array() )
			);

			return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		}

		/**
		 * Return route stop destination IDs for a tour.
		 *
		 * @param int $tour_id Tour ID.
		 * @return int[]
		 */
		public static function get_route_stop_ids_for_tour( int $tour_id ): array {
			$relationships = self::get_relationships( $tour_id );
			return isset( $relationships['route_stop_ids'] ) && is_array( $relationships['route_stop_ids'] ) ? array_values( array_map( 'absint', $relationships['route_stop_ids'] ) ) : array();
		}

		/**
		 * Query tours for a destination.
		 *
		 * @param int   $destination_id Destination ID.
		 * @param array $args           Optional WP_Query args.
		 * @return WP_Query
		 */
		public static function get_tours_for_destination( int $destination_id, array $args = array() ): WP_Query {
			$destination_id = absint( $destination_id );
			if ( $destination_id <= 0 || 'destination' !== get_post_type( $destination_id ) ) {
				return new WP_Query(
					array(
						'post_type'      => 'tour',
						'post__in'       => array( 0 ),
						'posts_per_page' => 1,
						'no_found_rows'  => true,
					)
				);
			}

			$defaults = array(
				'post_type'              => 'tour',
				'post_status'            => 'publish',
				'posts_per_page'         => 6,
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'meta_query'             => array(
					array(
						'key'     => IGP_PRO_REL_DESTINATION_INDEX_META_KEY,
						'value'   => $destination_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
			);

			return new WP_Query( wp_parse_args( $args, $defaults ) );
		}

		/**
		 * Query tours for multiple destinations.
		 *
		 * @param int[] $destination_ids Destination IDs.
		 * @param array $args            Optional WP_Query args.
		 * @return WP_Query
		 */
		public static function get_tours_for_destinations( array $destination_ids, array $args = array() ): WP_Query {
			$destination_ids = array_values( array_unique( array_filter( array_map( 'absint', $destination_ids ) ) ) );
			if ( empty( $destination_ids ) ) {
				return new WP_Query( wp_parse_args( array( 'post__in' => array( 0 ) ), $args ) );
			}

			$meta_query = array( 'relation' => 'OR' );
			foreach ( $destination_ids as $destination_id ) {
				$meta_query[] = array(
					'key'     => IGP_PRO_REL_DESTINATION_INDEX_META_KEY,
					'value'   => $destination_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
			}

			return new WP_Query(
				wp_parse_args(
					$args,
					array(
						'post_type'           => 'tour',
						'post_status'         => 'publish',
						'posts_per_page'      => 6,
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
						'meta_query'          => $meta_query,
					)
				)
			);
		}

		/**
		 * Return explicit related tour IDs for a post.
		 *
		 * @param int    $post_id  Owner post ID.
		 * @param string $context  Optional context label.
		 * @return int[]
		 */
		public static function get_related_tours( int $post_id, string $context = '' ): array {
			$post_type     = get_post_type( $post_id );
			$relationships = self::get_relationships( $post_id );
			$ids           = array();

			if ( 'tour' === $post_type ) {
				$ids = (array) ( $relationships['related_tour_ids'] ?? array() );
			} elseif ( 'destination' === $post_type ) {
				$query = self::get_tours_for_destination( $post_id, array( 'posts_per_page' => 12, 'fields' => 'ids' ) );
				$ids   = is_array( $query->posts ) ? $query->posts : array();
			}

			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

			if ( 'tour' === $post_type ) {
				$ids = array_values( array_diff( $ids, array( $post_id ) ) );
			}

			return $ids;
		}

		/**
		 * Return related destination IDs for a tour or destination.
		 *
		 * @param int    $post_id Owner post ID.
		 * @param string $context Optional context label.
		 * @return int[]
		 */
		public static function get_related_destinations( int $post_id, string $context = '' ): array {
			$post_type     = get_post_type( $post_id );
			$relationships = self::get_relationships( $post_id );
			$ids           = array();

			if ( 'tour' === $post_type ) {
				$ids = array_merge(
					array( absint( $relationships['primary_destination_id'] ?? 0 ) ),
					(array) ( $relationships['destination_ids'] ?? array() ),
					(array) ( $relationships['route_stop_ids'] ?? array() ),
					(array) ( $relationships['related_destination_ids'] ?? array() )
				);
			} elseif ( 'destination' === $post_type ) {
				$ids = (array) ( $relationships['related_destination_ids'] ?? array() );
			}

			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

			if ( 'destination' === $post_type ) {
				$ids = array_values( array_diff( $ids, array( $post_id ) ) );
			}

			return $ids;
		}

		/**
		 * Log relationship service events.
		 *
		 * @param string $operation   Operation slug.
		 * @param int    $object_id   Object ID.
		 * @param string $status      success|failure.
		 * @param string $summary     Summary.
		 * @param string $error_code  Optional error code.
		 * @param string $snapshot_id Optional snapshot ID.
		 */
		private static function log_relationship_event( string $operation, int $object_id, string $status, string $summary, string $error_code = '', string $snapshot_id = '' ): void {
			if ( ! function_exists( 'igp_pro_log' ) ) {
				return;
			}

			igp_pro_log(
				array(
					'actor_type'    => is_user_logged_in() ? 'human' : 'system',
					'operation'     => $operation,
					'object_type'   => 'relationships',
					'object_id'     => $object_id,
					'source_module' => 'relationships',
					'status'        => $status,
					'error_code'    => $error_code,
					'summary'       => $summary,
					'snapshot_id'   => $snapshot_id,
				)
			);
		}
	}
}

/**
 * Function wrappers for service consumers.
 */
function igp_pro_get_relationships( int $object_id, bool $include_deleted = false ): array {
	return IGP_Relationships::get_relationships( $object_id, $include_deleted );
}

function igp_pro_save_relationships( int $object_id, $payload, array $context = array() ) {
	return IGP_Relationships::save_relationships( $object_id, $payload, $context );
}

function igp_pro_get_primary_destination( int $tour_id ): int {
	return IGP_Relationships::get_primary_destination( $tour_id );
}

function igp_pro_get_destination_ids_for_tour( int $tour_id ): array {
	return IGP_Relationships::get_destination_ids_for_tour( $tour_id );
}

function igp_pro_get_tours_for_destination( int $destination_id, array $args = array() ): WP_Query {
	return IGP_Relationships::get_tours_for_destination( $destination_id, $args );
}

function igp_pro_get_tours_for_destinations( array $destination_ids, array $args = array() ): WP_Query {
	return IGP_Relationships::get_tours_for_destinations( $destination_ids, $args );
}

function igp_pro_get_related_tours( int $post_id, string $context = '' ): array {
	return IGP_Relationships::get_related_tours( $post_id, $context );
}

function igp_pro_get_related_destinations( int $post_id, string $context = '' ): array {
	return IGP_Relationships::get_related_destinations( $post_id, $context );
}
