// Tutorial de bienvenida: visita guiada paso a paso.
// Lee el estado de <script id="cf-onboarding"> (layouts/_onboarding.blade.php) y resalta el
// elemento con data-tour="<anchor>" de cada paso. Si no existe o no se ve, el paso sale centrado.
// Guarda el progreso en el servidor, así que se puede seguir en otra pestaña o dispositivo.

const dataEl = document.getElementById('cf-onboarding');

if (dataEl) {
    let data;
    try {
        data = JSON.parse(dataEl.textContent);
    } catch {
        data = null;
    }
    if (data?.tour?.show && data.tour.steps.length) {
        // Se espera a que la página esté pintada (barra de navegación con indicador, etc.)
        window.addEventListener('load', () => startTour(data));
    }
}

function post(url, body) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: body ? JSON.stringify(body) : null,
        credentials: 'same-origin',
    }).catch(() => null);
}

function visibleAnchor(name) {
    if (!name) return null;
    for (const el of document.querySelectorAll(`[data-tour="${CSS.escape(name)}"]`)) {
        const r = el.getBoundingClientRect();
        if (r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden') return el;
    }
    return null;
}

function startTour({ tour, urls }) {
    const steps = tour.steps;
    let index = tour.step || 0;
    const previouslyFocused = document.activeElement;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Capa oscura + recorte alrededor del elemento + bocadillo
    const root = document.createElement('div');
    root.className = 'cf-tour';
    root.innerHTML = `
        <div class="cf-tour-backdrop"></div>
        <div class="cf-tour-spot" hidden></div>
        <div class="cf-tour-pop" role="dialog" aria-modal="true" aria-labelledby="cf-tour-title" aria-describedby="cf-tour-text">
            <p class="cf-tour-count" aria-live="polite"></p>
            <h2 id="cf-tour-title" class="cf-tour-title"></h2>
            <p id="cf-tour-text" class="cf-tour-text"></p>
            <div class="cf-tour-confirm" hidden>
                <p>¿Saltar el tutorial? Puedes volver a verlo cuando quieras en tu menú › «Ver el tutorial otra vez».</p>
                <div class="cf-tour-buttons">
                    <button type="button" class="cf-tour-btn" data-act="keep">Seguir con el tutorial</button>
                    <button type="button" class="cf-tour-btn cf-tour-btn-danger" data-act="skip-yes">Sí, saltar</button>
                </div>
            </div>
            <div class="cf-tour-buttons cf-tour-nav">
                <button type="button" class="cf-tour-btn cf-tour-btn-ghost" data-act="skip">Saltar tutorial</button>
                <span class="cf-tour-spacer"></span>
                <button type="button" class="cf-tour-btn" data-act="back">Atrás</button>
                <button type="button" class="cf-tour-btn cf-tour-btn-primary" data-act="next">Siguiente</button>
            </div>
        </div>`;
    if (reduceMotion) root.classList.add('cf-tour-still');
    document.body.appendChild(root);

    const spot = root.querySelector('.cf-tour-spot');
    const pop = root.querySelector('.cf-tour-pop');
    const confirmBox = root.querySelector('.cf-tour-confirm');
    const nav = root.querySelector('.cf-tour-nav');
    const btnBack = root.querySelector('[data-act="back"]');
    const btnNext = root.querySelector('[data-act="next"]');

    function place(scroll = false) {
        const step = steps[index];
        const anchor = visibleAnchor(step.anchor);
        pop.classList.toggle('cf-tour-center', !anchor);
        spot.hidden = !anchor;
        if (!anchor) {
            pop.style.top = pop.style.left = '';
            return;
        }
        if (scroll === true) anchor.scrollIntoView({ block: 'nearest' });
        const r = anchor.getBoundingClientRect();
        const pad = 6;
        Object.assign(spot.style, {
            top: `${r.top - pad}px`, left: `${r.left - pad}px`,
            width: `${r.width + pad * 2}px`, height: `${r.height + pad * 2}px`,
        });
        // Debajo del elemento si cabe; si no, encima. Siempre dentro de la pantalla.
        const pw = pop.offsetWidth;
        const ph = pop.offsetHeight;
        const margin = 12;
        let top = r.bottom + margin;
        if (top + ph > window.innerHeight - margin) top = Math.max(margin, r.top - ph - margin);
        const left = Math.min(Math.max(margin, r.left + r.width / 2 - pw / 2), window.innerWidth - pw - margin);
        pop.style.top = `${top}px`;
        pop.style.left = `${left}px`;
    }

    function onMove() {
        place(false);
    }

    function render() {
        const step = steps[index];
        root.querySelector('.cf-tour-count').textContent = `Paso ${index + 1} de ${steps.length}`;
        root.querySelector('.cf-tour-title').textContent = step.title;
        root.querySelector('.cf-tour-text').textContent = step.text;
        btnBack.hidden = index === 0;
        btnNext.textContent = index === steps.length - 1 ? 'Terminar' : 'Siguiente';
        confirmBox.hidden = true;
        nav.hidden = false;
        place(true);
        btnNext.focus();
    }

    function close() {
        window.removeEventListener('resize', onMove);
        window.removeEventListener('scroll', onMove, true);
        document.removeEventListener('keydown', onKey, true);
        root.remove();
        previouslyFocused?.focus?.();
    }

    function askSkip() {
        confirmBox.hidden = false;
        nav.hidden = true;
        place();
        root.querySelector('[data-act="keep"]').focus();
    }

    function go(delta) {
        const next = index + delta;
        if (next >= steps.length) {
            post(urls.complete);
            close();
            return;
        }
        index = Math.max(0, next);
        post(urls.progress, { step: index });
        render();
    }

    function onKey(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            if (!confirmBox.hidden) render();
            else askSkip();
        } else if (e.key === 'ArrowRight' && confirmBox.hidden) {
            go(1);
        } else if (e.key === 'ArrowLeft' && confirmBox.hidden && index > 0) {
            go(-1);
        } else if (e.key === 'Tab') {
            // Mantiene el foco dentro del bocadillo
            const items = [...pop.querySelectorAll('button')].filter((b) => !b.hidden && b.offsetParent !== null);
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    root.addEventListener('click', (e) => {
        const act = e.target.closest('[data-act]')?.dataset.act;
        if (act === 'next') go(1);
        else if (act === 'back') go(-1);
        else if (act === 'skip') askSkip();
        else if (act === 'keep') render();
        else if (act === 'skip-yes') {
            post(urls.skip);
            close();
        }
    });
    window.addEventListener('resize', onMove);
    window.addEventListener('scroll', onMove, true);
    document.addEventListener('keydown', onKey, true);

    render();
}
