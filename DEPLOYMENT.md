# Deployment runbook

How www.winvigor.com is built, hosted and edited.

```
 Editors / Claude (MCP)                         Visitors
         │                                          │
         ▼                                          ▼
 cms.winvigor.com  ── publish ──► deploy hook ──► Cloudflare Workers ─► www.winvigor.com
 WordPress on ChemiCloud          (rebuild)       builds Astro from        static, global CDN
 (headless, noindex)  ◄── REST API at build time ─┘ GitHub + WP content
         ▲
         └── contact form posts enquiries here (stored + emailed)
```

| Piece | Where | Purpose |
|---|---|---|
| Code | GitHub `winvigor-website` (private) | Design, layout, components. Pushing to `main` deploys. |
| Content | WordPress at `cms.winvigor.com` (ChemiCloud) | Insights, case studies, talent board, open roles, delivery log, enquiries |
| Public site | Cloudflare Workers (static assets) | Builds the Astro site and serves it at `www.winvigor.com` |
| DNS | Cloudflare | `cms` → ChemiCloud; `www` and apex → the Worker (at launch) |
| Claude access | MCP Adapter plugin + application password | Claude reads and writes content through the Winvigor abilities |

The current site stays live until the launch step. Nothing below touches email (MX/TXT records).

---

## Phase 1: GitHub (you, about 10 minutes)

1. Sign in or create an account at https://github.com. An organisation named `winvigor` is optional but tidier for a company.
2. Create a **private** repository named `winvigor-website`. Leave it **empty**: no README, licence or .gitignore.
3. Tell Claude the full name, e.g. `winvigor/winvigor-website`. Claude pushes the code. If Claude asks for access, approve the GitHub connection prompt.

## Phase 2: WordPress CMS on ChemiCloud (you, about 20 minutes)

1. **DNS (Cloudflare):** DNS → Add record → Type `A`, Name `cms`, IPv4 = your ChemiCloud server IP (cPanel home → "Shared IP Address"), Proxy **on**.
2. **Subdomain (cPanel):** Domains → Create a new domain → `cms.winvigor.com`.
3. **Install WordPress (cPanel):** WordPress Manager / Softaculous → install on `cms.winvigor.com`.
   - Admin username: anything except `admin`. Use a long unique password.
   - Site title: `Winvigor CMS`.
4. **SSL:** cPanel → SSL/TLS Status → Run AutoSSL for `cms.winvigor.com`. Cloudflare SSL/TLS mode should be **Full (strict)**.
   If AutoSSL fails, switch the `cms` DNS record to "DNS only" (grey cloud), run AutoSSL again, then switch back.
5. **Permalinks:** WordPress → Settings → Permalinks → **Post name** → Save.
6. **Plugins:** Plugins → Add New → Upload Plugin, then activate each:
   - `mcp-adapter-0.7.0.zip` (official WordPress MCP Adapter, dependencies included)
   - `winvigor-headless-1.2.0.zip`
   Remove the default plugins you don't use (Hello Dolly, Akismet).
7. **Plugin settings:** WordPress → **Settings** → **Winvigor website**. Fill in **Send enquiries to** (`services@winvigor.com`) and **Public site address** (your `*.workers.dev` address until launch, then `https://www.winvigor.com`). The deploy hook is added in Phase 3. The page also shows a setup checklist.
   No `wp-config.php` editing and no cron job are needed. (Optional, for very low-traffic CMSs: a cPanel cron job running `wp-cron.php` every 5 minutes makes queued follow-up rebuilds fire promptly.)
8. **A user for Claude:** Users → Add New → username `claude`, role **Editor**. Then edit that user → Application Passwords → name `Claude MCP` → Add. **Copy the password now**; WordPress shows it once.
   An Editor can manage content but cannot install plugins or change settings. Revoke the password at any time to cut Claude's access.
9. **Check:** open `https://cms.winvigor.com/wp-json/wp/v2/talent`. You should see `[]`. Opening `https://cms.winvigor.com/` should redirect to the public site.

## Phase 3: Cloudflare Workers (you, about 15 minutes)

The site is deployed as a Cloudflare **Worker with static assets** (`wrangler.jsonc` in the repository). Cloudflare builds it from GitHub on every push to `main`.

1. Cloudflare dashboard → **Compute (Workers)** → **Workers & Pages** → **Create application** → **Import a repository** (Git) → choose `Caasifuci/winvigor-website`.
2. Project/Worker name `winvigor-website`. Build command `npm run build`. Deploy command: leave the default `npx wrangler deploy`. Root directory: leave empty.
3. Open the Worker → **Settings** → **Build** → **Build variables and secrets** → add (these are needed at build time, not runtime):
   | Name | Value |
   |---|---|
   | `NODE_VERSION` | `22` |
   | `PUBLIC_SHOW_SAMPLES` | `true` |
   | `PUBLIC_FORM_ENDPOINT` | `https://cms.winvigor.com/wp-json/winvigor/v1/enquiry` |

   This first build uses the sample content in the repository, so you can review the design straight away.
4. **Deployments** tab → latest build → **Retry build** (or push any commit). The site appears at `https://winvigor-website.<your-subdomain>.workers.dev`.
5. Once Claude has loaded content into WordPress (Phase 5), add two more build variables and rebuild:
   | Name | Value |
   |---|---|
   | `CONTENT_SOURCE` | `wordpress` |
   | `WP_API_URL` | `https://cms.winvigor.com/wp-json` |
