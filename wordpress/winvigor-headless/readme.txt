=== Winvigor Headless ===
Requires at least: 6.9
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later

Content back end for www.winvigor.com.

== What it adds ==

* Content types: Case studies, Talent board, Open roles, Delivery log (plus Posts as Insights), with structured fields.
* Public-content guard: blocks publishing anything that mentions security clearance or defence work. Open roles may state a required clearance (NATO CONFIDENTIAL, NATO SECRET, COSMIC TOP SECRET, EU SECRET) in a dedicated field; talent profiles never may.
* Rebuild trigger: publishing calls the Cloudflare Pages deploy hook (batched). Admin bar shows status and "Rebuild site now".
* Enquiry endpoint for the contact form: POST /wp-json/winvigor/v1/enquiry. Stores enquiries privately and emails them.
* Headless lockdown: front end redirects to the public site, noindex everywhere, XML-RPC off, user listing hidden.
* Abilities for Claude via the MCP Adapter plugin: site-status, list-content, get-content, save-insight, save-case-study, save-talent, save-role, save-log, unpublish-content, rebuild-site.

== Configuration (wp-config.php) ==

define( 'WV_SITE_URL', 'https://www.winvigor.com' );
define( 'WV_DEPLOY_HOOK', 'https://api.cloudflare.com/client/v4/pages/webhooks/deploy_hooks/...' );
define( 'WV_ENQUIRY_TO', 'services@winvigor.com' );
define( 'WV_TURNSTILE_SECRET', '' ); // optional
define( 'DISABLE_WP_CRON', true );   // with a real server cron, see DEPLOYMENT.md
