// Tema claro/oscuro/sistema, atajo Ctrl/⌘+K para el buscador y cierre de menús desplegables.
const STORAGE_KEY = 'codeflow-theme';
const media = window.matchMedia('(prefers-color-scheme: dark)');

function storedTheme() {
    try {
        return localStorage.getItem(STORAGE_KEY) || 'system';
    } catch {
        return 'system';
    }
}

function applyTheme(theme) {
    const dark = theme === 'dark' || (theme === 'system' && media.matches);
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.theme = theme;
    document.querySelectorAll('[data-theme-set]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.themeSet === theme));
    });
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-set]');
    if (button) {
        const theme = button.dataset.themeSet;
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch {
            // sin almacenamiento: se aplica solo en esta página
        }
        applyTheme(theme);
        return;
    }

    // Cierra los <details data-dropdown> abiertos al hacer clic fuera
    document.querySelectorAll('details[data-dropdown][open]').forEach((details) => {
        if (!details.contains(event.target)) {
            details.removeAttribute('open');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        const input = [...document.querySelectorAll('[data-search-shortcut]')].find((el) => el.offsetParent !== null);
        if (input) {
            event.preventDefault();
            input.focus();
            input.select();
        }
    }
    if (event.key === 'Escape') {
        document.querySelectorAll('details[data-dropdown][open]').forEach((details) => details.removeAttribute('open'));
    }
});

media.addEventListener('change', () => {
    if (storedTheme() === 'system') {
        applyTheme('system');
    }
});

applyTheme(storedTheme());
