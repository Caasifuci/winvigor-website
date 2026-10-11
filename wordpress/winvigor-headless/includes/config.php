<?php
/** Central access to configuration constants, with safe defaults. */
defined( 'ABSPATH' ) || exit;

function wv_config( string $key ) {
	$defaults = array(
		'WV_SITE_URL'          => 'https://www.winvigor.com',
		'WV_DEPLOY_HOOK'       => '',
		'WV_ENQUIRY_TO'        => get_option( 'admin_email' ),
		'WV_TURNSTILE_SECRET'  => '',
		'WV_HEADLESS_REDIRECT' => true,
		'WV_REBUILD_DELAY'     => 90, // seconds; batches several edits into one build
	);
	return defined( $key ) ? constant( $key ) : ( $defaults[ $key ] ?? null );
}

/** Public URL of the Astro site, without trailing slash. */
function wv_site_url( string $path = '' ): string {
	return untrailingslashit( (string) wv_config( 'WV_SITE_URL' ) ) . $path;
}
