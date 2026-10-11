<?php
/**
 * Plugin Name:       Winvigor Headless
 * Description:       Turns this WordPress install into the content back end for www.winvigor.com (Astro on Cloudflare Pages): content types, fields, rebuild trigger, enquiry endpoint, MCP abilities and front-end lockdown.
 * Version:           1.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Winvigor Solutions
 * License:           GPL-2.0-or-later
 * Text Domain:       winvigor
 *
 * Configuration (add to wp-config.php, above "That's all, stop editing!"):
 *
 *   define( 'WV_SITE_URL', 'https://www.winvigor.com' );          // public Astro site
 *   define( 'WV_DEPLOY_HOOK', 'https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/…' );
 *   define( 'WV_ENQUIRY_TO', 'services@winvigor.com' );           // where enquiries are emailed
 *   define( 'WV_TURNSTILE_SECRET', '' );                          // optional, Cloudflare Turnstile
 *   define( 'WV_HEADLESS_REDIRECT', true );                       // send front-end visitors to WV_SITE_URL
 */

defined( 'ABSPATH' ) || exit;

define( 'WV_PLUGIN_VERSION', '1.1.0' );
define( 'WV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once WV_PLUGIN_DIR . 'includes/config.php';
require_once WV_PLUGIN_DIR . 'includes/fields.php';
require_once WV_PLUGIN_DIR . 'includes/guard.php';
require_once WV_PLUGIN_DIR . 'includes/content-types.php';
require_once WV_PLUGIN_DIR . 'includes/admin.php';
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
