# Winvigor Solutions: website

Static Astro 7 site for www.winvigor.com, deployed as a Cloudflare Worker with static assets (`wrangler.jsonc`). Content comes from a headless WordPress at cms.winvigor.com (ChemiCloud), which Claude edits through MCP. Under 5 KB of client JS (hover prefetch, talent-board filters, contact form helpers).

**Setting up hosting, WordPress and the Claude connection: see [DEPLOYMENT.md](DEPLOYMENT.md).**

```bash
npm install
npm run dev       # http://localhost:4321
npm run build     # type-check + static build into dist/
npm run preview
```

Content source: by default the build uses the sample files in `src/content/`. With `CONTENT_SOURCE=wordpress` and `WP_API_URL` set (see `.env.example`) it reads WordPress instead. Pages are identical either way.

Deploy `dist/` to any static host (Cloudflare Pages, Netlify, Vercel, S3 + CloudFront).

## Structure

```
src/
  layouts/              BaseLayout (shell, SEO, fonts), CaseStudyLayout
  components/
    ui/                 Button, Container, Typography: the only styling primitives
    blocks/             HeroSplit, Editorial, MetricGrid, ClientLogos,
                        TalentBoard, ProcessSteps, DeliveryLog
    navigation/         Header (logo = home link, no-JS mobile menu), Footer
    visuals/            Vector artwork built from the logo's 21° stroke
  content/
    insights/           Markdown/MDX articles
    case-studies/       Markdown/MDX engagements
    talent/profiles.json  Talent board (anonymised engineer profiles)
    roles/roles.json    Open roles for the Careers page
    log/log.json        Delivery log entries for the home page
  content.config.ts     Collection schemas (Zod)
  data/
    site.ts             Contact details, nav, services, client list, flags
    process.ts          Consulting, resourcing and hiring steps
    taxonomy.ts         Shared disciplines list
  lib/content.ts        Collection queries (draft/sample filtering, sorting)
  lib/wordpress.ts      WordPress REST loaders (used when CONTENT_SOURCE=wordpress)
wordpress/
  winvigor-headless/    WordPress plugin: content types, fields, rebuild hook,
                        enquiry endpoint, headless lockdown, MCP abilities
public/_headers, _redirects   Cloudflare security headers and 301s (honoured by Workers static assets)
wrangler.jsonc          Cloudflare Workers deployment config (serves ./dist)
.github/workflows/      Build check on every push; weekly rebuild
  pages/                index, who-we-are, what-we-do, talent, insights,
                        careers, contact, 404, insights/[slug], case-studies/[slug]
  styles/global.css     Design tokens, reset, prose styles
```

Navigation: WHO WE ARE · WHAT WE DO · INSIGHTS · CAREERS · CONTACT. The logo (far left) is the home link. The header action is **Available engineers** (`/talent`).

## Design system

| Token | Value | Use |
|---|---|---|
| `--ink` | `#0B3C49` | Primary dark teal blue: headings, dark bands, home hero |
| `--graphite` | `#1A2430` | Body copy |
| `--slate` | `#4E5B6A` | Secondary copy |
| `--rule` | `#DCE1E7` | Hairlines, card borders |
| `--fog` | `#F4F6F8` | Neutral surface |
| `--accent` | `#1E6BFF` | Electric blue: buttons, links, focus, highlights (white label 4.6:1) |
| `--accent-strong` | `#1656D6` | Hover, accent text on light surfaces |
| `--accent-on-ink` | `#7FA8FF` | Accent text and links on navy |

