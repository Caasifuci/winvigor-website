<?php
/**
 * Settings → Winvigor website: edit configuration without touching
 * wp-config.php, test the rebuild, and see a setup checklist.
 */
defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Winvigor website', 'Winvigor website', 'manage_options', 'winvigor-website', 'wv_render_settings_page' );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( WV_PLUGIN_DIR . 'winvigor-headless.php' ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=winvigor-website' ) ) . '">Settings</a>' );
		return $links;
	}
);

add_action(
	'admin_post_wv_save_settings',
	function () {
		check_admin_referer( 'wv_save_settings' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		$in     = wp_unslash( $_POST['wv'] ?? array() ); // phpcs:ignore
		$saved  = (array) get_option( 'wv_settings', array() );
		$errors = array();

		$url = trim( (string) ( $in['site_url'] ?? '' ) );
		if ( '' === $url || preg_match( '#^https://[^\s/]+\.[^\s/]+#', $url ) ) {
			$saved['site_url'] = untrailingslashit( esc_url_raw( $url ) );
		} else {
			$errors[] = 'Public site address must start with https://';
		}

		$hook = trim( (string) ( $in['deploy_hook'] ?? '' ) );
		if ( '' === $hook || str_starts_with( $hook, 'https://api.cloudflare.com/' ) ) {
			$saved['deploy_hook'] = esc_url_raw( $hook );
		} else {
			$errors[] = 'The deploy hook must be the URL Cloudflare gives you, starting with https://api.cloudflare.com/';
		}

		$email = trim( (string) ( $in['enquiry_to'] ?? '' ) );
		if ( '' === $email || is_email( $email ) ) {
			$saved['enquiry_to'] = sanitize_email( $email );
		} else {
			$errors[] = 'Enquiry email is not a valid address.';
		}

		$secret = trim( (string) ( $in['turnstile_secret'] ?? '' ) );
		if ( '••••••••' !== $secret ) { // unchanged placeholder keeps the stored secret
			$saved['turnstile_secret'] = sanitize_text_field( $secret );
		}

		update_option( 'wv_settings', $saved, false );
		set_transient( 'wv_settings_notice_' . get_current_user_id(), $errors ? array( 'error', $errors ) : array( 'success', array( 'Settings saved.' ) ), 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=winvigor-website' ) );
		exit;
	}
);

function wv_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$notice = get_transient( 'wv_settings_notice_' . get_current_user_id() );
	delete_transient( 'wv_settings_notice_' . get_current_user_id() );
	$saved = (array) get_option( 'wv_settings', array() );
	$last  = get_option( 'wv_last_rebuild' );

	$field = function ( string $const, string $key, string $label, string $help, string $type = 'text', string $placeholder = '' ) use ( $saved ) {
		$locked = defined( $const );
		$value  = $locked ? (string) constant( $const ) : (string) ( $saved[ $key ] ?? '' );
		if ( 'password' === $type && '' !== $value ) {
			$value = '••••••••';
		}
		printf(
			'<tr><th scope="row"><label for="wv-%1$s">%2$s</label></th><td><input type="%3$s" id="wv-%1$s" name="wv[%1$s]" value="%4$s" placeholder="%5$s" class="large-text code" %6$s><p class="description">%7$s%8$s</p></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( 'password' === $type ? 'text' : $type ),
			esc_attr( $value ),
			esc_attr( $placeholder ),
			$locked ? 'disabled' : '',
			esc_html( $help ),
			$locked ? ' <strong>Set in wp-config.php (' . esc_html( $const ) . '), so it can\'t be changed here.</strong>' : ''
		);
	};

	// Setup checklist.
	$checks = array(
		array( 'Permalinks set to "Post name"', '/%postname%/' === get_option( 'permalink_structure' ), admin_url( 'options-permalink.php' ) ),
		array( 'MCP Adapter plugin active', function_exists( 'is_plugin_active' ) ? is_plugin_active( 'mcp-adapter/mcp-adapter.php' ) || class_exists( '\WP\MCP\Core\McpAdapter' ) : class_exists( '\WP\MCP\Core\McpAdapter' ), admin_url( 'plugins.php' ) ),
		array( 'Deploy hook added (below)', (bool) wv_config( 'WV_DEPLOY_HOOK' ), '' ),
		array( 'Last rebuild call succeeded', $last && ! empty( $last['ok'] ), '' ),
		array( 'Editor user "claude" exists for the Claude connection', (bool) get_user_by( 'login', 'claude' ), admin_url( 'user-new.php' ) ),
	);
	?>
	<div class="wrap">
		<h1>Winvigor website</h1>
		<p>This WordPress is the content back end for <a href="<?php echo esc_url( wv_site_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wv_site_url( '/' ) ); ?></a>. Publishing here rebuilds the public site automatically.</p>

		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notice[0] ); ?>"><p><?php echo implode( '<br>', array_map( 'esc_html', $notice[1] ) ); // phpcs:ignore ?></p></div>
		<?php endif; ?>

		<h2>Setup checklist</h2>
		<table class="widefat striped" style="max-width:720px">
			<tbody>
			<?php foreach ( $checks as [ $label, $ok, $link ] ) : ?>
				<tr>
					<td style="width:2em"><?php echo $ok ? '<span style="color:#008a20">✔</span>' : '<span style="color:#b32d2e">✘</span>'; // phpcs:ignore ?></td>
					<td><?php echo esc_html( $label ); ?><?php if ( ! $ok && $link ) : ?> <a href="<?php echo esc_url( $link ); ?>">Fix</a><?php endif; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wv_save_settings">
			<?php wp_nonce_field( 'wv_save_settings' ); ?>
			<h2>Settings</h2>
			<table class="form-table" role="presentation">
				<tbody>
				<?php
				$field( 'WV_DEPLOY_HOOK', 'deploy_hook', 'Cloudflare deploy hook', 'From Cloudflare: Workers & Pages → winvigor-website → Settings → Builds → Deploy Hooks → Create. Paste the whole URL.', 'url', 'https://api.cloudflare.com/client/v4/workers/builds/deploy_hooks/…' );
				$field( 'WV_SITE_URL', 'site_url', 'Public site address', 'Where visitors to this WordPress are sent, and where "View" links point. Use the workers.dev address until launch, then https://www.winvigor.com.', 'url', 'https://www.winvigor.com' );
				$field( 'WV_ENQUIRY_TO', 'enquiry_to', 'Send enquiries to', 'Contact form messages are emailed here (and kept under Enquiries).', 'email', 'services@winvigor.com' );
				$field( 'WV_TURNSTILE_SECRET', 'turnstile_secret', 'Turnstile secret key (optional)', 'Only if you enable Cloudflare Turnstile spam protection on the contact form.', 'password' );
				?>
				</tbody>
			</table>
			<?php submit_button( 'Save settings' ); ?>
		</form>

		<h2>Rebuild</h2>
		<p>
			<?php if ( $last ) : ?>
				Last rebuild call: <?php echo esc_html( human_time_diff( (int) $last['time'] ) ); ?> ago,
				<?php echo $last['ok'] ? '<strong style="color:#008a20">accepted by Cloudflare</strong>' : '<strong style="color:#b32d2e">failed: ' . esc_html( $last['error'] ) . '</strong>'; // phpcs:ignore ?>
				(<?php echo esc_html( $last['reason'] ); ?>).
			<?php else : ?>
				No rebuild has been triggered yet.
			<?php endif; ?>
		</p>
		<?php if ( wv_config( 'WV_DEPLOY_HOOK' ) ) : ?>
			<p><a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wv_rebuild_now' ), 'wv_rebuild_now' ) ); ?>">Rebuild site now</a></p>
		<?php endif; ?>
	</div>
	<?php
}
