<?php
/**
 * Plugin Name:       Winvigor Headless
 * Description:       Turns this WordPress install into the content back end for www.winvigor.com (Astro on Cloudflare Pages): content types, fields, rebuild trigger, enquiry endpoint, MCP abilities and front-end lockdown.
 * Version:           1.3.1
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Winvigor Solutions
 * License:           GPL-2.0-or-later
 * Text Domain:       winvigor
 *
 * Configuration: Settings → Winvigor website in the WordPress admin.
 * Any value can instead be fixed in wp-config.php with a constant
 * (WV_SITE_URL, WV_DEPLOY_HOOK, WV_ENQUIRY_TO, WV_TURNSTILE_SECRET,
 * WV_HEADLESS_REDIRECT), which then overrides the admin setting.
 */

defined( 'ABSPATH' ) || exit;

define( 'WV_PLUGIN_VERSION', '1.3.1' );
define( 'WV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once WV_PLUGIN_DIR . 'includes/config.php';
require_once WV_PLUGIN_DIR . 'includes/fields.php';
require_once WV_PLUGIN_DIR . 'includes/guard.php';
require_once WV_PLUGIN_DIR . 'includes/content-types.php';
require_once WV_PLUGIN_DIR . 'includes/admin.php';
require_once WV_PLUGIN_DIR . 'includes/settings.php';
require_once WV_PLUGIN_DIR . 'includes/rebuild.php';
require_once WV_PLUGIN_DIR . 'includes/enquiry.php';
require_once WV_PLUGIN_DIR . 'includes/headless.php';
require_once WV_PLUGIN_DIR . 'includes/abilities.php';

register_activation_hook( __FILE__, function () {
	wv_register_content_types();
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'wv_run_rebuild' );
	flush_rewrite_rules();
} );
