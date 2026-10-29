/**
 * Revisión con IA en el pull request: mientras el análisis está en cola o en curso, la sección
 * (#analisis-ia con data-ai-poll) se vuelve a pedir cada pocos segundos y se sustituye por la nueva.
 */
const POLL_MS = 4000;

function poll(section) {
  const url = section.dataset.aiPoll;
  if (!url) return;

  window.setTimeout(async () => {
    try {
      const response = await fetch(url, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) return;
      const template = document.createElement('template');
      template.innerHTML = (await response.text()).trim();
      const fresh = template.content.querySelector('[data-ai-section]');
      if (!fresh) return;
      section.replaceWith(fresh);
      poll(fresh);
    } catch {
      // Sin conexión: se reintenta en el siguiente ciclo
      poll(section);
    }
  }, POLL_MS);
}

function init() {
  document.querySelectorAll('[data-ai-section][data-ai-poll]').forEach(poll);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
