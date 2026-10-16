=== Winvigor Headless ===
Requires at least: 6.9
Requires PHP: 8.0
Stable tag: 1.3.0
License: GPLv2 or later

Content back end for www.winvigor.com.

== What it adds ==

* Content types: Case studies, Talent board, Open roles, Delivery log (plus Posts as Insights), with structured fields.
* Public-content guard: blocks publishing anything that mentions security clearance or defence work. Open roles may state a required clearance (NATO CONFIDENTIAL, NATO SECRET, COSMIC TOP SECRET, EU SECRET) in a dedicated field; talent profiles never may.
* Rebuild trigger: publishing calls the Cloudflare deploy hook straight away (throttled to one call per 30 s). Admin bar and settings page show status and "Rebuild site now".
* Enquiry endpoint for the contact form: POST /wp-json/winvigor/v1/enquiry. Stores enquiries privately and emails them.
* Headless lockdown: front end redirects to the public site, noindex everywhere, XML-RPC off, user listing hidden.
* Abilities for Claude via the MCP Adapter plugin: site-status, list-content, get-content, save-insight, save-case-study, save-talent, save-role, save-log, unpublish-content, rebuild-site.

== Configuration ==

Settings → Winvigor website: Cloudflare deploy hook, public site address, enquiry email, optional Turnstile secret, plus a setup checklist and a "Rebuild site now" button.

Constants in wp-config.php (WV_SITE_URL, WV_DEPLOY_HOOK, WV_ENQUIRY_TO, WV_TURNSTILE_SECRET) override the admin settings if you prefer to fix them there.

No server cron job is needed: publishing triggers the rebuild at the end of the request, throttled to one call per 30 seconds with a queued follow-up.
