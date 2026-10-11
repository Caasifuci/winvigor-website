/**
 * Shared geometry for the vector artwork. Every visual is built from the
 * same primitive as the Winvigor mark: a bar slanted at 21°.
 */
export const SLANT = Math.tan((21 * Math.PI) / 180);

/** Parallelogram standing on a baseline, leaning right as it rises. */
export function bar(x: number, baseY: number, width: number, height: number): string {
  const s = height * SLANT;
  return `M${x} ${baseY}H${x + width}L${x + width + s} ${baseY - height}H${x + s}Z`;
}

/** Top-centre point of a bar, for connecting lines and nodes. */
export function barTop(x: number, baseY: number, width: number, height: number): [number, number] {
  return [x + width / 2 + height * SLANT, baseY - height];
}

/** Evenly spaced grid lines for a blueprint-style backdrop. */
export function gridLines(w: number, h: number, step: number) {
  const v: number[] = [];
  const hz: number[] = [];
  for (let x = step; x < w; x += step) v.push(x);
  for (let y = step; y < h; y += step) hz.push(y);
  return { v, h: hz };
}
