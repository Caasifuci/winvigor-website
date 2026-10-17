/**
 * Post-build: add a Content-Security-Policy to dist/_headers.
 *
 * The site ships a few small inline scripts (the motion opt-in in <head>
 * and Astro's inlined component scripts). Rather than allowing all inline
 * script, this hashes each one in the built HTML so only those exact
 * scripts may run. Runs after every build, so hashes always match.
 */
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const dist = new URL('../dist/', import.meta.url).pathname;
const htmlFiles = (dir) =>
  readdirSync(dir).flatMap((f) => {
    const p = join(dir, f);
    return statSync(p).isDirectory() ? htmlFiles(p) : p.endsWith('.html') ? [p] : [];
  });

const hashes = new Set();
for (const file of htmlFiles(dist)) {
  const html = readFileSync(file, 'utf8');
  for (const [, attrs, body] of html.matchAll(/<script([^>]*)>([\s\S]*?)<\/script>/g)) {
    if (/\bsrc=/.test(attrs) || /application\/ld\+json/.test(attrs) || !body.trim()) continue;
    hashes.add(`'sha256-${createHash('sha256').update(body).digest('base64')}'`);
  }
}

const policy = [
  "default-src 'self'",
  `script-src 'self' ${[...hashes].join(' ')} https://static.cloudflareinsights.com`,
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' data:",
  "font-src 'self'",
  "connect-src 'self' https://cloudflareinsights.com",
  // The contact form posts to the WordPress CMS, which redirects back here.
  "form-action 'self' https://cms.winvigor.com mailto:",
  "frame-ancestors 'none'",
  "base-uri 'self'",
  "object-src 'none'",
  'upgrade-insecure-requests',
].join('; ');

const headersFile = join(dist, '_headers');
const headers = readFileSync(headersFile, 'utf8').replace(/^\/\*\n/m, `/*\n  Content-Security-Policy: ${policy}\n`);
writeFileSync(headersFile, headers);
console.log(`[csp] ${hashes.size} inline script hash(es) added to _headers`);
