/**
 * Bencho "Icon bar" — Vanilla JS physics engine
 * MIT licensed — bencho.dev/licence
 * Adapted for CodeFlow (Blade + Vite, no React).
 *
 * ── HOW IT WORKS ──────────────────────────────────────────────────────────
 * The indicator travels in two phases:
 *
 *   stretch  The leading edge jumps immediately to the destination slot.
 *            The indicator dilates (grows wider) to cover both origin and
 *            destination simultaneously. Duration: ~180ms.
 *
 *   settle   The trailing edge springs to the destination with a tuned
 *            overshoot curve so the pill compresses back to slot-width.
 *            Duration: ~420ms. The CSS curve provides the elasticity.
 *
 * All geometry is written as CSS custom properties on the .gnav element so
 * the transition happens entirely in CSS — JS only measures and assigns.
 *
 * ── RESIZE SAFETY ─────────────────────────────────────────────────────────
 * A ResizeObserver watches each nav container. When the layout changes (font
 * scale, viewport resize, sidebar open/close) measure() re-runs and the
 * indicator repositions instantly (phase="idle", no transition) to avoid a
 * stale position that would then animate to the wrong place.
 */

/* ── helpers ──────────────────────────────────────────────────────────────── */

/** Read once — a preference, not a live listener. */
const prefersReducedMotion = () =>
  typeof window !== 'undefined' &&
  !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/**
 * Measure every slot in the given .gnav element and return an array of
 * { left, width } objects relative to the nav's own left edge.
 */
function measureSlots(nav) {
  const navRect = nav.getBoundingClientRect();
  return Array.from(nav.querySelectorAll('.gnav-slot')).map((slot) => {
    const r = slot.getBoundingClientRect();
    return { left: r.left - navRect.left, width: r.width };
  });
}

/**
 * Write the indicator's position and size to CSS vars on the .gnav element.
 * The CSS transitions react to these changes — the phase attribute controls
 * which transition curve fires.
 */
function applyStyle(nav, { left, width }) {
  nav.style.setProperty('--ind-move', `${left.toFixed(2)}px`);
  nav.style.setProperty('--ind-size', `${width.toFixed(2)}px`);
}

/* ── per-nav initialiser ─────────────────────────────────────────────────── */

export function initIconBar(nav) {
  const ind = nav.querySelector('.gnav-ind');
  if (!ind) return;

  const slots    = Array.from(nav.querySelectorAll('.gnav-slot'));
  let   measures = [];
  let   currentIndex = slots.findIndex(
    (s) => s.getAttribute('aria-current') === 'page' || s.dataset.active === 'true'
  );
  // If nothing is active, default to the first slot
  if (currentIndex < 0) currentIndex = 0;

  let timer = null;

  /* measure() — called on init and every resize ─────────────────────────── */
  function measure() {
    measures = measureSlots(nav);
    if (!measures.length) return;
    // Reposition instantly without any transition
    const m = measures[currentIndex] ?? measures[0];
    ind.setAttribute('data-phase', 'idle');
    applyStyle(nav, m);
  }

  /* navigate(index) — the two-phase elastic transition ──────────────────── */
  function navigate(toIndex) {
    if (toIndex === currentIndex) return;
    if (!measures.length) measure();

    const from = measures[currentIndex];
    const to   = measures[toIndex];
    if (!from || !to) return;

    const reduced = prefersReducedMotion();
    const goingRight = toIndex > currentIndex;

    clearTimeout(timer);
    currentIndex = toIndex;

    if (reduced) {
      // Instant jump — no elastic animation
      ind.setAttribute('data-phase', 'idle');
      applyStyle(nav, to);
      return;
    }

    /* ── stretch phase ──────────────────────────────────────────────────────
       The leading edge moves to the destination; the trailing edge stays.
       This is achieved by setting the indicator's left to whichever edge is
       closer to origin, and the width to span both slots. */
    const stretchLeft  = goingRight ? from.left              : to.left;
    const stretchRight = goingRight ? to.left + to.width     : from.left + from.width;
    const stretchWidth = stretchRight - stretchLeft;

    ind.setAttribute('data-phase', 'stretch');
    applyStyle(nav, { left: stretchLeft, width: stretchWidth });

    /* ── settle phase ───────────────────────────────────────────────────────
       After the stretch CSS transition (180ms), flip to the settle curve and
       write the final geometry. The CSS cubic-bezier does the overshoot. */
    timer = window.setTimeout(() => {
      ind.setAttribute('data-phase', 'settle');
      applyStyle(nav, to);
    }, 150); // slightly shorter than the CSS 180ms to overlap cleanly
  }

  /* bind slot clicks ───────────────────────────────────────────────────────
     We intercept the click before navigation to trigger the animation, then
     let the default <a> href fire naturally. */
  slots.forEach((slot, i) => {
    slot.addEventListener('click', () => navigate(i));
  });

  /* ResizeObserver — reposition on layout changes without animating ──────── */
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(() => measure()).observe(nav);
  }

  // Initial position
  measure();
}

/* ── auto-init ───────────────────────────────────────────────────────────── */

export function initAllIconBars() {
  document.querySelectorAll('.gnav:not([data-ib-init])').forEach((nav) => {
    nav.setAttribute('data-ib-init', 'true');
    initIconBar(nav);
  });
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllIconBars);
  } else {
    initAllIconBars();
  }
  // pageshow fires on back/forward cache restores too
  window.addEventListener('pageshow', initAllIconBars);
}
