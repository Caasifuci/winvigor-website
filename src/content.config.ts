/**
 * Content collections. Content lives in src/content/<collection>/,
 * fully decoupled from presentation; schemas are the contract.
 *
 * `sample: true` marks illustrative content. It renders with a visible
 * "Sample" tag and is removed site-wide by PUBLIC_SHOW_SAMPLES=false.
 *
 * Public-content rule: never publish security clearance or defence-sector
 * details. The talent schema deliberately has no field for them.
 */
import { defineCollection } from 'astro:content';
import { file, glob } from 'astro/loaders';
import { z } from 'astro/zod';
import { disciplines } from './data/taxonomy';
import { wpCaseStudies, wpInsights, wpLog, wpRoles, wpTalent } from './lib/wordpress';

/**
 * Where content comes from. "local" (default) reads the files in
 * src/content/; "wordpress" reads the headless CMS at WP_API_URL.
 */
const fromWordPress = process.env.CONTENT_SOURCE === 'wordpress';

const insights = defineCollection({
  loader: fromWordPress ? wpInsights() : glob({ pattern: '**/*.{md,mdx}', base: './src/content/insights' }),
  schema: z.object({
    title: z.string().max(110),
    description: z.string().max(220),
    publishDate: z.coerce.date(),
    updatedDate: z.coerce.date().optional(),
    author: z.string().default('Winvigor Solutions'),
    topics: z.array(z.string()).default([]),
    sample: z.boolean().default(false),
    draft: z.boolean().default(false),
  }),
});

const caseStudies = defineCollection({
  loader: fromWordPress ? wpCaseStudies() : glob({ pattern: '**/*.{md,mdx}', base: './src/content/case-studies' }),
  schema: ({ image }) =>
    z.object({
      title: z.string().max(110),
      summary: z.string().max(260),
      /** How the client is named publicly. Use an anonymised description unless the client has approved naming. */
      client: z.string(),
      sector: z.string(),
      services: z.array(z.string()).min(1),
      duration: z.string().optional(),
      publishDate: z.coerce.date(),
      results: z
        .array(z.object({ value: z.string(), label: z.string(), detail: z.string().optional() }))
        .max(4)
        .default([]),
      cover: image().optional(),
      coverAlt: z.string().optional(),
      order: z.number().default(100),
      featured: z.boolean().default(false),
      sample: z.boolean().default(false),
      draft: z.boolean().default(false),
    }),
});


/**
 * Talent board: anonymised profiles of engineers available for placement.
 * Edited in WordPress (Talent board) or src/content/talent/profiles.json. No names, no photos,
 * no clearance or defence-sector information.
 */
const talent = defineCollection({
  loader: fromWordPress ? wpTalent() : file('./src/content/talent/profiles.json'),
  schema: z.object({
    id: z.string().regex(/^WV-\d{4}$/, 'Profile reference must look like WV-1234'),
    role: z.string(),
    discipline: z.enum(disciplines),
    seniority: z.enum(['Mid-level', 'Senior', 'Lead', 'Principal']),
    years: z.number().int().min(1),
    skills: z.array(z.string()).min(2).max(6),
    languages: z.array(z.string()).min(1),
    location: z.string(),
    /** ISO date or omitted for "available now". */
    availableFrom: z.coerce.date().optional(),
    engagement: z.enum(['Contract', 'Permanent', 'Contract or permanent']),
    summary: z.string().max(200),
    sample: z.boolean().default(false),
    listed: z.boolean().default(true),
  }),
});

/** Open roles at Winvigor, for candidates. */
const roles = defineCollection({
  loader: fromWordPress ? wpRoles() : file('./src/content/roles/roles.json'),
  schema: z.object({
    id: z.string(),
    title: z.string(),
    discipline: z.enum(disciplines),
    location: z.string(),
    type: z.enum(['Permanent', 'Contract', 'Permanent or contract']),
    summary: z.string().max(220),
    sample: z.boolean().default(false),
    open: z.boolean().default(true),
  }),
});

/** Delivery log: dated, factual entries from live engagements (home page). */
const log = defineCollection({
  loader: fromWordPress ? wpLog() : file('./src/content/log/log.json'),
  schema: z.object({
    date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
    sector: z.string(),
    entry: z.string().max(200),
    sample: z.boolean().default(false),
  }),
});

export const collections = { insights, 'case-studies': caseStudies, talent, roles, log };
