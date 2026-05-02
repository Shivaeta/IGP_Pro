<?php
/**
 * Capability and role service for IGP Pro V2.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'IGP_PRO_ROLE_CAPABILITIES_OPTION' ) ) {
	define( 'IGP_PRO_ROLE_CAPABILITIES_OPTION', 'igp_pro_role_capabilities' );
}

/**
 * Return the controlled IGP capability registry.
 *
 * @return array<string,array{label:string,description:string,privileged:bool}>
 */
function igp_pro_get_capability_definitions(): array {
	return array(
		'igp_manage_settings'             => array(
			'label'       => __( 'Manage IGP Settings', 'igp-pro' ),
			'description' => __( 'Change core IGP settings, including V2 feature flags and role grants.', 'igp-pro' ),
			'privileged'  => true,
		),
		'igp_edit_content_graph'          => array(
			'label'       => __( 'Edit Content Graph', 'igp-pro' ),
			'description' => __( 'Open the IGP content editor and save structured Content Graph data.', 'igp-pro' ),
			'privileged'  => false,
		),
		'igp_import_content'              => array(
			'label'       => __( 'Import Content', 'igp-pro' ),
			'description' => __( 'Import structured IGP Content Graph payloads.', 'igp-pro' ),
			'privileged'  => false,
		),
		'igp_manage_templates'            => array(
			'label'       => __( 'Manage Templates', 'igp-pro' ),
			'description' => __( 'Preview, import, merge, and roll back starter templates.', 'igp-pro' ),
			'privileged'  => true,
		),
		'igp_manage_media_optimization'   => array(
			'label'       => __( 'Manage Media Optimization', 'igp-pro' ),
			'description' => __( 'Run media audits and optimization operations.', 'igp-pro' ),
			'privileged'  => true,
		),
		'igp_manage_seo'                  => array(
			'label'       => __( 'Manage SEO', 'igp-pro' ),
			'description' => __( 'Manage IGP SEO settings, audits, and structured SEO data.', 'igp-pro' ),
			'privileged'  => true,
		),
		'igp_manage_integrations'         => array(
			'label'       => __( 'Manage Integrations', 'igp-pro' ),
			'description' => __( 'Manage optional integration bridges such as Rank Math and Link Whisper.', 'igp-pro' ),
			'privileged'  => true,
		),
		'igp_use_mcp_bridge'              => array(
			'label'       => __( 'Use MCP Bridge', 'igp-pro' ),
			'description' => __( 'Use controlled MCP/AI bridge operations after the bridge is implemented.', 'igp-pro' ),
			'privileged'  => true,
		),
		'igp_publish_ai_changes'          => array(
			'label'       => __( 'Publish AI Changes', 'igp-pro' ),
			'description' => __( 'Approve and publish AI/API-proposed changesets.', 'igp-pro' ),
			'privileged'  => true,
		),
		'igp_manage_recovery'             => array(
			'label'       => __( 'Manage Recovery', 'igp-pro' ),
			'description' => __( 'Create snapshots, inspect recovery data, and restore rollback points.', 'igp-pro' ),
			'privileged'  => true,
		),
	);
}

/**
 * Return all controlled IGP capability slugs.
 *
 * @return string[]
 */
function igp_pro_get_capabilities(): array {
	return array_keys( igp_pro_get_capability_definitions() );
}

/**
 * Sanitize a capability slug against the controlled registry.
 *
 * @param string $capability Capability slug.
 * @return string
 */
function igp_pro_sanitize_capability( string $capability ): string {
	$capability = sanitize_key( $capability );
	return in_array( $capability, igp_pro_get_capabilities(), true ) ? $capability : '';
}

/**
 * Determine whether a user has an IGP capability.
 *
 * @param string   $capability Capability slug.
 * @param int|null $user_id    Optional user ID. Defaults to current user.
 * @return bool
 */
function igp_pro_current_user_can( string $capability, ?int $user_id = null ): bool {
	$capability = igp_pro_sanitize_capability( $capability );

	if ( '' === $capability ) {
		return false;
	}

	if ( null !== $user_id ) {
		return user_can( $user_id, $capability );
	}

	return current_user_can( $capability );
}

/**
 * Required-style short helper for IGP capability checks.
 *
 * @param string $capability Capability slug.
 * @return bool
 */
function igp_user_can( string $capability ): bool {
	return igp_pro_current_user_can( $capability );
}

/**
 * Return capabilities that may be granted to restricted content roles from settings.
 *
 * Privileged caps stay administrator-only unless a future phase explicitly adds
 * a dedicated workflow for delegating them.
 *
 * @return string[]
 */
function igp_pro_get_restricted_role_grantable_capabilities(): array {
	return array(
		'igp_edit_content_graph',
		'igp_import_content',
	);
}

/**
 * Return roles managed by the Phase 6.2 settings UI.
 *
 * @return array<string,string>
 */
function igp_pro_get_managed_capability_roles(): array {
	return array(
		'editor' => __( 'Editor', 'igp-pro' ),
	);
}

/**
 * Add administrator capabilities and reconcile explicitly managed role grants.
 */
