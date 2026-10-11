/** Shared vocabularies used by content schemas and UI filters. */
export const disciplines = [
  'Software engineering',
  'Cloud & platform',
  'Data & analytics',
  'Quality & test',
  'Analysis & delivery',
] as const;

export type Discipline = (typeof disciplines)[number];
