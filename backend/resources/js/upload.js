/**
 * Subida de archivos y carpetas (CodeFlow).
 * - Selector de archivos, selector de carpeta (webkitdirectory) y arrastrar y soltar (también carpetas).
 * - Conserva la estructura de carpetas: cada archivo se envía con su ruta relativa en paths[].
 * - Comprueba los límites antes de enviar y muestra una barra de progreso.
 */
const IGNORED_DIRS = ['.git', 'node_modules'];
const IGNORED_FILES = ['.DS_Store', 'Thumbs.db', 'desktop.ini'];

function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function isIgnored(path) {
    const parts = path.split('/');
    return parts.some((p) => IGNORED_DIRS.includes(p)) || IGNORED_FILES.includes(parts[parts.length - 1]);
}

// Lee recursivamente una carpeta arrastrada (readEntries devuelve los hijos por lotes)
function readEntry(entry) {
    if (entry.isFile) {
        return new Promise((resolve) => entry.file(
            (file) => resolve([{ file, path: entry.fullPath.replace(/^\//, '') }]),
            () => resolve([]),
        ));
    }
    if (!entry.isDirectory) return Promise.resolve([]);
    const reader = entry.createReader();
    const all = [];
    return new Promise((resolve) => {
        const batch = () => reader.readEntries(async (entries) => {
            if (!entries.length) {
                const nested = await Promise.all(all.map(readEntry));
                resolve(nested.flat());
                return;
            }
            all.push(...entries);
            batch();
        }, () => resolve([]));
        batch();
    });
}

function init(root) {
    const form = root.closest('form');
    const limits = {
        files: Number(root.dataset.maxFiles),
        fileBytes: Number(root.dataset.maxFileBytes),
        totalBytes: Number(root.dataset.maxTotalBytes),
    };
    const fileInput = root.querySelector('[data-upload-files]');
    const folderInput = root.querySelector('[data-upload-folder]');
    const dropZone = root.querySelector('[data-upload-drop]');
    const list = root.querySelector('[data-upload-list]');
    const summary = root.querySelector('[data-upload-summary]');
    const problems = root.querySelector('[data-upload-problems]');
    const progress = root.querySelector('[data-upload-progress]');
    const bar = root.querySelector('[data-upload-bar]');
    const progressText = root.querySelector('[data-upload-progress-text]');
    const submit = form.querySelector('button[type="submit"]');

    /** @type {Map<string, File>} ruta relativa → archivo */
    const selected = new Map();
    let ignored = 0;

    function add(items) {
        for (const { file, path } of items) {
            const clean = path.replace(/\\/g, '/').replace(/^\/+/, '');
            if (!clean) continue;
            if (isIgnored(clean)) { ignored++; continue; }
            selected.set(clean, file);
        }
        render();
    }

    function render() {
        const entries = [...selected.entries()].sort(([a], [b]) => a.localeCompare(b));
        const total = entries.reduce((sum, [, f]) => sum + f.size, 0);
        const errors = [];
        if (entries.length > limits.files) errors.push(`Has seleccionado ${entries.length} archivos y el máximo por subida es ${limits.files}.`);
        if (total > limits.totalBytes) errors.push(`La subida ocupa ${formatBytes(total)} y el máximo es ${formatBytes(limits.totalBytes)}.`);
        const big = entries.filter(([, f]) => f.size > limits.fileBytes);
        if (big.length) errors.push(`${big.length} archivo(s) superan ${formatBytes(limits.fileBytes)}: ${big.slice(0, 3).map(([p]) => p).join(', ')}${big.length > 3 ? '…' : ''}`);

        summary.textContent = entries.length
            ? `${entries.length} archivo(s) · ${formatBytes(total)}${ignored ? ` · ${ignored} omitido(s) (.git, node_modules, archivos del sistema)` : ''}`
            : 'Ningún archivo seleccionado.';
        problems.innerHTML = '';
        for (const e of errors) {
            const li = document.createElement('li');
            li.textContent = e;
            problems.appendChild(li);
        }
        problems.hidden = errors.length === 0;
        submit.disabled = entries.length === 0 || errors.length > 0;

        list.innerHTML = '';
        const shown = entries.slice(0, 300);
        for (const [path, file] of shown) {
            const row = document.createElement('li');
            row.className = 'flex items-center gap-3 px-3 py-1.5 text-sm';
            const name = document.createElement('span');
            name.className = 'min-w-0 flex-1 truncate font-mono text-xs' + (file.size > limits.fileBytes ? ' text-rose-600' : ' text-zinc-700 dark:text-zinc-300');
            name.textContent = path;
            name.title = path;
            const size = document.createElement('span');
            size.className = 'shrink-0 text-xs text-zinc-500 dark:text-zinc-400';
            size.textContent = formatBytes(file.size);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'shrink-0 rounded px-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10';
            remove.textContent = '✕';
            remove.title = 'Quitar';
            remove.setAttribute('aria-label', 'Quitar ' + path);
            remove.addEventListener('click', () => { selected.delete(path); render(); });
            row.append(name, size, remove);
            list.appendChild(row);
        }
        if (entries.length > shown.length) {
            const more = document.createElement('li');
            more.className = 'px-3 py-1.5 text-xs text-zinc-500 dark:text-zinc-400';
            more.textContent = `… y ${entries.length - shown.length} archivo(s) más`;
            list.appendChild(more);
        }
        list.hidden = entries.length === 0;
    }

    fileInput.addEventListener('change', () => {
        add([...fileInput.files].map((file) => ({ file, path: file.name })));
        fileInput.value = '';
    });
    folderInput.addEventListener('change', () => {
        add([...folderInput.files].map((file) => ({ file, path: file.webkitRelativePath || file.name })));
        folderInput.value = '';
    });
    root.querySelector('[data-upload-clear]').addEventListener('click', () => { selected.clear(); ignored = 0; render(); });

    ['dragenter', 'dragover'].forEach((type) => dropZone.addEventListener(type, (e) => {
        e.preventDefault();
        dropZone.classList.add('border-purple-500', 'bg-purple-50', 'dark:bg-purple-500/10');
    }));
    ['dragleave', 'drop'].forEach((type) => dropZone.addEventListener(type, () => {
        dropZone.classList.remove('border-purple-500', 'bg-purple-50', 'dark:bg-purple-500/10');
    }));
    dropZone.addEventListener('drop', async (e) => {
        e.preventDefault();
        const entries = [...e.dataTransfer.items].map((item) => item.webkitGetAsEntry?.()).filter(Boolean);
        if (entries.length) {
            add((await Promise.all(entries.map(readEntry))).flat());
        } else {
            add([...e.dataTransfer.files].map((file) => ({ file, path: file.name })));
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        if (submit.disabled) return;

        const data = new FormData(form);
        data.delete('files[]');
        for (const [path, file] of selected) {
            data.append('files[]', file, file.name);
            data.append('paths[]', path);
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        progress.hidden = false;
        submit.disabled = true;

        xhr.upload.addEventListener('progress', (ev) => {
            if (!ev.lengthComputable) return;
            const pct = Math.round((ev.loaded / ev.total) * 100);
            bar.style.width = `${pct}%`;
            progressText.textContent = pct < 100 ? `Subiendo… ${pct}% (${formatBytes(ev.loaded)} de ${formatBytes(ev.total)})` : 'Cifrando y guardando el commit…';
        });
        xhr.addEventListener('load', () => {
            let body = {};
            try { body = JSON.parse(xhr.responseText); } catch { /* respuesta no JSON */ }
            if (xhr.status >= 200 && xhr.status < 300 && body.redirect) {
                window.location.href = body.redirect;
                return;
            }
            progress.hidden = true;
            submit.disabled = false;
            const messages = body.errors ? Object.values(body.errors).flat() : [body.message || (xhr.status === 413 ? 'La subida es demasiado grande para el servidor.' : `Error ${xhr.status} al subir los archivos.`)];
            problems.innerHTML = '';
            for (const m of [...new Set(messages)]) {
                const li = document.createElement('li');
                li.textContent = m;
                problems.appendChild(li);
            }
            problems.hidden = false;
            problems.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        xhr.addEventListener('error', () => {
            progress.hidden = true;
            submit.disabled = false;
            problems.innerHTML = '<li>Se perdió la conexión durante la subida. Inténtalo de nuevo.</li>';
            problems.hidden = false;
        });
        xhr.send(data);
    });

    render();
}

document.querySelectorAll('[data-upload]').forEach(init);
