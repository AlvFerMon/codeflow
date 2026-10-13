/**
 * Bencho "Icon bar" — Vanilla JS physics engine
 * MIT licensed — bencho.dev/licence
 * Adapted for CodeFlow (Blade + Vite, no React).
 *
 * ── HOW IT WORKS ──────────────────────────────────────────────────────────────
 * The indicator travels in two phases:
 *
 *   stretch  The leading edge jumps immediately to the destination slot.
 *            The indicator dilates (grows wider) to cover both origin and
 *            destination simultaneously. Duration: ~180 ms.
 *
 *   settle   The trailing edge springs to the destination with a tuned
 *            overshoot curve so the pill compresses back to slot-width.
 *            Duration: ~420 ms. The CSS curve provides the elasticity.
 *
 * All geometry is written as CSS custom properties on the .gnav element so
 * the transition happens entirely in CSS — JS only measures and assigns vars.
 *
 * ── RESIZE SAFETY ─────────────────────────────────────────────────────────────
 * A ResizeObserver watches each nav container. When the layout shifts (font
 * scale, viewport resize, sidebar open/close) measure() re-runs and the
 * indicator repositions instantly (phase="idle", no transition) to avoid a
 * stale position that would then animate to the wrong place.
 *
 * ── BACK / FORWARD CACHE ──────────────────────────────────────────────────────
 * The pageshow event fires when the browser restores a page from bfcache.
 * We re-initialise any .gnav elements that were not yet seen (data-ib-init guard
 * prevents double-init on normal navigations).
 */

/* ── helpers ──────────────────────────────────────────────────────────────────── */

/** Read once per call — a preference, not a live listener. */
function prefersReducedMotion() {
  return (
    typeof window !== 'undefined' &&
    !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  );
}

/**
 * Measure every .gnav-slot inside `nav` and return an array of
 * { left, width } objects relative to the nav's own left edge.
 * All values are in CSS pixels (floating-point safe).
 */
function measureSlots(nav) {
  const navRect = nav.getBoundingClientRect();
  return Array.from(nav.querySelectorAll('.gnav-slot')).map((slot) => {
    const r = slot.getBoundingClientRect();
    return {
      left:  r.left - navRect.left,
      width: r.width,
    };
  });
}

/**
 * Write the indicator's current position and size as CSS variables on the
 * .gnav element. The CSS transitions react to these property changes; the
 * data-phase attribute on .gnav-ind controls which curve fires.
 */
function applyStyle(nav, { left, width }) {
  nav.style.setProperty('--ind-move', `${left.toFixed(2)}px`);
  nav.style.setProperty('--ind-size', `${width.toFixed(2)}px`);
}

/* ── per-nav initialiser ──────────────────────────────────────────────────────── */

export function initIconBar(nav) {
  const ind = nav.querySelector('.gnav-ind');
  if (!ind) return;

  const slots = Array.from(nav.querySelectorAll('.gnav-slot'));
  if (!slots.length) return;

  let measures     = [];
  let currentIndex = slots.findIndex(
    (s) => s.getAttribute('aria-current') === 'page' || s.dataset.active === 'true'
  );

  // Nothing active? Default to the first slot so the indicator is always visible.
  if (currentIndex < 0) currentIndex = 0;

  let timer = null;

  /* ── measure() ─────────────────────────────────────────────────────────────
     Reads slot positions from the DOM and snaps the indicator to the active
     slot without any transition. Called on init and on every resize. */
  function measure() {
    measures = measureSlots(nav);
    if (!measures.length) return;

    const m = measures[currentIndex] ?? measures[0];
    ind.setAttribute('data-phase', 'idle'); // no CSS transition
    applyStyle(nav, m);
  }

  /* ── navigate(toIndex) ─────────────────────────────────────────────────────
     The two-phase elastic transition.

     stretch  Leading edge jumps to the destination; the indicator dilates to
              span both origin and destination simultaneously.

     settle   After 150 ms (slightly before the 180 ms stretch CSS duration
              ends, so they overlap cleanly) the trailing edge springs to the
              destination with an overshoot cubic-bezier. */
  function navigate(toIndex) {
    if (toIndex === currentIndex) return;
    if (!measures.length) measure();

    const from = measures[currentIndex];
    const to   = measures[toIndex];
    if (!from || !to) return;

    const reduced    = prefersReducedMotion();
    const goingRight = toIndex > currentIndex;

    clearTimeout(timer);
    currentIndex = toIndex;

    if (reduced) {
      // Respect the user's motion preference — instant jump, no spring.
      ind.setAttribute('data-phase', 'idle');
      applyStyle(nav, to);
      return;
    }

    /* ── stretch phase ────────────────────────────────────────────────────────
       Determine which edge is the "leading" one (the one that jumps first).
       Going right → leading edge is the right side of `to`.
       Going left  → leading edge is the left  side of `to`.
       The indicator left = min(from.left, to.left);
       The indicator width spans from the leftmost to the rightmost edge. */
    const stretchLeft  = goingRight ? from.left          : to.left;
    const stretchRight = goingRight ? to.left + to.width : from.left + from.width;
    const stretchWidth = stretchRight - stretchLeft;

    ind.setAttribute('data-phase', 'stretch');
    applyStyle(nav, { left: stretchLeft, width: stretchWidth });

    /* ── settle phase ─────────────────────────────────────────────────────────
       150 ms into stretch (before the 180 ms CSS transition ends) we flip
       to the settle curve and write the final geometry. The CSS
       cubic-bezier(0.22, 1, 0.36, 1.35) with p2y = 1.35 overshoots and
       then pulls back — that overshoot is the "spring feel". */
    timer = window.setTimeout(() => {
      ind.setAttribute('data-phase', 'settle');
      applyStyle(nav, to);
    }, 150);
  }

  /* ── slot click bindings ────────────────────────────────────────────────────
     We intercept the click to fire the animation, then let the default <a>
     href navigate naturally. No e.preventDefault() — the link still works. */
  slots.forEach((slot, i) => {
    slot.addEventListener('click', () => navigate(i));
  });

  /* ── ResizeObserver ─────────────────────────────────────────────────────────
     Repositions the indicator without animating whenever the nav changes size.
     Fires on font-scale changes, viewport resizes, etc. */
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(() => measure()).observe(nav);
  }

  // Initial measurement — places the indicator on the active slot at first paint.
  measure();
}

/* ── auto-init ────────────────────────────────────────────────────────────────── */

/**
 * Find every .gnav in the document that hasn't been initialised yet and run
 * initIconBar() on it. The data-ib-init guard prevents double-init on normal
 * navigations. On back/forward cache restores (pageshow) it is safe to call
 * again because new .gnav elements may have appeared.
 */
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
    // Script ran after DOMContentLoaded (e.g. deferred / module) — init now.
    initAllIconBars();
  }

  // pageshow also fires on back/forward cache restores, not just fresh loads.
  window.addEventListener('pageshow', initAllIconBars);
}