6. **Deploy hook:** Worker → **Settings** → **Builds** → **Deploy Hooks** → name `WordPress`, branch `main` → **Create** → copy the URL (`https://api.cloudflare.com/client/v4/workers/builds/deploy_hooks/…`).
   - WordPress → **Settings** → **Winvigor website** → paste it into **Cloudflare deploy hook** → **Save settings** → **Rebuild site now**. The checklist should show "Last rebuild call succeeded".
   - Optional, weekly rebuild: GitHub repo → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**, name `CF_DEPLOY_HOOK`, value the same URL.
7. Optional spam protection: Cloudflare → Turnstile → Add site (`www.winvigor.com`) → put the **site key** in the build variables as `PUBLIC_TURNSTILE_SITE_KEY` and the **secret key** in the **Turnstile secret key** field on WordPress → Settings → Winvigor website.

## Phase 4: Connect Claude to WordPress (you, about 10 minutes)

**Claude Desktop (recommended).** Needs Node.js 22 or newer on your computer (https://nodejs.org, LTS installer).

1. Claude Desktop → Settings → Developer → **Edit Config**. This opens `claude_desktop_config.json`.
2. Add the `winvigor-cms` server (merge with anything already in `mcpServers`):
   ```json
   {
     "mcpServers": {
       "winvigor-cms": {
         "command": "npx",
         "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
         "env": {
           "WP_API_URL": "https://cms.winvigor.com/wp-json/mcp/mcp-adapter-default-server",
           "WP_API_USERNAME": "claude",
           "WP_API_PASSWORD": "the application password from Phase 2, step 9",
           "OAUTH_ENABLED": "false"
         }
       }
     }
   }
   ```
3. Quit and reopen Claude Desktop. In a chat, ask Claude for the "Winvigor website status". It should report content counts.

**Claude on the web (alternative).** Only if your account shows **Request headers** when adding a custom connector: Customize → Connectors → Add custom connector → URL `https://cms.winvigor.com/wp-json/mcp/mcp-adapter-default-server` → Authentication **No sign-in** → Request header `authorization` = `Basic ` followed by base64 of `claude:APPLICATION_PASSWORD`.

What Claude can do through this connection (and nothing else):

| Ability | Effect |
|---|---|
| `winvigor/site-status` | Content counts, last rebuild, house rules |
| `winvigor/list-content`, `winvigor/get-content` | Read insights, case studies, talent, roles, log |
| `winvigor/save-insight`, `save-case-study`, `save-talent`, `save-role`, `save-log` | Create or update; draft or publish |
| `winvigor/unpublish-content` | Move to draft (never deletes) |
| `winvigor/rebuild-site` | Rebuild now |

All writes pass the same validation and public-content guard as the admin screens: anything mentioning security clearance or defence work is saved as a draft and flagged. The one exception is open roles, which may state a required clearance level in their **Security clearance** field (never on talent profiles).

## Phase 5: Content and launch

1. Claude loads the content into WordPress through MCP. You review it in the WordPress admin.
2. Replace sample content with real material and untick **Sample content** on each item. Then set `PUBLIC_SHOW_SAMPLES=false` in Pages.
3. Add privacy and cookie pages (EU/GDPR), the LinkedIn URL and the VAT number.
4. **Go live:** Worker → Settings → **Domains & Routes** → Add → **Custom domain** → `www.winvigor.com`, then `winvigor.com`. Cloudflare creates those two DNS records automatically (remove any old A/CNAME records for those two names first) and leaves MX/TXT alone.
   - Redirect apex to www: Cloudflare → Rules → Redirect Rules → "Redirect from root to WWW" template.
   - WordPress → Settings → Winvigor website → set **Public site address** to `https://www.winvigor.com`.
5. Check that email to services@winvigor.com still arrives, and send a test enquiry through the contact form.
6. Retire the old site's WordPress install once everything is confirmed.

---

## Day to day

- **Publish in WordPress** → site rebuilds automatically (about 2 minutes). The admin bar shows "Site rebuilt N min ago" and has **Rebuild site now**.
- **Ask Claude** to add a talent profile, publish a case study or post a log entry. It works through the abilities above.
- **Design or code changes** → Claude edits the GitHub repo; Cloudflare builds and deploys every push to `main` (other branches get preview URLs if enabled under Settings → Build).
- **Enquiries** arrive by email and are also kept under WordPress → Enquiries.

## Troubleshooting

| Symptom | Check |
|---|---|
| Build fails "WordPress … HTTP 403" | Cloudflare Security → WAF: Bot Fight Mode or a rule is challenging API requests. Add a custom rule to **Skip** security for `cms.winvigor.com/wp-json/*`. |
| Publishing doesn't rebuild | WordPress → Settings → Winvigor website: is the deploy hook filled in, and what does "Last rebuild call" say? |
| Claude can't connect | Node.js 22+ installed? Application password correct (spaces are fine)? Permalinks set to Post name? |
| Enquiry emails not arriving | ChemiCloud mail sending: add an SMTP plugin (e.g. FluentSMTP) using the services@ mailbox. Enquiries are still stored under WordPress → Enquiries. |
| Old URL 404s | Add it to `public/_redirects`. |
