<?php
/**
 * AI Copilot changeset review admin panel.
 *
 * @package IGP_Pro
 */

defined( 'ABSPATH' ) || exit;

function igp_pro_register_ai_copilot_changesets_admin(): void {
	add_action( 'admin_menu', 'igp_pro_register_ai_copilot_changesets_menu' );
	foreach ( array( 'approve', 'reject', 'rollback' ) as $action ) {
		add_action( 'admin_post_igp_ai_changeset_' . $action, 'igp_pro_handle_ai_changeset_' . $action );
	}
}

function igp_pro_register_ai_copilot_changesets_menu(): void {
	add_submenu_page(
		'igp-pro-content-editor',
		__( 'IGP AI Changesets', 'igp-pro' ),
		__( 'AI Changesets', 'igp-pro' ),
		function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts',
		'igp-pro-ai-changesets',
		'igp_pro_render_ai_changesets_page'
	);
}

function igp_pro_render_ai_changesets_page(): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to review IGP AI changesets.', 'igp-pro' ) );
	}

	if ( ! class_exists( 'IGP_AI_Copilot_Changeset' ) ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'IGP AI Changesets', 'igp-pro' ) . '</h1><div class="notice notice-error"><p>' . esc_html__( 'AI changeset service is unavailable.', 'igp-pro' ) . '</p></div></div>';
		return;
	}

	$notice = isset( $_GET['igp_notice'] ) ? sanitize_key( (string) wp_unslash( $_GET['igp_notice'] ) ) : '';
	$view_id = isset( $_GET['changeset_id'] ) ? sanitize_key( (string) wp_unslash( $_GET['changeset_id'] ) ) : '';
	?>
	<div class="wrap igp-pro-admin-wrap igp-ai-changesets-wrap">
		<h1><?php esc_html_e( 'IGP AI Changesets', 'igp-pro' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Review AI-generated compiled Content Graph proposals before they are saved. Approval saves draft-safe content only; rejection leaves existing content untouched.', 'igp-pro' ); ?></p>
		<?php if ( '' !== $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( igp_pro_ai_changeset_notice_text( $notice ) ); ?></p></div>
		<?php endif; ?>
		<?php
		if ( '' !== $view_id ) {
			igp_pro_render_ai_changeset_detail( $view_id );
		} else {
			igp_pro_render_ai_changeset_list();
		}
		?>
	</div>
	<?php
}

function igp_pro_render_ai_changeset_list(): void {
	$items = IGP_AI_Copilot_Changeset::list( array( 'limit' => 100 ) );
	?>
	<table class="widefat striped">
		<thead><tr><th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Title', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Target', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Source', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Updated', 'igp-pro' ); ?></th><th><?php esc_html_e( 'Actions', 'igp-pro' ); ?></th></tr></thead>
		<tbody>
		<?php if ( empty( $items ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No AI changesets found.', 'igp-pro' ); ?></td></tr>
		<?php endif; ?>
		<?php foreach ( $items as $item ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $item['status'] ); ?></strong></td>
				<td><?php echo esc_html( $item['title'] ?: $item['changeset_id'] ); ?></td>
				<td><?php echo $item['target_post_id'] ? esc_html( '#' . $item['target_post_id'] . ' ' . $item['target_post_title'] ) : esc_html__( 'New draft', 'igp-pro' ); ?></td>
				<td><?php echo esc_html( $item['source'] ); ?></td>
				<td><?php echo esc_html( $item['updated_at'] ); ?></td>
				<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=igp-pro-ai-changesets&changeset_id=' . rawurlencode( $item['changeset_id'] ) ) ); ?>"><?php esc_html_e( 'Review', 'igp-pro' ); ?></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

function igp_pro_render_ai_changeset_detail( string $changeset_id ): void {
	$record = IGP_AI_Copilot_Changeset::get( $changeset_id );
	if ( is_wp_error( $record ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $record->get_error_message() ) . '</p></div>';
		return;
	}

	$summary = IGP_AI_Copilot_Changeset::summarize( $record );
	$current_graph = array();
	if ( ! empty( $summary['target_post_id'] ) && function_exists( 'igp_pro_load_content_graph' ) ) {
		$current_graph = igp_pro_load_content_graph( absint( $summary['target_post_id'] ) );
		if ( is_wp_error( $current_graph ) ) {
			$current_graph = array( 'error' => $current_graph->get_error_message() );
		}
	} elseif ( ! empty( $record['original_graph'] ) && is_array( $record['original_graph'] ) ) {
		$current_graph = $record['original_graph'];
	}
	$proposed_graph = isset( $record['content_graph'] ) && is_array( $record['content_graph'] ) ? $record['content_graph'] : array();
	?>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=igp-pro-ai-changesets' ) ); ?>">&larr; <?php esc_html_e( 'Back to changesets', 'igp-pro' ); ?></a></p>
	<div class="igp-pro-admin-card">
		<h2><?php echo esc_html( $summary['title'] ?: $summary['changeset_id'] ); ?></h2>
		<table class="widefat striped"><tbody>
			<tr><th><?php esc_html_e( 'Changeset ID', 'igp-pro' ); ?></th><td><code><?php echo esc_html( $summary['changeset_id'] ); ?></code></td></tr>
			<tr><th><?php esc_html_e( 'Status', 'igp-pro' ); ?></th><td><?php echo esc_html( $summary['status'] ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Target', 'igp-pro' ); ?></th><td><?php echo $summary['target_post_id'] ? esc_html( '#' . $summary['target_post_id'] . ' ' . $summary['target_post_title'] ) : esc_html__( 'New draft', 'igp-pro' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Snapshot', 'igp-pro' ); ?></th><td><code><?php echo esc_html( $summary['snapshot_id'] ); ?></code></td></tr>
			<tr><th><?php esc_html_e( 'Approval Snapshot', 'igp-pro' ); ?></th><td><code><?php echo esc_html( $summary['approval_snapshot_id'] ); ?></code></td></tr>
		</tbody></table>
		<div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
			<?php if ( 'pending' === $summary['status'] ) : ?>
				<?php igp_pro_ai_changeset_action_form( 'approve', $summary['changeset_id'], __( 'Approve and Save Draft', 'igp-pro' ), 'button-primary' ); ?>
				<?php igp_pro_ai_changeset_action_form( 'reject', $summary['changeset_id'], __( 'Reject', 'igp-pro' ), 'button-secondary' ); ?>
			<?php endif; ?>
			<?php if ( 'approved' === $summary['status'] ) : ?>
				<?php igp_pro_ai_changeset_action_form( 'rollback', $summary['changeset_id'], __( 'Rollback', 'igp-pro' ), 'button-secondary' ); ?>
				<?php if ( ! empty( $summary['edit_link'] ) ) : ?><a class="button" href="<?php echo esc_url( $summary['edit_link'] ); ?>"><?php esc_html_e( 'Open Approved Draft', 'igp-pro' ); ?></a><?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="igp-ai-copilot-grid" style="margin-top:20px">
		<section class="igp-pro-admin-card"><h2><?php esc_html_e( 'Current / Original Graph', 'igp-pro' ); ?></h2><pre style="max-height:520px;overflow:auto"><?php echo esc_html( wp_json_encode( $current_graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre></section>
		<section class="igp-pro-admin-card"><h2><?php esc_html_e( 'Proposed Graph', 'igp-pro' ); ?></h2><pre style="max-height:520px;overflow:auto"><?php echo esc_html( wp_json_encode( $proposed_graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre></section>
	</div>
	<div class="igp-ai-copilot-grid" style="margin-top:20px">
		<section class="igp-pro-admin-card"><h2><?php esc_html_e( 'Validation Result', 'igp-pro' ); ?></h2><pre><?php echo esc_html( wp_json_encode( $summary['validation_result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre></section>
		<section class="igp-pro-admin-card"><h2><?php esc_html_e( 'Mapping Report', 'igp-pro' ); ?></h2><pre><?php echo esc_html( wp_json_encode( $summary['mapping_report'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre></section>
	</div>
	<?php
}

function igp_pro_ai_changeset_action_form( string $action, string $changeset_id, string $label, string $button_class = 'button-secondary' ): void {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="igp_ai_changeset_<?php echo esc_attr( $action ); ?>">
		<input type="hidden" name="changeset_id" value="<?php echo esc_attr( $changeset_id ); ?>">
		<?php wp_nonce_field( 'igp_ai_changeset_' . $action . '_' . $changeset_id ); ?>
		<button type="submit" class="button <?php echo esc_attr( $button_class ); ?>"><?php echo esc_html( $label ); ?></button>
	</form>
	<?php
}

function igp_pro_handle_ai_changeset_approve(): void { igp_pro_handle_ai_changeset_action( 'approve' ); }
function igp_pro_handle_ai_changeset_reject(): void { igp_pro_handle_ai_changeset_action( 'reject' ); }
function igp_pro_handle_ai_changeset_rollback(): void { igp_pro_handle_ai_changeset_action( 'rollback' ); }

function igp_pro_handle_ai_changeset_action( string $action ): void {
	if ( ! current_user_can( function_exists( 'igp_pro_get_surface_capability' ) ? igp_pro_get_surface_capability( 'ai_copilot' ) : 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to review IGP AI changesets.', 'igp-pro' ) );
	}
	$changeset_id = isset( $_POST['changeset_id'] ) ? sanitize_key( (string) wp_unslash( $_POST['changeset_id'] ) ) : '';
	check_admin_referer( 'igp_ai_changeset_' . $action . '_' . $changeset_id );
	if ( ! class_exists( 'IGP_AI_Copilot_Changeset' ) ) {
		wp_die( esc_html__( 'AI changeset service is unavailable.', 'igp-pro' ) );
	}
	if ( 'approve' === $action ) {
		$result = IGP_AI_Copilot_Changeset::approve( $changeset_id );
	} elseif ( 'reject' === $action ) {
		$result = IGP_AI_Copilot_Changeset::reject( $changeset_id );
	} else {
		$result = IGP_AI_Copilot_Changeset::rollback( $changeset_id, 'safe' );
	}
	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ) );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=igp-pro-ai-changesets&changeset_id=' . rawurlencode( $changeset_id ) . '&igp_notice=' . rawurlencode( $action . '_complete' ) ) );
	exit;
}

function igp_pro_ai_changeset_notice_text( string $notice ): string {
	$map = array(
		'approve_complete'  => __( 'Changeset approved and saved through the controlled draft-safe save path.', 'igp-pro' ),
		'reject_complete'   => __( 'Changeset rejected. Existing content was not changed.', 'igp-pro' ),
		'rollback_complete' => __( 'Changeset rollback completed.', 'igp-pro' ),
	);
	return $map[ $notice ] ?? __( 'Changeset action completed.', 'igp-pro' );
}
