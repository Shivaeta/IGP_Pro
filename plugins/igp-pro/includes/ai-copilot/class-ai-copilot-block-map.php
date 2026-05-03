<?php
/**
 * AI Copilot block alias mapper.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

class IGP_AI_Copilot_Block_Map {
	/** Resolve an AI block name to a registered block ID. */
	public static function resolve_block( string $ai_block_name ): array {
		$input = self::normalize_alias( $ai_block_name );
		$aliases = function_exists( 'igp_ai_copilot_get_block_aliases' ) ? igp_ai_copilot_get_block_aliases() : array();
		$target = $aliases[ $input ] ?? null;

		if ( null === $target ) {
			return array(
				'status'     => 'unknown',
				'input'      => $ai_block_name,
				'block_id'   => null,
				'confidence' => 0,
				'warnings'   => array( __( 'Unknown AI block name. Manual mapping required.', 'igp-pro' ) ),
			);
		}

		$registered = self::is_registered( $target );
		if ( ! $registered ) {
			return array(
				'status'     => 'unavailable',
				'input'      => $ai_block_name,
				'block_id'   => null,
				'target'     => $target,
				'confidence' => 0,
				'warnings'   => array( __( 'Mapped target block is not registered in the current IGP registry.', 'igp-pro' ) ),
			);
		}

		return array(
			'status'     => 'mapped',
			'input'      => $ai_block_name,
			'block_id'   => $target,
			'confidence' => $input === $target ? 1 : 0.95,
			'warnings'   => array(),
		);
	}

	/** Return alias registry with availability report. */
	public static function get_supported_aliases(): array {
		$aliases = function_exists( 'igp_ai_copilot_get_block_aliases' ) ? igp_ai_copilot_get_block_aliases() : array();
		$out = array();
		foreach ( $aliases as $alias => $block_id ) {
			$out[ $alias ] = array(
				'block_id'    => $block_id,
				'registered'  => self::is_registered( $block_id ),
				'wp_slug'     => str_replace( '_', '-', $block_id ),
			);
		}
		return $out;
	}

	private static function normalize_alias( string $name ): string {
		$name = strtolower( trim( $name ) );
		$name = str_replace( array( ' ', '/', '.' ), '_', $name );
		$name = str_replace( '-', '_', $name );
		return preg_replace( '/[^a-z0-9_]/', '', $name ) ?: '';
	}

	private static function is_registered( string $block_id ): bool {
		if ( function_exists( 'igp_pro_get_registered_block' ) ) {
			return (bool) igp_pro_get_registered_block( $block_id );
		}
		if ( function_exists( 'igp_pro_get_default_block_definitions' ) ) {
			foreach ( igp_pro_get_default_block_definitions() as $block ) {
				if ( isset( $block['id'] ) && $block_id === (string) $block['id'] ) {
					return true;
				}
			}
		}
		return false;
	}
}
