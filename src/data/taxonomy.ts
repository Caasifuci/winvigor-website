/** Shared vocabularies used by content schemas and UI filters. */
export const disciplines = [
  'Software engineering',
  'Cloud & platform',
  'Data & analytics',
  'Quality & test',
  'Analysis & delivery',
] as const;

export type Discipline = (typeof disciplines)[number];

/** Clearance levels an open role may require. Never used on talent profiles. */
export const clearanceLevels = ['NATO CONFIDENTIAL', 'NATO SECRET', 'COSMIC TOP SECRET', 'EU SECRET'] as const;
export const clearanceStatuses = ['Must hold', 'Must be eligible'] as const;
