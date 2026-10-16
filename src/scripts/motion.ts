/**
 * Motion: line reveals for hero typography and strategic pillars.
 *
 *   data-reveal="lines"  text slides up line by line from an overflow mask
 *   data-reveal="fade"   element fades up 14px
 *   style="--d: 200ms"   optional delay before the element starts
 *
 * Lines are measured after fonts load, wrapped in masks for the animation,
 * then restored to plain text so later resizes reflow normally.
 * Nothing is hidden unless html.motion is set (JavaScript on, motion allowed).
 */

const root = document.documentElement;
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function splitLines(el: HTMLElement) {
  const text = el.textContent?.replace(/\s+/g, ' ').trim() ?? '';
  // Only plain-text elements are split; anything with markup just fades.
  if (!text || el.children.length > 0) return false;

  el.textContent = '';
  const words = text.split(' ').map((w) => {
    const span = document.createElement('span');
    span.textContent = w;
    el.append(span, ' ');
    return span;
  });

  const lines: string[][] = [];
  let top = Number.NaN;
  for (const w of words) {
    if (w.offsetTop !== top) {
      lines.push([]);
      top = w.offsetTop;
    }
    lines[lines.length - 1].push(w.textContent ?? '');
  }

  el.textContent = '';
  lines.forEach((line, i) => {
    const mask = document.createElement('span');
    mask.className = 'rv-mask';
    const inner = document.createElement('span');
    inner.className = 'rv-line';
    inner.style.setProperty('--i', String(i));
    inner.textContent = line.join(' ');
    mask.append(inner);
    el.append(mask);
  });

  // Restore plain text once the last line has settled.
  const last = el.querySelector<HTMLElement>('.rv-mask:last-child .rv-line');
  last?.addEventListener('animationend', () => { el.textContent = text; }, { once: true });
  return true;
}

function reveal(el: HTMLElement) {
  if (el.dataset.reveal === 'lines') splitLines(el);
  el.classList.add('is-revealed');
}

function init() {
  const targets = [...document.querySelectorAll<HTMLElement>('[data-reveal]')];
  if (reduce || !('IntersectionObserver' in window)) {
    targets.forEach((el) => el.classList.add('is-revealed'));
    root.classList.add('motion-ready');
    return;
  }
  const io = new IntersectionObserver(
    (entries) => {
      for (const e of entries) {
        if (!e.isIntersecting) continue;
        io.unobserve(e.target);
        reveal(e.target as HTMLElement);
      }
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.15 },
  );
  targets.forEach((el) => io.observe(el));
  root.classList.add('motion-ready');
}

// Measure lines with the real fonts, but never wait more than 400ms.
const fontsReady = (document as Document & { fonts?: FontFaceSet }).fonts?.ready ?? Promise.resolve();
Promise.race([fontsReady, new Promise((r) => setTimeout(r, 400))]).then(init);
