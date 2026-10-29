// Pantalla «Resolver conflictos»: botones para quedarse con una versión y aviso de marcas pendientes.
const form = document.querySelector('form[data-conflicts]');

if (form) {
    const MARKER = /^(<{7} |={7}$|>{7} )/m;

    const update = () => {
        let pending = 0;
        form.querySelectorAll('[data-conflict-file]').forEach((card) => {
            const choice = card.querySelector('[data-choice]:checked')?.dataset.choice ?? 'edit';
            const textarea = card.querySelector('[data-content]');
            const state = card.querySelector('[data-conflict-state]');
            const hasMarkers = choice === 'edit' && MARKER.test(textarea.value);
            if (hasMarkers) pending++;
            state.textContent = choice === 'delete' ? 'Se borrará' : hasMarkers ? 'Quedan marcas de conflicto' : 'Resuelto';
            state.className = 'text-xs font-semibold ' + (hasMarkers ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300');
        });
        const summary = form.querySelector('[data-conflicts-summary]');
        summary.textContent = pending === 0 ? 'Todo listo para guardar.' : pending === 1 ? 'Falta 1 archivo por resolver.' : `Faltan ${pending} archivos por resolver.`;
    };

    form.addEventListener('change', (e) => {
        const radio = e.target.closest('[data-choice]');
        if (!radio) return;
        const card = radio.closest('[data-conflict-file]');
        const choice = radio.dataset.choice;
        const textarea = card.querySelector('[data-content]');
        const source = card.querySelector(`template[data-version="${choice}"]`);
        if (source) textarea.value = source.content.textContent;
        textarea.closest('label').hidden = choice === 'delete' || card.querySelector('[data-binary]') !== null;
        textarea.disabled = card.querySelector('[data-binary]') !== null;
        update();
    });
    // Si se edita el texto tras elegir una versión, pasa a «Editar a mano»
    form.addEventListener('input', (e) => {
        const card = e.target.closest('[data-conflict-file]');
        const edit = card?.querySelector('[data-choice="edit"]');
        if (edit && !edit.checked) edit.checked = true;
        update();
    });
    form.addEventListener('submit', (e) => {
        update();
        if (/^Falta/.test(form.querySelector('[data-conflicts-summary]').textContent)) {
            e.preventDefault();
            form.querySelector('[data-conflicts-summary]').focus?.();
        }
    });
    update();
}
