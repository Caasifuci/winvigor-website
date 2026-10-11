<?php
/**
 * Headless lockdown. This WordPress serves editors and the API only:
 * - front-end page requests redirect to the public Astro site
 * - search engines are told not to index anything here
 * - XML-RPC is off; user listing via REST needs authentication
 */
defined( 'ABSPATH' ) || exit;

// Send front-end visitors to the public site (admin, login, REST, cron and AJAX are untouched).
add_action(
	'template_redirect',
	function () {
		if ( ! wv_config( 'WV_HEADLESS_REDIRECT' ) || is_user_logged_in() || is_preview() ) {
			return;
		}
		wp_redirect( wv_site_url( '/' ), 301 ); // phpcs:ignore WordPress.Security.SafeRedirect
		exit;
	}
);

// Never index the CMS.
add_filter( 'wp_robots', fn() => array( 'noindex' => true, 'nofollow' => true ) );
add_action( 'send_headers', fn() => header( 'X-Robots-Tag: noindex, nofollow', true ) );
add_filter( 'pre_option_blog_public', '__return_zero' );

// Disable XML-RPC (a common attack surface; not needed headless).
add_filter( 'xmlrpc_enabled', '__return_false' );

// Hide user enumeration from anonymous REST callers.
add_filter(
	'rest_endpoints',
	function ( array $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( str_starts_with( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
);

// "Preview" and "View" links in the admin point at the public site.
add_filter( 'post_type_link', 'wv_public_link', 10, 2 );
add_filter( 'post_link', 'wv_public_link', 10, 2 );
function wv_public_link( string $url, $post ): string {
	$post = get_post( $post );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return $url;
	}
	$map = array(
		'post'          => '/insights/' . $post->post_name,
		'wv_case_study' => '/case-studies/' . $post->post_name,
		'wv_talent'     => '/talent',
		'wv_role'       => '/careers#roles',
		'wv_log'        => '/',
	);
	return isset( $map[ $post->post_type ] ) ? wv_site_url( $map[ $post->post_type ] ) : $url;
}