function igp_pro_register_capabilities(): void {
	$administrator = get_role( 'administrator' );

	if ( $administrator instanceof WP_Role ) {
		foreach ( igp_pro_get_capabilities() as $capability ) {
			$administrator->add_cap( $capability );
		}
	}

	$stored = get_option( IGP_PRO_ROLE_CAPABILITIES_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	igp_pro_apply_managed_role_capabilities( $stored );
}

/**
 * Remove IGP capabilities from roles on uninstall/deactivation workflows if needed.
 *
 * This function is intentionally not called on ordinary deactivation so existing
 * admin access is not unexpectedly disrupted while testing.
 */
function igp_pro_remove_capabilities_from_roles(): void {
	foreach ( array_keys( wp_roles()->roles ) as $role_slug ) {
		$role = get_role( $role_slug );
		if ( ! $role instanceof WP_Role ) {
			continue;
		}

		foreach ( igp_pro_get_capabilities() as $capability ) {
			$role->remove_cap( $capability );
		}
	}
}

/**
 * Sanitize managed role capability grants.
 *
 * @param mixed $grants Raw grants keyed by role.
 * @return array<string,string[]>
 */
function igp_pro_sanitize_role_capability_grants( $grants ): array {
	$managed_roles = igp_pro_get_managed_capability_roles();
	$grantable     = igp_pro_get_restricted_role_grantable_capabilities();
	$raw           = is_array( $grants ) ? $grants : array();
	$sanitized     = array();

	foreach ( $managed_roles as $role_slug => $label ) {
		$role_grants = isset( $raw[ $role_slug ] ) && is_array( $raw[ $role_slug ] ) ? $raw[ $role_slug ] : array();
		$requested   = array();

		foreach ( $role_grants as $key => $value ) {
			if ( is_string( $key ) && ! is_numeric( $key ) ) {
				if ( ! empty( $value ) ) {
					$requested[] = sanitize_key( $key );
				}
			} elseif ( is_scalar( $value ) ) {
				$requested[] = sanitize_key( (string) $value );
			}
		}

		$sanitized[ $role_slug ] = array_values( array_intersect( array_unique( $requested ), $grantable ) );
	}

	return $sanitized;
}

/**
 * Apply managed role capability grants to WordPress roles.
 *
 * @param array<string,string[]> $grants Sanitized grants.
 */
function igp_pro_apply_managed_role_capabilities( array $grants ): void {
	$grantable = igp_pro_get_restricted_role_grantable_capabilities();

	foreach ( igp_pro_get_managed_capability_roles() as $role_slug => $label ) {
		$role = get_role( $role_slug );
		if ( ! $role instanceof WP_Role ) {
			continue;
		}

		$role_caps = isset( $grants[ $role_slug ] ) && is_array( $grants[ $role_slug ] ) ? $grants[ $role_slug ] : array();

		foreach ( $grantable as $capability ) {
			if ( in_array( $capability, $role_caps, true ) ) {
				$role->add_cap( $capability );
			} else {
				$role->remove_cap( $capability );
			}
		}
	}
}

/**
 * Update explicit restricted-role IGP grants.
 *
 * @param mixed $grants Raw grants keyed by role.
 * @return bool
 */
function igp_pro_update_role_capability_grants( $grants ): bool {
	$sanitized = igp_pro_sanitize_role_capability_grants( $grants );
	igp_pro_apply_managed_role_capabilities( $sanitized );

	return update_option( IGP_PRO_ROLE_CAPABILITIES_OPTION, $sanitized, false );
}

/**
 * Get explicit restricted-role IGP grants from actual role state.
 *
 * @return array<string,string[]>
 */
function igp_pro_get_role_capability_grants(): array {
	$stored = get_option( IGP_PRO_ROLE_CAPABILITIES_OPTION, array() );
	$stored = igp_pro_sanitize_role_capability_grants( $stored );
	$roles  = array();

	foreach ( igp_pro_get_managed_capability_roles() as $role_slug => $label ) {
		$role = get_role( $role_slug );
		$roles[ $role_slug ] = array();

		if ( ! $role instanceof WP_Role ) {
			continue;
		}

		foreach ( igp_pro_get_restricted_role_grantable_capabilities() as $capability ) {
			if ( ! empty( $role->capabilities[ $capability ] ) ) {
				$roles[ $role_slug ][] = $capability;
			}
		}
	}

	return $roles ?: $stored;
}

/**
 * Get the capability used for a named module/admin surface.
 *
 * @param string $surface Surface key.
 * @return string
 */
function igp_pro_get_surface_capability( string $surface ): string {
	$surface = sanitize_key( $surface );
	$map     = array(
		'content_editor'      => 'igp_edit_content_graph',
		'import_content'      => 'igp_import_content',
		'settings'            => 'igp_manage_settings',
		'seo'                 => 'igp_manage_seo',
		'payment_settings'    => 'igp_manage_integrations',
		'integrations'        => 'igp_manage_integrations',
		'recovery'            => 'igp_manage_recovery',
		'diagnostics'         => 'igp_manage_settings',
		'templates'           => 'igp_manage_templates',
		'media_optimization'  => 'igp_manage_media_optimization',
		'mcp'                 => 'igp_use_mcp_bridge',
	);

	return $map[ $surface ] ?? 'igp_manage_settings';
}
