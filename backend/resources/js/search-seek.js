/**
 * Bencho "Search / Seek" — Vanilla JS Physics Engine
 * MIT licensed — bencho.dev/licence
 * Adapted for CodeFlow (Blade + Vite, Vanilla JS, no React).
 *
 * ── HOW IT WORKS ──────────────────────────────────────────────────────────────
 * 1. Geometry & State:
 *    The width (--w) is the single state of truth:
 *      - Closed: measures --shut (compact 36px circle).
 *      - Open:   expands smoothly to capsule pill (--span / 280px-320px).
 *
 * 2. Static Lens Anchor:
 *    The SVG lens never translates with respect to the box; the box grows
 *    centered/anchored around it.
 *
 * 3. Text Reveal (--say):
 *    The text field is revealed during the last third of expansion
 *    through the CSS custom property --say (0 to 1).
 *
 * 4. Physics Simulation:
 *    Frame-based spring simulation with springOf(tune) and calibrated
 *    zeta damping ratio (0.78), avoiding infinite oscillations while
 *    maintaining a pleasant, tactile overshoot.
 *
 * 5. Magnetic Attraction:
 *    Subtle magnetic pull toward cursor while closed (give = 50).
 */

/* ── helpers ──────────────────────────────────────────────────────────────────── */

const prefersReducedMotion = () =>
  typeof window !== 'undefined' &&
  !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/**
 * springOf(tune)
 * Second-order harmonic oscillator simulation with real damping.
 *
 *   stiffness (k) : restoring force constant (spring tension)
 *   zeta          : damping ratio (0.78 = underdamped, subtle overshoot)
 *   mass (m)      : inertial mass (1.0)
 *   damping (c)   : calculated from zeta: c = 2 * zeta * sqrt(k * m)
 */
function springOf(tune = {}) {
  const k    = tune.stiffness ?? 280;
  const mass = tune.mass ?? 1;
  const zeta = tune.zeta ?? 0.78;
  const c    = tune.damping ?? (2 * zeta * Math.sqrt(k * mass));

  return function step(current, target, velocity, dt) {
    // Clamp delta time to 32ms to prevent divergence during tab switching
    const clampedDt = Math.min(dt, 0.032);
    const displacement = current - target;
    const springForce  = -k * displacement;
    const dampingForce = -c * velocity;
    const acceleration = (springForce + dampingForce) / mass;

    const newVelocity = velocity + acceleration * clampedDt;
    const newCurrent  = current + newVelocity * clampedDt;

    // Settle condition
    const isAtRest = Math.abs(newCurrent - target) < 0.15 && Math.abs(newVelocity) < 0.2;

    return {
      value:    isAtRest ? target : newCurrent,
      velocity: isAtRest ? 0 : newVelocity,
      done:     isAtRest,
    };
  };
}

/* ── per-instance Seek controller ─────────────────────────────────────────────── */

