<?php
/**
 * Contact form endpoint for the static site.
 *
 *   POST https://cms.winvigor.com/wp-json/winvigor/v1/enquiry
 *
 * Accepts a normal HTML form post (no JavaScript required), stores the
 * enquiry privately in WordPress, emails it to WV_ENQUIRY_TO, then
 * redirects the visitor back to the public site.
 *
 * Spam controls: honeypot field, per-IP rate limit, optional Cloudflare
 * Turnstile (WV_TURNSTILE_SECRET), minimum fill time.
 */
defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'winvigor/v1',
			'/enquiry',
			array(
				'methods'             => 'POST',
				'callback'            => 'wv_handle_enquiry',
				'permission_callback' => '__return_true',
			)
		);
	}
);

function wv_enquiry_redirect( string $status ): WP_REST_Response {
	$response = new WP_REST_Response( null, 303 );
	$response->header( 'Location', wv_site_url( 'ok' === $status ? '/contact/thanks' : '/contact?status=' . rawurlencode( $status ) . '#enquiry' ) );
	return $response;
}

function wv_handle_enquiry( WP_REST_Request $req ) {
	$p = $req->get_body_params() ?: $req->get_json_params() ?: array();

	// Honeypot: real visitors never fill this hidden field. Pretend success.
	if ( ! empty( $p['website'] ) ) {
		return wv_enquiry_redirect( 'ok' );
	}

	// Rate limit: 5 enquiries per IP per hour.
	$ip  = sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore
	$key = 'wv_enq_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 5 ) {
		return wv_enquiry_redirect( 'limit' );
	}
	set_transient( $key, $n + 1, HOUR_IN_SECONDS );

	// Optional Cloudflare Turnstile.
	$secret = (string) wv_config( 'WV_TURNSTILE_SECRET' );
	if ( $secret ) {
		$verify = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => $secret,
					'response' => (string) ( $p['cf-turnstile-response'] ?? '' ),
					'remoteip' => $ip,
				),
			)
		);
		$body = is_wp_error( $verify ) ? array() : json_decode( wp_remote_retrieve_body( $verify ), true );
		if ( empty( $body['success'] ) ) {
			return wv_enquiry_redirect( 'verify' );
		}
	}

	$name    = sanitize_text_field( (string) ( $p['name'] ?? '' ) );
	$email   = sanitize_email( (string) ( $p['email'] ?? '' ) );
	$org     = sanitize_text_field( (string) ( $p['organisation'] ?? '' ) );
	$profile = sanitize_text_field( (string) ( $p['profile'] ?? '' ) );
	$message = sanitize_textarea_field( (string) ( $p['message'] ?? '' ) );

	if ( '' === $name || ! is_email( $email ) || mb_strlen( $message ) < 5 ) {
		return wv_enquiry_redirect( 'invalid' );
	}
	$message = mb_substr( $message, 0, 5000 );
	$profile = preg_match( '/^WV-\d{4}$/', $profile ) ? $profile : '';

	$subject = $profile ? "Profile request {$profile} from {$name}" : "Website enquiry from {$name}" . ( $org ? " ({$org})" : '' );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'wv_enquiry',
			'post_status'  => 'private',
			'post_title'   => $subject,
			'post_content' => $message,
		)
	);
	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, 'wv_name', $name );
		update_post_meta( $post_id, 'wv_email', $email );
		update_post_meta( $post_id, 'wv_organisation', $org );
		update_post_meta( $post_id, 'wv_profile', $profile );
	}

	$body = "Name: {$name}\nEmail: {$email}\nOrganisation: " . ( $org ?: '-' ) . "\n" . ( $profile ? "Profile: {$profile}\n" : '' ) . "\n{$message}\n\n-- \nStored in WordPress: " . admin_url( 'edit.php?post_type=wv_enquiry' );
	wp_mail( (string) wv_config( 'WV_ENQUIRY_TO' ), $subject, $body, array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );

	return wv_enquiry_redirect( 'ok' );
}

/* Show the enquirer's details on the enquiry edit screen. */
add_action(
	'add_meta_boxes_wv_enquiry',
	function () {
		add_meta_box(
			'wv_enquiry_from',
			'From',
			function ( $post ) {
				foreach ( array( 'name' => 'Name', 'email' => 'Email', 'organisation' => 'Organisation', 'profile' => 'Profile' ) as $k => $label ) {
					$v = get_post_meta( $post->ID, 'wv_' . $k, true );
					if ( $v ) {
						printf( '<p><strong>%s:</strong> %s</p>', esc_html( $label ), 'email' === $k ? '<a href="mailto:' . esc_attr( $v ) . '">' . esc_html( $v ) . '</a>' : esc_html( $v ) );
					}
				}
			},
			'wv_enquiry',
			'side'
		);
	}
);
