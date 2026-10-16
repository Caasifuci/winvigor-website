// @ts-check
import { defineConfig, fontProviders } from 'astro/config';
import mdx from '@astrojs/mdx';
import sitemap from '@astrojs/sitemap';

export default defineConfig({
  site: 'https://www.winvigor.com',
  trailingSlash: 'never',
  build: { format: 'directory', inlineStylesheets: 'auto' },
  prefetch: { prefetchAll: false, defaultStrategy: 'hover' },
  integrations: [mdx(), sitemap()],

  // Legacy WordPress URLs → new information architecture
  redirects: {
    '/home-2': '/',
    '/blog': '/insights',
    '/about': '/who-we-are',
    '/services': '/what-we-do',
  },

  // Self-hosted variable fonts: no third-party requests, automatic
  // metric-matched fallbacks to avoid layout shift.
  fonts: [
    {
      name: 'Plus Jakarta Sans',
      cssVariable: '--font-display',
      provider: fontProviders.local(),
      fallbacks: ['system-ui', 'sans-serif'],
      options: {
        variants: [
          { src: ['./src/assets/fonts/plus-jakarta-sans-latin-wght-normal.woff2'], weight: '200 800', style: 'normal' },
        ],
      },
    },
    {
      name: 'Inter',
      cssVariable: '--font-sans',
      provider: fontProviders.local(),
      fallbacks: ['system-ui', 'sans-serif'],
      options: {
        variants: [
          { src: ['./src/assets/fonts/inter-latin-opsz-normal.woff2'], weight: '100 900', style: 'normal' },
        ],
      },
    },
  ],
});
