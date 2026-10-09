// Carga bajo demanda del diff de cada archivo (commits y pull requests grandes).
async function load(container, url) {
    container.innerHTML = '<p class="animate-pulse px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400" role="status">Cargando cambios…</p>';
    try {
        const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
        if (!response.ok) {
            throw new Error(String(response.status));
        }
        container.innerHTML = await response.text();
    } catch {
        container.innerHTML = '<p class="px-4 py-3 text-sm text-rose-600 dark:text-rose-400" role="alert">No se pudieron cargar los cambios. '
            + '<a class="underline" href="' + url + '">Abrir aparte</a></p>';
    }
}

document.addEventListener('toggle', (event) => {
    const details = event.target;
    if (!(details instanceof HTMLDetailsElement) || !details.open || !details.dataset.diffSrc || details.dataset.loaded) {
        return;
    }
    details.dataset.loaded = '1';
    load(details.querySelector('[data-diff-body]'), details.dataset.diffSrc);
}, true);

document.addEventListener('click', (event) => {
    const more = event.target.closest('[data-diff-more]');
    const body = more?.closest('[data-diff-body]');
    if (more && body) {
        event.preventDefault();
        load(body, more.href);
    }
});
