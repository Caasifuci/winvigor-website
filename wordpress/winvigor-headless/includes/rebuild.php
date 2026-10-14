<?php
/**
 * Rebuild trigger. Any publish, update, unpublish or delete of site content
 * calls the Cloudflare deploy hook once, at the end of that request.
 * Calls are throttled to one per WV_REBUILD_THROTTLE seconds; a change made
 * inside that window queues one follow-up call, so the last edit is always
 * built. No server cron job is required.
 */
defined( 'ABSPATH' ) || exit;

add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( ! in_array( $post->post_type, wv_content_post_types(), true ) ) {
			return;
		}
		if ( 'publish' === $new || 'publish' === $old ) {
			wv_schedule_rebuild( sprintf( '%s "%s" %s', $post->post_type, $post->post_title, 'publish' === $new ? 'published/updated' : 'unpublished' ) );
		}
	},
	10,
	3
);

add_action(
	'before_delete_post',
	function ( $post_id ) {
		$post = get_post( $post_id );
		if ( $post && 'publish' === $post->post_status && in_array( $post->post_type, wv_content_post_types(), true ) ) {
			wv_schedule_rebuild( 'deleted ' . $post->post_type );
		}
	}
);

// Meta changes made through the REST API or MCP after the status change.
add_action(
	'updated_post_meta',
	function ( $meta_id, $post_id, $key ) {
		if ( str_starts_with( (string) $key, 'wv_' ) && 'publish' === get_post_status( $post_id ) && in_array( get_post_type( $post_id ), wv_content_post_types(), true ) ) {
			wv_schedule_rebuild( 'fields updated' );
		}
	},
	10,
	3
);

function wv_schedule_rebuild( string $reason = '' ): void {
	if ( ! wv_config( 'WV_DEPLOY_HOOK' ) ) {
		return;
	}
	update_option( 'wv_rebuild_reason', $reason, false );
	if ( empty( $GLOBALS['wv_rebuild_pending'] ) ) {
		$GLOBALS['wv_rebuild_pending'] = true;
		add_action( 'shutdown', 'wv_rebuild_on_shutdown' );
	}
}

/** Runs once at the end of a request that changed published content. */
function wv_rebuild_on_shutdown(): void {
	$throttle = (int) wv_config( 'WV_REBUILD_THROTTLE' );
	$last     = (int) get_transient( 'wv_rebuild_lock' );
	if ( $last && ( time() - $last ) < $throttle ) {
		// Too soon after the previous build: queue one follow-up.
		if ( ! wp_next_scheduled( 'wv_run_rebuild' ) ) {
			wp_schedule_single_event( $last + $throttle + 1, 'wv_run_rebuild' );
		}
		return;
	}
	wv_trigger_rebuild();
}

add_action( 'wv_run_rebuild', 'wv_trigger_rebuild' );

/** Call the deploy hook now. Returns true on success. */
function wv_trigger_rebuild(): bool {
	$hook = (string) wv_config( 'WV_DEPLOY_HOOK' );
	if ( ! $hook ) {
		return false;
	}
	set_transient( 'wv_rebuild_lock', time(), HOUR_IN_SECONDS );
	wp_clear_scheduled_hook( 'wv_run_rebuild' );
	$res = wp_remote_post( $hook, array( 'timeout' => 8 ) );
	$ok  = ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) < 300;
	update_option(
		'wv_last_rebuild',
		array(
			'time'   => time(),
			'ok'     => $ok,
			'reason' => get_option( 'wv_rebuild_reason', '' ),
			'error'  => is_wp_error( $res ) ? $res->get_error_message() : ( $ok ? '' : 'HTTP ' . wp_remote_retrieve_response_code( $res ) ),
		),
		false
	);
	return $ok;
}

/* Admin bar: status and a manual "Rebuild site now" action. */
add_action(
	'admin_bar_menu',
	function ( WP_Admin_Bar $bar ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$last  = get_option( 'wv_last_rebuild' );
		$queued = wp_next_scheduled( 'wv_run_rebuild' );
		$title = ! wv_config( 'WV_DEPLOY_HOOK' ) ? 'Site rebuild: not configured'
			: ( $queued ? 'Site rebuild: queued' : ( $last ? 'Site rebuilt ' . human_time_diff( $last['time'] ) . ' ago' . ( $last['ok'] ? '' : ' (failed)' ) : 'Site rebuild: idle' ) );
		$bar->add_node( array( 'id' => 'wv-rebuild', 'title' => $title ) );
		if ( wv_config( 'WV_DEPLOY_HOOK' ) ) {
			$bar->add_node(
				array(
					'id'     => 'wv-rebuild-now',
					'parent' => 'wv-rebuild',
					'title'  => 'Rebuild site now',
					'href'   => wp_nonce_url( admin_url( 'admin-post.php?action=wv_rebuild_now' ), 'wv_rebuild_now' ),
				)
			);
		}
		$bar->add_node( array( 'id' => 'wv-view-site', 'parent' => 'wv-rebuild', 'title' => 'View public site', 'href' => wv_site_url( '/' ) ) );
	},
	100
);

add_action(
	'admin_post_wv_rebuild_now',
	function () {
		check_admin_referer( 'wv_rebuild_now' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Not allowed.' );
		}
		update_option( 'wv_rebuild_reason', 'manual', false );
		wv_trigger_rebuild();
		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}
);