- **Plus Jakarta Sans** (variable) for headings H1–H3 and figures; **Inter** for body copy and UI labels. Both self-hosted through Astro's Fonts API with metric-matched fallbacks (no third-party requests, no layout shift).
- Fluid type scale `--step--1` … `--step-5`; all text goes through `<Typography variant>`. Uppercase is reserved for the navigation and footer headings.
- 12-column grid via `<Container grid>`; `Editorial` places heading in columns 1–5 and content in 7–12.
- **Motion** (`src/scripts/motion.ts`, `components/motion/Ambient.astro`): hero headings and strategic pillars slide up line by line from overflow masks (`data-reveal="lines"`, ease-out-expo `cubic-bezier(0.16, 1, 0.3, 1)`); teal bands carry a 20s slow-drift gradient, a creeping hairline grid and a low-opacity SVG constellation that drifts by pixels and leans toward the cursor. Paused off screen, static under `prefers-reduced-motion`, about 1 KB gzipped.
- **No photographs of people.** Hero artwork lives in `components/visuals/` and is drawn in SVG from the logo's slanted stroke (`geometry.ts`).
- Layouts follow two patterns: knowledge hub (Home, Who we are, What we do, Insights: hero, featured banner, card grids, "How can we help?") and employer page (Careers, Contact: navy hero with icon features, split rows, stats, numbered steps).

## Talent board

Profiles are edited in WordPress → Talent board (or `src/content/talent/profiles.json` for local builds). Each profile is anonymised (reference `WV-1234`, no names or photos). "Request profile" opens `/contact?profile=WV-1234`, which pre-fills the enquiry. Set `"listed": false` to hide a profile without deleting it. Availability is computed at build time from `availableFrom`, so **rebuild at least weekly** (a scheduled deploy hook works).

## Public-content rules

- Brand content (pages, case studies, insights, delivery log, talent profiles) never mentions security clearance or defence-sector work. The WordPress plugin blocks publishing it.
- **Exception: open roles.** A role may state the clearance the client requires (NATO CONFIDENTIAL, NATO SECRET, COSMIC TOP SECRET, EU SECRET) in its **Security clearance** field, with "Must hold" or "Must be eligible". It appears on the Careers page as a fact line. Defence/military wording remains blocked in roles.
- **Talent profiles never state an individual's clearance**: that would advertise a cleared person. Hiring managers state the level in their brief and are matched privately.
- Show, don't tell: prefer figures, dated log entries and case studies to adjectives.
- Name clients in case studies only with their approval.

## Sample content

Metrics, the delivery log, talent profiles, open roles, case studies and insights are **illustrative** and visibly tagged "Sample". Sample entries are also `noindex`.

- Before launch, replace sample items in WordPress (untick **Sample content**) and the `outcomes` array in `src/pages/index.astro`.
- To hide all sample content at once: `PUBLIC_SHOW_SAMPLES=false npm run build`.
- Case studies name clients through the `client` field. Use an anonymised description unless the client has approved being named.

## Before launch

- [x] Replace sample case studies and insights (now generic, no client-identifying details)
- [ ] Replace the home-page outcome figures and delivery log with real ones
- [ ] Set `PUBLIC_FORM_ENDPOINT` to the WordPress enquiry endpoint (DEPLOYMENT.md, Phase 3). Without it the form opens the visitor's mail client.
- [ ] Add the LinkedIn URL in `src/data/site.ts`
- [ ] Add approved client logo SVGs to `clients` in `src/data/site.ts` (names render as text until then)
- [ ] Confirm the production domain in `astro.config.mjs` (`site`) and `public/robots.txt`
- [x] Add a privacy notice page (EU/GDPR) and link it in the footer (`/privacy`, consent box on the contact form)
- [ ] Confirm the process steps in `src/data/process.ts` match how you actually work
- [x] Add the enterprise and VAT numbers in `src/data/site.ts` (`legal`)

## Migration notes

- Source: All-in-One WP Migration export, Astra 4.14 + Spectra blocks. Page copy was extracted from Spectra block attributes in the database and lightly edited for tone; facts (services, contact details, mission) are unchanged.
- Dropped: ZipWP placeholder team, testimonials and blog posts; all plugins (Spectra, SureForms, SureRank, SureTriggers, SureCookie, Sigmize, Zip AI).
- Redirects: `/home-2` → `/`, `/blog` → `/insights`, `/about` → `/who-we-are`, `/services` → `/what-we-do`.
