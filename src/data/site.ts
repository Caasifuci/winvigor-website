/**
 * Single source of truth for site-wide facts.
 * Everything here was carried over from the WordPress database, except
 * the client list (supplied by Winvigor) and items marked TODO.
 */

export const site = {
  name: 'Winvigor Solutions',
  shortName: 'Winvigor',
  tagline: 'Build. Scale. Succeed.',
  description:
    'Winvigor Solutions is a technology consulting and talent resourcing firm helping enterprises streamline operations, reduce delivery risk and drive innovation.',
  url: 'https://www.winvigor.com',

  contact: {
    email: 'services@winvigor.com',
    phone: '+32 468 02 66 75',
    phoneHref: 'tel:+32468026675',
    address: {
      street: 'Ikaroslaan 1',
      postalCode: '1930',
      locality: 'Zaventem',
      country: 'Belgium',
    },
    /**
     * Where the contact form posts. Set PUBLIC_FORM_ENDPOINT to a form
     * backend (Formspree, Basin, a serverless function…). Without it the
     * form falls back to opening the visitor's mail client.
     */
    formEndpoint: import.meta.env.PUBLIC_FORM_ENDPOINT as string | undefined,
  },

  /** TODO: the WordPress site had empty social URLs. Add real ones here. */
  social: {
    linkedin: '',
  },

  /** TODO: company number / VAT (BTW) for the footer, e.g. 'BE0123.456.789'. */
  vat: '',

  /**
   * Sample content (illustrative metrics, case studies, insights) is shown
   * with a visible "Sample" tag. Set PUBLIC_SHOW_SAMPLES=false to hide every
   * sample entry and block in one switch before launch.
   */
  showSampleContent: import.meta.env.PUBLIC_SHOW_SAMPLES !== 'false',
} as const;

/** Primary navigation. The logo links home, so there is no Home item. */
export const nav = [
  { label: 'Who we are', href: '/who-we-are' },
  { label: 'What we do', href: '/what-we-do' },
  { label: 'Insights', href: '/insights' },
  { label: 'Careers', href: '/careers' },
  { label: 'Contact', href: '/contact' },
] as const;

/** Header call to action: the talent board is a primary route for hiring managers. */
export const headerCta = { label: 'Available engineers', href: '/talent' } as const;

/** Real clients, supplied by Winvigor. Add `logo` (an imported SVG) once brand-approved artwork is available. */
export const clients: { name: string; logo?: ImageMetadata }[] = [
  { name: 'EU Institutions' },
  { name: 'Capgemini' },
  { name: 'Atos' },
  { name: 'Reckitt' },
  { name: 'Dresser-Rand' },
  { name: 'GardPass' },
];

export const services = [
  {
    id: 'strategic-it-consulting',
    title: 'Strategic IT consulting',
    summary:
      'Aligning technology initiatives with business goals, so investment goes where it improves performance and competitive position.',
    detail:
      'We work with leadership teams to assess the current estate, set a realistic target architecture and sequence the change. The result is a roadmap your organisation can fund, staff and deliver.',
  },
  {
    id: 'custom-software-development',
    title: 'Custom software development',
    summary:
      'Software built around the way your organisation actually works, from internal platforms to customer-facing products.',
    detail:
      'Our engineers design, build and hand over maintainable systems that streamline operations and improve the experience of the people who use them, keeping the business agile as market demands change.',
  },
  {
    id: 'resource-augmentation',
    title: 'Resource augmentation',
    summary:
      'Specialised engineers and designers, placed into your teams when the work demands it, without the long-term commitment of permanent hires.',
    detail:
      'We source, vet and support senior talent so programmes are staffed with the right expertise at the right time, and your delivery leads keep full control of the work.',
  },
  {
    id: 'operational-risk-management',
    title: 'Operational risk management',
    summary:
      'Clear guidance through complex IT landscapes, so decisions are informed and the organisation is ready for what comes next.',
    detail:
      'We identify where systems, suppliers and processes expose the business, then put practical controls in place that support sustainable growth rather than slow it down.',
  },
] as const;
