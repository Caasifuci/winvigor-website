/**
 * WordPress content loaders (Astro Content Layer).
 *
 * At build time these read published content from the headless WordPress
 * at WP_API_URL (e.g. https://cms.winvigor.com/wp-json) and hand it to the
 * same collection schemas the local files use, so pages don't care where
 * content came from.
 *
 * Environment:
 *   CONTENT_SOURCE=wordpress     switch collections from local files to WordPress
 *   WP_API_URL                   REST root, no trailing slash
 *   WP_API_USER / WP_API_APP_PASSWORD   optional; an application password for authenticated reads
 *
 * If WordPress is unreachable the build fails on purpose: Cloudflare then
 * keeps serving the previous deployment instead of publishing an empty site.
 */
import type { Loader } from 'astro/loaders';

type WpItem = {
  id: number;
  slug: string;
  date_gmt: string;
  modified_gmt: string;
  title: { rendered: string };
  content?: { rendered: string };
  excerpt?: { rendered: string };
  meta: Record<string, unknown>;
  _embedded?: { 'wp:term'?: Array<Array<{ taxonomy: string; name: string }>> };
};

const entities: Record<string, string> = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ', hellip: '…', ndash: '–', mdash: '—', rsquo: '’', lsquo: '‘', rdquo: '”', ldquo: '“' };

/** Decode the HTML entities WordPress puts in rendered titles. */
export function decode(text: string): string {
  return text
    .replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(Number(n)))
    .replace(/&#x([0-9a-f]+);/gi, (_, n) => String.fromCodePoint(parseInt(n, 16)))
    .replace(/&([a-z]+);/gi, (m, name) => entities[name.toLowerCase()] ?? m);
}

const stripTags = (html: string) => decode(html.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim());

function apiRoot(): string {
  const root = process.env.WP_API_URL;
  if (!root) throw new Error('CONTENT_SOURCE=wordpress but WP_API_URL is not set.');
  return root.replace(/\/$/, '');
}

function headers(): Record<string, string> {
  const user = process.env.WP_API_USER;
  const pass = process.env.WP_API_APP_PASSWORD;
  const h: Record<string, string> = { Accept: 'application/json', 'User-Agent': 'winvigor-astro-build' };
  if (user && pass) h.Authorization = `Basic ${Buffer.from(`${user}:${pass}`).toString('base64')}`;
  return h;
}

/** Fetch every published item of a REST collection, following pagination. */
async function fetchAll(route: string, params: Record<string, string> = {}): Promise<WpItem[]> {
  const items: WpItem[] = [];
  for (let page = 1; ; page++) {
    const url = new URL(`${apiRoot()}/wp/v2/${route}`);
    url.search = new URLSearchParams({ per_page: '100', page: String(page), status: 'publish', ...params }).toString();
    const res = await fetch(url, { headers: headers() });
    if (!res.ok) throw new Error(`WordPress ${route} page ${page}: HTTP ${res.status} ${await res.text().then((t) => t.slice(0, 200))}`);
    items.push(...((await res.json()) as WpItem[]));
    const totalPages = Number(res.headers.get('x-wp-totalpages') ?? 1);
    if (page >= totalPages) break;
  }
  return items;
}

const m = (item: WpItem, key: string) => item.meta?.[`wv_${key}`];
const str = (v: unknown) => (typeof v === 'string' ? v : '');
const arr = (v: unknown) => (Array.isArray(v) ? v : []);

/** Generic loader factory. `map` turns a WP item into { id, data, html? }. */
function wpLoader(
  name: string,
  route: string,
  map: (item: WpItem) => { id: string; data: Record<string, unknown>; html?: string },
  params: Record<string, string> = {},
): Loader {
  return {
    name: `wordpress-${name}`,
    load: async ({ store, parseData, generateDigest, logger }) => {
      const items = await fetchAll(route, params);
      store.clear();
      for (const item of items) {
        const { id, data: raw, html } = map(item);
        const data = await parseData({ id, data: raw });
        store.set({ id, data, digest: generateDigest({ raw, html }), ...(html !== undefined && { rendered: { html } }) });
      }
      logger.info(`Loaded ${items.length} ${name} from WordPress`);
    },
  };
}

export const wpInsights = () =>
  wpLoader(
    'insights',
    'posts',
    (p) => ({
      id: p.slug,
      html: p.content?.rendered ?? '',
      data: {
        title: decode(p.title.rendered),
        description: str(m(p, 'description')) || stripTags(p.excerpt?.rendered ?? '').slice(0, 220),
        publishDate: `${p.date_gmt}Z`,
        updatedDate: p.modified_gmt !== p.date_gmt ? `${p.modified_gmt}Z` : undefined,
        topics: (p._embedded?.['wp:term'] ?? []).flat().filter((t) => t.taxonomy === 'category' && t.name !== 'Uncategorized').map((t) => decode(t.name)),
        sample: m(p, 'sample') === true,
      },
    }),
    { _embed: 'wp:term' },
  );

export const wpCaseStudies = () =>
  wpLoader('case studies', 'case-studies', (p) => ({
    id: p.slug,
    html: p.content?.rendered ?? '',
    data: {
      title: decode(p.title.rendered),
      summary: str(m(p, 'summary')),
      client: str(m(p, 'client')),
      sector: str(m(p, 'sector')),
      services: arr(m(p, 'services')),
      duration: str(m(p, 'duration')) || undefined,
      publishDate: `${p.date_gmt}Z`,
      results: arr(m(p, 'results')),
      order: Number(m(p, 'order') ?? 100),
      featured: m(p, 'featured') === true,
      sample: m(p, 'sample') === true,
    },
  }));

export const wpTalent = () =>
  wpLoader('talent profiles', 'talent', (p) => {
    const ref = str(m(p, 'ref'));
    return {
      id: ref,
      data: {
        id: ref,
        role: decode(p.title.rendered),
        discipline: str(m(p, 'discipline')),
        seniority: str(m(p, 'seniority')),
        years: Number(m(p, 'years') ?? 0),
        skills: arr(m(p, 'skills')),
        languages: arr(m(p, 'languages')),
        location: str(m(p, 'location')),
        availableFrom: str(m(p, 'available_from')) || undefined,
        engagement: str(m(p, 'engagement')),
        summary: str(m(p, 'summary')),
        listed: m(p, 'listed') !== false,
        sample: m(p, 'sample') === true,
      },
    };
  });

export const wpRoles = () =>
  wpLoader('open roles', 'roles', (p) => ({
    id: p.slug,
    data: {
      id: p.slug,
      title: decode(p.title.rendered),
      discipline: str(m(p, 'discipline')),
      location: str(m(p, 'location')),
      type: str(m(p, 'role_type')),
      summary: str(m(p, 'summary')),
      clearance: str(m(p, 'clearance')) || undefined,
      clearanceStatus: str(m(p, 'clearance_status')) || undefined,
      open: m(p, 'open') !== false,
      sample: m(p, 'sample') === true,
    },
  }));

export const wpLog = () =>
  wpLoader('delivery log entries', 'delivery-log', (p) => ({
    id: String(p.id),
    data: {
      date: str(m(p, 'log_date')) || p.date_gmt.slice(0, 10),
      sector: str(m(p, 'sector')),
      entry: decode(p.title.rendered),
      sample: m(p, 'sample') === true,
    },
  }));
