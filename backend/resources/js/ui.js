// Interacciones comunes de la interfaz (sin librerías):
//  - Confirmaciones con <dialog> para formularios con data-confirm (sustituye a confirm()).
//  - Evitar dobles envíos: al enviar un formulario se desactiva su botón y muestra «Procesando…».
//  - Cerrar mensajes flash ([data-dismiss]).

const dialog = () => document.getElementById('confirm-dialog');

function openConfirm(form, submitter) {
    const dlg = dialog();
    if (!dlg || typeof dlg.showModal !== 'function') {
        return window.confirm(form.dataset.confirm) ? submit(form, submitter) : null;
    }

    dlg.querySelector('[data-confirm-title]').textContent = form.dataset.confirmTitle || '¿Seguro?';
    dlg.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
    const accept = dlg.querySelector('[data-confirm-accept]');
    accept.textContent = form.dataset.confirmButton || 'Confirmar';
    accept.classList.toggle('bg-rose-600', form.dataset.confirmVariant !== 'primary');
    accept.classList.toggle('hover:bg-rose-500', form.dataset.confirmVariant !== 'primary');
    accept.classList.toggle('bg-purple-600', form.dataset.confirmVariant === 'primary');
    accept.classList.toggle('hover:bg-purple-500', form.dataset.confirmVariant === 'primary');

    dlg.returnValue = '';
    dlg.onclose = () => {
        if (dlg.returnValue === 'ok') {
            submit(form, submitter);
        }
    };
    dlg.showModal();
    dlg.querySelector('[data-confirm-cancel]').focus();
    return null;
}

function submit(form, submitter) {
    form.dataset.confirmed = '1';
    form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
}

function lock(form, submitter) {
    const button = submitter || form.querySelector('button[type=submit], button:not([type])');
    if (!button || button.disabled) {
        return;
    }
    // Se desactiva en el siguiente ciclo para que el navegador envíe el nombre/valor del botón
    setTimeout(() => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.dataset.originalHtml = button.innerHTML;
        button.textContent = button.dataset.loadingText || 'Procesando…';
    }, 0);
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset.confirm && form.dataset.confirmed !== '1') {
        event.preventDefault();
        openConfirm(form, event.submitter);
        return;
    }
    delete form.dataset.confirmed;

    // Formularios GET (búsquedas, filtros) y los que gestionan su propio envío no se bloquean
    if (event.defaultPrevented || (form.method || 'get').toLowerCase() === 'get' || form.hasAttribute('data-no-lock')) {
        return;
    }
    lock(form, event.submitter);
});

// Al volver con «Atrás» la página puede venir de la caché: se reactivan los botones
window.addEventListener('pageshow', () => {
    document.querySelectorAll('button[aria-busy="true"]').forEach((button) => {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    });
});

document.addEventListener('click', (event) => {
    const close = event.target.closest('[data-dismiss]');
    if (close) {
        close.closest('[data-dismissible]')?.remove();
    }
});
