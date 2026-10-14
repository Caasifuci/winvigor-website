<?php
/**
 * Configuration. Values come from, in order of priority:
 *   1. a constant in wp-config.php (e.g. define( 'WV_DEPLOY_HOOK', '…' );)
 *   2. Settings → Winvigor website in the WordPress admin
 *   3. the defaults below
 */
defined( 'ABSPATH' ) || exit;

/** Settings that can be edited on the admin settings page: constant name => option key. */
function wv_editable_settings(): array {
	return array(
		'WV_SITE_URL'         => 'site_url',
		'WV_DEPLOY_HOOK'      => 'deploy_hook',
		'WV_ENQUIRY_TO'       => 'enquiry_to',
		'WV_TURNSTILE_SECRET' => 'turnstile_secret',
	);
}

function wv_config( string $key ) {
	if ( defined( $key ) ) {
		return constant( $key );
	}
	$map = wv_editable_settings();
	if ( isset( $map[ $key ] ) ) {
		$saved = (array) get_option( 'wv_settings', array() );
		if ( isset( $saved[ $map[ $key ] ] ) && '' !== $saved[ $map[ $key ] ] ) {
			return $saved[ $map[ $key ] ];
		}
	}
	$defaults = array(
		'WV_SITE_URL'          => 'https://www.winvigor.com',
		'WV_DEPLOY_HOOK'       => '',
		'WV_ENQUIRY_TO'        => get_option( 'admin_email' ),
		'WV_TURNSTILE_SECRET'  => '',
		'WV_HEADLESS_REDIRECT' => true,
		'WV_REBUILD_THROTTLE'  => 30, // seconds between deploy-hook calls
	);
	return $defaults[ $key ] ?? null;
}

/** Public URL of the Astro site, without trailing slash. */
function wv_site_url( string $path = '' ): string {
	return untrailingslashit( (string) wv_config( 'WV_SITE_URL' ) ) . $path;
}
