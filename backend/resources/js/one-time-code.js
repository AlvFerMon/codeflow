/**
 * Bencho "One-time code" (OTP) — Liquid Goo & Spring Physics Controller
 * MIT licensed — bencho.dev/licence
 * Adapted for CodeFlow (Blade + Vite, Vanilla JS, no React).
 *
 * ── ARCHITECTURE & PHYSICS ──────────────────────────────────────────────────
 * 1. Single Native Input:
 *    A transparent <input> overlays all slots to preserve system autofill,
 *    mobile numeric keypads, clipboard paste, backspace, and screen readers.
 *
 * 2. Liquid SVG Filter (Goo):
 *    feGaussianBlur (stdDeviation: 3) + feColorMatrix (alpha cut: 24, offset: -12)
 *    fuses contiguous filled blobs and the moving cursor into an organic shape.
 *
 * 3. Spring Physics:
 *    - Pill cursor merge: k = 0.075, zeta = 0.42 (calibrated smooth spring).
 *    - Digit pop duration: POP_MS = 260ms (half sine expansion).
 *    - Error rejection shake: SHAKE_MS = 620ms with right-to-left digit draining.
 */

const prefersReducedMotion = () =>
  typeof window !== 'undefined' &&
  !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

export function initOneTimeCode(otpContainer) {
  if (!otpContainer || otpContainer.dataset.otpInit === 'true') return;
  otpContainer.dataset.otpInit = 'true';

  const nativeInput = otpContainer.querySelector('.otp-native');
  const slots       = Array.from(otpContainer.querySelectorAll('.otp-slot'));
  const chars       = Array.from(otpContainer.querySelectorAll('.otp-char'));
  const blobs       = Array.from(otpContainer.querySelectorAll('.otp-blob'));
  const pill        = otpContainer.querySelector('.otp-pill');
  const length      = parseInt(otpContainer.dataset.length, 10) || 6;

  if (!nativeInput || !slots.length) return;

  // Spring physics constants (MERGE: k = 0.075, zeta = 0.42)
  const MERGE_K    = 0.075;
  const MERGE_ZETA = 0.42;
  const MERGE_MASS = 1.0;
  const MERGE_C    = 2 * MERGE_ZETA * Math.sqrt(MERGE_K * MERGE_MASS);

  // Animation constants
  const POP_MS   = 260; // Digit entry pop duration
  const SHAKE_MS = 620; // Rejection shake duration

  // Dynamic geometry measurement
  let slotWidth  = 44;
  let slotGap    = 8;

  function measureGeometry() {
    if (slots[0]) {
      const rect = slots[0].getBoundingClientRect();
      if (rect.width > 0) slotWidth = rect.width;
    }
    if (slots.length > 1) {
      const r0 = slots[0].getBoundingClientRect();
      const r1 = slots[1].getBoundingClientRect();
      if (r1.left > r0.left) slotGap = r1.left - r0.right;
    }
  }

  // Spring state for cursor pill
  let currentPillX  = 0;
  let targetPillX   = 0;
  let pillVelocity  = 0;
  let springFrameId = null;
  let lastTime      = null;

  function getSlotPosition(index) {
    const clampedIndex = Math.max(0, Math.min(index, length - 1));
    return clampedIndex * (slotWidth + slotGap);
  }

  function applyPillX(x) {
    otpContainer.style.setProperty('--pill-x', `${x.toFixed(2)}px`);
  }

  /* ── 60Hz frame-based spring simulation ──────────────────────────────────── */
  function tickSpring(time) {
    if (!lastTime) lastTime = time;
    const dt = Math.min((time - lastTime) / 1000, 0.032);
    lastTime = time;

    // Second-order differential equation step normalized to 60fps
    const timeFactor = dt * 60;
    const displacement = currentPillX - targetPillX;
    const springForce = -MERGE_K * displacement;
    const dampingForce = -MERGE_C * pillVelocity;
    const acceleration = (springForce + dampingForce) / MERGE_MASS;

    pillVelocity += acceleration * timeFactor;
    currentPillX += pillVelocity * timeFactor;

    applyPillX(currentPillX);

    const isResting = Math.abs(currentPillX - targetPillX) < 0.2 && Math.abs(pillVelocity) < 0.2;

    if (!isResting && !prefersReducedMotion()) {
      springFrameId = requestAnimationFrame(tickSpring);
    } else {
      currentPillX = targetPillX;
      pillVelocity = 0;
      applyPillX(currentPillX);
      springFrameId = null;
      lastTime = null;
    }
  }

  function movePillTo(index) {
    targetPillX = getSlotPosition(index);
    if (prefersReducedMotion()) {
      currentPillX = targetPillX;
      pillVelocity = 0;
      applyPillX(currentPillX);
      return;
    }
    if (!springFrameId) {
      lastTime = null;
      springFrameId = requestAnimationFrame(tickSpring);
    }
  }

  /* ── Visual update from input value ──────────────────────────────────────── */
  let prevLength = nativeInput.value.length;

  function syncDisplay(triggerPop = false) {
    const val = nativeInput.value;
    const activeIndex = Math.min(val.length, length - 1);

    chars.forEach((charEl, idx) => {
      const char = val[idx] || '';
      charEl.textContent = char;

      // Pop animation on newly added digit
      if (triggerPop && idx === val.length - 1 && val.length > prevLength) {
        charEl.dataset.popping = 'true';
        setTimeout(() => {
          charEl.dataset.popping = 'false';
        }, POP_MS);
      }
    });

    blobs.forEach((blobEl, idx) => {
      blobEl.dataset.filled = idx < val.length ? 'true' : 'false';
    });

    slots.forEach((slotEl, idx) => {
      slotEl.dataset.active = idx === activeIndex ? 'true' : 'false';
    });

    movePillTo(activeIndex);
    prevLength = val.length;
  }

  /* ── Rejection & right-to-left digit draining ────────────────────────────── */
  function triggerReject() {
    otpContainer.dataset.phase = 'no';

    // Start cascading right-to-left drain
    const currentVal = nativeInput.value;
    const count = currentVal.length;

    if (count > 0) {
      // Delay drain slightly so the first shake wave is visible
      setTimeout(() => {
        let drainIndex = count - 1;
        const drainInterval = setInterval(() => {
          if (drainIndex >= 0) {
            nativeInput.value = nativeInput.value.slice(0, drainIndex);
            syncDisplay(false);
            drainIndex--;
          } else {
            clearInterval(drainInterval);
            nativeInput.value = '';
            syncDisplay(false);
          }
        }, 65);
      }, 200);
    }

    // Clear error phase after shake duration
    setTimeout(() => {
      otpContainer.dataset.phase = 'idle';
      nativeInput.focus();
    }, SHAKE_MS + 100);
  }

  /* ── Event Listeners ──────────────────────────────────────────────────────── */

  nativeInput.addEventListener('input', () => {
    // Sanitize non-digits
    const clean = nativeInput.value.replace(/\D/g, '').slice(0, length);
    if (nativeInput.value !== clean) {
      nativeInput.value = clean;
    }

    // Reset error phase on new input
    if (otpContainer.dataset.phase === 'no') {
      otpContainer.dataset.phase = 'idle';
    }

    syncDisplay(true);

    // Auto-submit when code is completely entered — transition to 'ok' state and let success animation display fully
    if (clean.length === length) {
      const form = otpContainer.closest('form');
      if (form) {
        // Trigger visual success/validation animation once all digits are entered
        setTimeout(() => {
          otpContainer.dataset.phase = 'ok';
        }, POP_MS);

        // Allow ample time for the pop (260ms), spring settle (~450ms) and success state animation to show completely
        setTimeout(() => {
          form.requestSubmit();
        }, 850);
      }
    }
  });

  nativeInput.addEventListener('focus', () => {
    otpContainer.dataset.focused = 'true';
    measureGeometry();
    const activeIndex = Math.min(nativeInput.value.length, length - 1);
    movePillTo(activeIndex);
  });

  nativeInput.addEventListener('blur', () => {
    otpContainer.dataset.focused = 'false';
  });

  // Clicking anywhere on slots focuses the invisible input
  otpContainer.addEventListener('click', () => {
    nativeInput.focus();
  });

  // ResizeObserver for responsive recalculation
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(() => {
      measureGeometry();
      const activeIndex = Math.min(nativeInput.value.length, length - 1);
      targetPillX = getSlotPosition(activeIndex);
      currentPillX = targetPillX;
      applyPillX(currentPillX);
    }).observe(otpContainer);
  }

  // Initial measurement and sync
  measureGeometry();
  syncDisplay(false);

  // Check if loaded with error phase (e.g. backend validation failure)
  if (otpContainer.dataset.phase === 'no') {
    triggerReject();
  }
}

/* ── Auto-initialiser ─────────────────────────────────────────────────────────── */

export function initAllOneTimeCodes() {
  document.querySelectorAll('.otp:not([data-otp-init="true"])').forEach((el) => {
    initOneTimeCode(el);
  });
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllOneTimeCodes);
  } else {
    initAllOneTimeCodes();
  }

  window.addEventListener('pageshow', initAllOneTimeCodes);
}
