import { getCollection } from 'astro:content';
import { site } from '@/data/site';

/** Published, sample-aware, newest first. */
export async function getInsights() {
  const all = await getCollection('insights', ({ data }) => !data.draft && (site.showSampleContent || !data.sample));
  return all.sort((a, b) => b.data.publishDate.valueOf() - a.data.publishDate.valueOf());
}

/** Published, sample-aware, ordered by `order` then date. */
export async function getCaseStudies() {
  const all = await getCollection('case-studies', ({ data }) => !data.draft && (site.showSampleContent || !data.sample));
  return all.sort((a, b) => a.data.order - b.data.order || b.data.publishDate.valueOf() - a.data.publishDate.valueOf());
}

const fmt = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
export const formatDate = (d: Date) => fmt.format(d);

/** Listed talent profiles, sample-aware. Available-now first, then by date. */
export async function getTalent() {
  const all = await getCollection('talent', ({ data }) => data.listed && (site.showSampleContent || !data.sample));
  const when = (d?: Date) => (d ? d.valueOf() : 0);
  return all.sort((a, b) => when(a.data.availableFrom) - when(b.data.availableFrom) || a.data.id.localeCompare(b.data.id));
}

/** Open roles at Winvigor, sample-aware. */
export async function getRoles() {
  return getCollection('roles', ({ data }) => data.open && (site.showSampleContent || !data.sample));
}

const monthFmt = new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

/**
 * Human availability label. Dates in the past (or none) read "Available now",
 * evaluated at build time, so rebuild at least weekly to keep it accurate.
 */
export function availability(from?: Date, now = new Date()) {
  if (!from || from <= now) return { now: true, label: 'Available now' };
  return { now: false, label: `From ${monthFmt.format(from)}` };
}

/** Delivery log entries, sample-aware, newest first. */
export async function getLog() {
  const all = await getCollection('log', ({ data }) => site.showSampleContent || !data.sample);
  return all.sort((a, b) => b.data.date.localeCompare(a.data.date));
}