export function initSeek(form) {
  if (!form || form.dataset.sekInit === 'true') return;
  form.dataset.sekInit = 'true';

  const lensBtn   = form.querySelector('.sek-lens');
  const input     = form.querySelector('.sek-input');
  const clearBtn  = form.querySelector('.sek-clear');
  const kbdChip   = form.querySelector('.sek-k');

  if (!lensBtn || !input) return;

  // Read dimensions from CSS variables or computed layout
  function readDimensions() {
    const style = getComputedStyle(form);
    const shut  = parseFloat(style.getPropertyValue('--shut')) || 36;
    const span  = parseFloat(style.getPropertyValue('--span')) || 280;
    return { shut, span };
  }

  let { shut, span } = readDimensions();

  // State variables
  let isOpen      = false;
  let currentW    = shut;
  let targetW     = shut;
  let velocityW   = 0;
  let currentSay  = 0;
  let animFrameId = null;
  let lastTime    = null;

  // Magnetic attraction state (give = 50)
  const GIVE_MAX = 50;
  const GIVE_RADIUS = 90;
  let currentMagX = 0;
  let currentMagY = 0;
  let targetMagX  = 0;
  let targetMagY  = 0;
  let magFrameId  = null;

  const spring = springOf({ stiffness: 300, zeta: 0.78, mass: 1 });

  /* ── render frame ─────────────────────────────────────────────────────────── */
  function applyGeometry(w, say) {
    form.style.setProperty('--w', `${w.toFixed(2)}px`);
    form.style.setProperty('--say', say.toFixed(3));
  }

  function applyMagnetic(x, y) {
    form.style.setProperty('--mag-x', `${x.toFixed(2)}px`);
    form.style.setProperty('--mag-y', `${y.toFixed(2)}px`);
  }

  /* ── main physics loop ────────────────────────────────────────────────────── */
  function tickSpring(time) {
    if (!lastTime) lastTime = time;
    const dt = (time - lastTime) / 1000;
    lastTime = time;

    const res = spring(currentW, targetW, velocityW, dt);
    currentW  = res.value;
    velocityW = res.velocity;

    // The text field is revealed during the last third of expansion via --say
    // progress p from 0 to 1
    const p = Math.max(0, Math.min(1, (currentW - shut) / Math.max(1, span - shut)));
    // Last third: p from 0.67 to 1.0 maps to say from 0 to 1
    currentSay = p <= 0.67 ? 0 : Math.min(1, (p - 0.67) / 0.33);

    applyGeometry(currentW, currentSay);

    if (!res.done) {
      animFrameId = requestAnimationFrame(tickSpring);
    } else {
      animFrameId = null;
      lastTime = null;
      currentW = targetW;
      currentSay = isOpen ? 1 : 0;
      applyGeometry(currentW, currentSay);
    }
  }

  function startSpring(target) {
    targetW = target;
    if (prefersReducedMotion()) {
      currentW = targetW;
      velocityW = 0;
      currentSay = isOpen ? 1 : 0;
      applyGeometry(currentW, currentSay);
      return;
    }
    if (!animFrameId) {
      lastTime = null;
      animFrameId = requestAnimationFrame(tickSpring);
    }
  }

  /* ── magnetic loop (ambient attraction when closed) ───────────────────────── */
  function tickMagnetic() {
    // Smooth lerp toward target magnetic position
    currentMagX += (targetMagX - currentMagX) * 0.2;
    currentMagY += (targetMagY - currentMagY) * 0.2;

    applyMagnetic(currentMagX, currentMagY);

    if (Math.abs(targetMagX - currentMagX) > 0.05 || Math.abs(targetMagY - currentMagY) > 0.05) {
      magFrameId = requestAnimationFrame(tickMagnetic);
    } else {
      currentMagX = targetMagX;
      currentMagY = targetMagY;
      applyMagnetic(currentMagX, currentMagY);
      magFrameId = null;
    }
  }

  function startMagnetic() {
    if (prefersReducedMotion()) return;
    if (!magFrameId) {
      magFrameId = requestAnimationFrame(tickMagnetic);
    }
  }

  /* ── public state transitions ────────────────────────────────────────────── */
  function open() {
    if (isOpen) return;
    isOpen = true;
    form.dataset.open = 'true';

    // Clear magnetic deflection upon opening
    targetMagX = 0;
    targetMagY = 0;
    startMagnetic();

    startSpring(span);

    // Focus input once expanding
    setTimeout(() => {
      input.focus();
    }, 50);
  }

  function close() {
    if (!isOpen) return;
    // Don't close if there is non-empty text input
    if (input.value.trim().length > 0) return;

    isOpen = false;
    form.dataset.open = 'false';
    input.blur();

    startSpring(shut);
  }

  function updateValueState() {
    const hasVal = input.value.trim().length > 0;
    form.dataset.hasValue = hasVal ? 'true' : 'false';
  }

  /* ── event listeners ──────────────────────────────────────────────────────── */

  // Click on lens triggers expansion or focus
  lensBtn.addEventListener('click', (e) => {
    if (!isOpen) {
      e.preventDefault();
      open();
    } else if (input.value.trim().length > 0) {
      // If already open and has value, submit form
      form.requestSubmit();
    } else {
      input.focus();
    }
  });

  // Expand when input receives focus (e.g. via Tab navigation or Ctrl+K)
  input.addEventListener('focus', () => {
    if (!isOpen) open();
  });

  // Collapse on blur if input is empty
  input.addEventListener('blur', () => {
    // Small timeout to allow clearBtn or form submit clicks to process
    setTimeout(() => {
      if (document.activeElement !== input) {
        close();
      }
    }, 150);
  });

  // Track text updates
  input.addEventListener('input', () => {
    updateValueState();
  });

  // Clear button
  if (clearBtn) {
    clearBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      input.value = '';
      updateValueState();
      input.focus();
    });
  }

  // Keyboard controls: Escape collapses
  form.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      input.value = '';
      updateValueState();
      close();
    }
  });

  // Ambient magnetic effect towards cursor when closed
  const onPointerMove = (e) => {
    if (isOpen) {
      if (targetMagX !== 0 || targetMagY !== 0) {
        targetMagX = 0;
        targetMagY = 0;
        startMagnetic();
      }
      return;
    }

    const rect = form.getBoundingClientRect();
    // Element might be hidden (e.g. mobile hidden)
    if (rect.width === 0 || rect.height === 0) return;

    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;
    const dx = e.clientX - centerX;
    const dy = e.clientY - centerY;
    const dist = Math.hypot(dx, dy);

    if (dist < GIVE_RADIUS) {
      // Quadratic dropoff for tactile feel
      const force = Math.pow(1 - dist / GIVE_RADIUS, 1.8);
      // Magnetic displacement bounded by GIVE_MAX proportion
      targetMagX = (dx / (dist || 1)) * force * (GIVE_MAX * 0.22);
      targetMagY = (dy / (dist || 1)) * force * (GIVE_MAX * 0.22);
      startMagnetic();
    } else if (targetMagX !== 0 || targetMagY !== 0) {
      targetMagX = 0;
      targetMagY = 0;
      startMagnetic();
    }
  };

  const onPointerLeave = () => {
    if (targetMagX !== 0 || targetMagY !== 0) {
      targetMagX = 0;
      targetMagY = 0;
      startMagnetic();
    }
  };

  window.addEventListener('pointermove', onPointerMove, { passive: true });
  document.addEventListener('pointerleave', onPointerLeave);

  // ResizeObserver to recalibrate dimensions on font-scale or responsive changes
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(() => {
      const dims = readDimensions();
      shut = dims.shut;
      span = dims.span;
      if (!isOpen && currentW !== shut) {
        currentW = shut;
        applyGeometry(shut, 0);
      }
    }).observe(form);
  }

  // Initial state check: if input already has a value, initialize open immediately!
  updateValueState();
  if (input.value.trim().length > 0) {
    isOpen = true;
    form.dataset.open = 'true';
    currentW = span;
    targetW = span;
    currentSay = 1;
    applyGeometry(span, 1);
  } else {
    currentW = shut;
    targetW = shut;
    currentSay = 0;
    applyGeometry(shut, 0);
  }
}

/* ── auto-initialiser ─────────────────────────────────────────────────────────── */

export function initAllSeeks() {
  document.querySelectorAll('.sek:not([data-sek-init="true"])').forEach((form) => {
    initSeek(form);
  });
}

// Global shortcut handler for Ctrl+K / Cmd+K to open Seek
if (typeof document !== 'undefined') {
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      const seek = document.querySelector('.sek[data-sek-init="true"]');
      if (seek) {
        e.preventDefault();
        const input = seek.querySelector('.sek-input');
        if (input) {
          input.focus();
          input.select();
        }
      }
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllSeeks);
  } else {
    initAllSeeks();
  }

  window.addEventListener('pageshow', initAllSeeks);
}
