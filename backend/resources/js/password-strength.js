/**
 * UI Arc "Password Strength" — Validation & Meter Controller
 * MIT / UI Arc (uiarc.dev) adapted for CodeFlow.
 *
 * ── FEATURES ────────────────────────────────────────────────────────────────
 * - Real-time password evaluation with 4 progressive strength levels.
 * - Show/hide password toggle button with accessible aria-pressed state.
 * - Interactive rules checklist with live remaining character counter.
 * - Semantic meter (role="meter") & live region (role="status" aria-live="polite").
 * - Dark mode and prefers-reduced-motion compatible.
 */

export function initPasswordStrength(container) {
  if (!container || container.dataset.pwsInit === 'true') return;
  container.dataset.pwsInit = 'true';

  const input      = container.querySelector('.pws-input');
  const toggleBtn  = container.querySelector('.pws-toggle');
  const eyeOpen    = container.querySelector('.pws-eye-open');
  const eyeClosed  = container.querySelector('.pws-eye-closed');
  const meter      = container.querySelector('.pws-meter');
  const statusEl   = container.querySelector('.pws-status');
  const counterEl  = container.querySelector('.pws-counter');
  const minLength  = parseInt(container.dataset.minlength, 10) || 8;

  const ruleLength = container.querySelector('[data-rule="length"]');
  const ruleCases  = container.querySelector('[data-rule="cases"]');
  const ruleNumber = container.querySelector('[data-rule="number"]');
  const ruleSymbol = container.querySelector('[data-rule="symbol"]');

  if (!input) return;

  /* ── 1. Show/Hide Password Toggle ────────────────────────────────────────── */
  if (toggleBtn && eyeOpen && eyeClosed) {
    toggleBtn.addEventListener('click', () => {
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';

      toggleBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
      toggleBtn.setAttribute(
        'aria-label',
        isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'
      );

      eyeOpen.classList.toggle('hidden', isPassword);
      eyeClosed.classList.toggle('hidden', !isPassword);

      // Restore focus to input without resetting caret
      input.focus();
    });
  }

  /* ── 2. Password Strength Evaluation ─────────────────────────────────────── */
  const STATUS_LABELS = [
    'Demasiado corta', // Score 0
    'Débil',           // Score 1
    'Aceptable',       // Score 2
    'Buena',           // Score 3
    'Fuerte',          // Score 4
  ];

  function evaluate() {
    const val = input.value || '';

    // Rule checks
    const hasLength = val.length >= minLength;
    const hasCases  = /[a-z]/.test(val) && /[A-Z]/.test(val);
    const hasNumber = /[0-9]/.test(val);
    const hasSymbol = /[^a-zA-Z0-9]/.test(val);

    // Update rule checklist states
    if (ruleLength) ruleLength.dataset.passed = hasLength ? 'true' : 'false';
    if (ruleCases)  ruleCases.dataset.passed  = hasCases ? 'true' : 'false';
    if (ruleNumber) ruleNumber.dataset.passed = hasNumber ? 'true' : 'false';
    if (ruleSymbol) ruleSymbol.dataset.passed = hasSymbol ? 'true' : 'false';

    // Update live character counter
    if (counterEl) {
      if (val.length === 0) {
        counterEl.textContent = '';
      } else if (val.length < minLength) {
        const remaining = minLength - val.length;
        counterEl.textContent = `(faltan ${remaining})`;
      } else {
        counterEl.textContent = `✓`;
      }
    }

    // Calculate score (0 to 4)
    let score = 0;
    const passedCount = (hasLength ? 1 : 0) + (hasCases ? 1 : 0) + (hasNumber ? 1 : 0) + (hasSymbol ? 1 : 0);

    if (val.length === 0) {
      score = 0;
    } else if (val.length < minLength) {
      // Too short: if other complexity rules pass, grade as weak (1), otherwise 0
      score = passedCount >= 2 ? 1 : 0;
    } else {
      // Met length requirement: score matches passed rules count
      score = passedCount;
    }

    // Clamp score
    score = Math.max(0, Math.min(4, score));
    const statusText = STATUS_LABELS[score];

    // Update container state & meter
    container.dataset.score = String(score);

    if (meter) {
      meter.setAttribute('aria-valuenow', String(score));
      meter.setAttribute('aria-valuetext', `Fortaleza: ${statusText}`);
    }

    if (statusEl) {
      statusEl.textContent = statusText;
    }
  }

  /* ── 3. Event Listeners ──────────────────────────────────────────────────── */
  input.addEventListener('input', evaluate);

  // Initial evaluation on load
  evaluate();
}

/* ── Auto-initialiser ─────────────────────────────────────────────────────────── */

export function initAllPasswordStrengths() {
  document.querySelectorAll('.pws:not([data-pws-init="true"])').forEach((el) => {
    initPasswordStrength(el);
  });
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllPasswordStrengths);
  } else {
    initAllPasswordStrengths();
  }

  window.addEventListener('pageshow', initAllPasswordStrengths);
}
