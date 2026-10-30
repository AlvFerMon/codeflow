// CodeFlow Desktop — interfaz. Todo pasa por window.codeflow (preload), que lanza la CLI.
'use strict';

const $ = (id) => document.getElementById(id);
const cf = window.codeflow;

const state = {
  repos: [],          // rutas locales
  info: {},           // ruta -> status JSON (o {error})
  current: null,      // ruta seleccionada
  tab: 'changes',
  cloneRepos: [],
  cloneSelected: null,
  busy: false,
};

const KIND_ICON = { nuevo: '+', modificado: '~', borrado: '−' };

// ── Utilidades ──────────────────────────────────────────────────────────────
function el(tag, attrs = {}, ...children) {
  const node = document.createElement(tag);
  for (const [k, v] of Object.entries(attrs)) {
    if (k === 'class') node.className = v;
    else if (k === 'text') node.textContent = v;
    else if (k.startsWith('on')) node.addEventListener(k.slice(2), v);
    else node.setAttribute(k, v);
  }
  for (const c of children) if (c != null) node.append(c);
  return node;
}

let toastTimer;
function toast(msg, isError = false) {
  const t = $('toast');
  t.textContent = msg;
  t.classList.toggle('error', isError);
  t.hidden = false;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (t.hidden = true), isError ? 9000 : 4500);
}

function setBusy(on, text = 'Trabajando…') {
  state.busy = on;
  $('busy-text').textContent = text;
  $('busy').hidden = !on;
}

function parseJSON(text) {
  try {
    return JSON.parse(text);
  } catch {
    return null;
  }
}

async function cli(args, opts = {}) {
  return cf.cli(args, opts);
}

// Ejecuta una acción con el indicador de "trabajando" y muestra el resultado
async function action(text, args, opts = {}) {
  setBusy(true, text);
  try {
    const res = await cli(args, opts);
    if (!res.ok) toast(res.stderr || res.stdout || 'Algo ha fallado.', true);
    else if (opts.showOutput !== false && res.stdout.trim()) toast(res.stdout.trim());
    return res;
  } finally {
    setBusy(false);
  }
}

function fmtDate(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return isNaN(d) ? iso : d.toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
}

// ── Sesión ──────────────────────────────────────────────────────────────────
async function start() {
  const res = await cli(['whoami', '--json']);
  const me = res.ok && parseJSON(res.stdout);
  if (me && me.user) showApp(me);
  else showLogin();
}

function showLogin(error) {
  $('view-app').hidden = true;
  $('view-login').hidden = false;
  $('login-error').hidden = !error;
  $('login-error').textContent = error || '';
  $('login-email').focus();
}

async function showApp(me) {
  $('view-login').hidden = true;
  $('view-app').hidden = false;
  $('account-name').textContent = me.user.name || me.user.email;
  $('account-server').textContent = me.server.replace(/^https?:\/\//, '');
  await loadRepos();
}

$('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const server = $('login-server').value.trim();
  const email = $('login-email').value.trim();
  const password = $('login-password').value;
  $('login-submit').disabled = true;
  $('login-error').hidden = true;
  try {
    const res = await cli(['login', server, '--email', email, '--password-stdin', '--json'], { stdin: password });
    const me = res.ok && parseJSON(res.stdout);
    if (!me) return showLogin(res.stderr || 'No se ha podido iniciar sesión.');
    $('login-password').value = '';
    showApp(me);
  } finally {
    $('login-submit').disabled = false;
  }
});

$('btn-logout').addEventListener('click', async () => {
  await cli(['logout']);
  state.current = null;
  showLogin();
});

// ── Lista de repositorios ───────────────────────────────────────────────────
async function loadRepos() {
  state.repos = await cf.repos.list();
  if (state.current && !state.repos.includes(state.current)) state.current = null;
  await Promise.all(state.repos.map(refreshInfo));
  renderRepoList();
  renderRepo();
}

async function refreshInfo(dir) {
  const res = await cli(['status', '--json'], { cwd: dir });
  state.info[dir] = (res.ok && parseJSON(res.stdout)) || { error: res.stderr || 'No se pudo leer el estado.' };
}

function renderRepoList() {
  const nav = $('repo-list');
  nav.replaceChildren();
  if (!state.repos.length) {
    nav.append(el('p', { class: 'muted small', text: 'Aún no hay repositorios en este equipo.' }));
  }
  for (const dir of state.repos) {
    const info = state.info[dir] || {};
    const dirty = info.changes && (info.changes.length || info.pending.length);
    const name = el('span', { text: info.repository || dir.split(/[\\/]/).pop() });
    if (dirty) name.append(el('span', { class: 'dot', title: 'Tiene cambios' }));
    nav.append(
      el('button', {
        class: 'repo-item' + (dir === state.current ? ' active' : ''),
        type: 'button',
        onclick: () => selectRepo(dir),
      }, el('span', { class: 'project', text: info.project || (info.error ? 'Error' : '') }), name),
    );
  }
}

async function selectRepo(dir) {
  state.current = dir;
  renderRepoList();
  await refreshInfo(dir);
  renderRepoList();
  renderRepo();
  loadBranches();
  if (state.tab === 'history') loadHistory();
}

$('btn-add').addEventListener('click', async () => {
  const dir = await cf.pickFolder('Elige la carpeta de un repositorio clonado');
  if (!dir) return;
  const res = await cf.repos.add(dir);
  if (!res.ok) return toast(res.error, true);
  await loadRepos();
  selectRepo(res.list.find((p) => p.toLowerCase() === dir.toLowerCase()) || dir);
});

// Instala la CLI que lleva la app (codeflow install): la copia a la carpeta del usuario y la añade al PATH
$('btn-terminal').addEventListener('click', async () => {
  const res = await action('Instalando el comando codeflow…', ['install'], { showOutput: false });
  if (res.ok) toast('Listo. Abre una terminal nueva (PowerShell o cmd) y escribe:\n  codeflow repos');
});

// ── Vista del repositorio ───────────────────────────────────────────────────
function renderRepo() {
  const dir = state.current;
  $('empty').hidden = !!dir;
  $('repo').hidden = !dir;
  if (!dir) return;
  const info = state.info[dir] || {};

  $('repo-project').textContent = info.project || '';
  $('repo-name').textContent = info.repository || dir.split(/[\\/]/).pop();
  $('repo-path').textContent = dir;

  const pending = info.pending || [];
  $('push-count').hidden = !pending.length;
  $('push-count').textContent = pending.length;

  const changes = info.changes || [];
  $('changes-count').hidden = !changes.length;
  $('changes-count').textContent = changes.length;

  const list = $('changes');
  list.replaceChildren();
  if (info.error) {
    list.append(el('li', { class: 'placeholder error', text: info.error }));
  } else if (!changes.length) {
    list.append(el('li', { class: 'placeholder', text: 'No hay cambios. Edita archivos en la carpeta y aparecerán aquí.' }));
  }
  for (const c of changes) {
    list.append(
      el('li', {},
        el('span', { class: `kind ${c.kind}`, title: c.kind, text: KIND_ICON[c.kind] || '?' }),
        el('span', { class: 'path', title: c.path, text: c.path }),
        el('button', {
          class: 'btn ghost small undo', type: 'button', title: 'Deshacer el cambio de este archivo',
          onclick: () => undoChange(c.path),
        }, 'Deshacer'),
      ),
    );
  }
  for (const b of document.querySelectorAll('#commit-form button')) b.disabled = !changes.length;

  const pl = $('pending');
  pl.replaceChildren();
  if (!pending.length) pl.append(el('li', { class: 'placeholder', text: 'Todo está subido.' }));
  for (const p of pending) {
    pl.append(el('li', {},
      el('strong', { text: p.message }),
      el('div', { class: 'files', text: `${p.changes.length} archivo(s) · ${fmtDate(p.created_at)}` }),
    ));
  }
  $('btn-push').disabled = !pending.length;
}

async function loadBranches() {
  const dir = state.current;
  const select = $('branch-select');
  const res = await cli(['branch', '--json'], { cwd: dir });
  if (dir !== state.current) return;
  const list = (res.ok && parseJSON(res.stdout)) || [];
  select.replaceChildren();
  for (const b of list) {
    const opt = el('option', { value: b.name, text: b.name + (b.default ? ' (principal)' : '') });
    if (b.current) opt.selected = true;
    select.append(opt);
  }
  if (!list.length) {
    const branch = state.info[dir]?.branch;
    if (branch) select.append(el('option', { value: branch, text: branch }));
  }
}

async function loadHistory() {
  const dir = state.current;
  const ul = $('history');
  ul.replaceChildren(el('li', { class: 'placeholder', text: 'Cargando…' }));
  const res = await cli(['log', '--json'], { cwd: dir });
  if (dir !== state.current) return;
  const data = res.ok && parseJSON(res.stdout);
  ul.replaceChildren();
  if (!data) return ul.append(el('li', { class: 'placeholder error', text: res.stderr || 'No se pudo cargar el historial.' }));
  for (const msg of data.pending) {
    ul.append(el('li', { class: 'local' },
      el('span', { class: 'sha', text: 'local' }),
      el('span', { text: msg }),
      el('span', { class: 'meta', text: 'sin subir' }),
    ));
  }
  for (const c of data.commits) {
    const msg = el('span', { text: c.message });
    if (c.current) msg.append(el('span', { class: 'here', text: '● tu versión' }));
    ul.append(el('li', {},
      el('span', { class: 'sha', text: c.sha.slice(0, 7) }),
      msg,
      el('span', { class: 'meta', text: `${c.author} · ${fmtDate(c.date)}` }),
    ));
  }
  if (!data.pending.length && !data.commits.length) ul.append(el('li', { class: 'placeholder', text: 'Sin commits todavía.' }));
}

async function refreshCurrent() {
  if (!state.current) return;
  await refreshInfo(state.current);
  renderRepoList();
  renderRepo();
  if (state.tab === 'history') loadHistory();
}

async function undoChange(path) {
  if (!confirm(`¿Deshacer los cambios de ${path}? Se perderá lo que hayas cambiado en ese archivo.`)) return;
  const res = await action('Deshaciendo…', ['restore', path], { cwd: state.current });
  if (res.ok) refreshCurrent();
}

// Pestañas
for (const tab of document.querySelectorAll('.tab')) {
  tab.addEventListener('click', () => {
    state.tab = tab.dataset.tab;
    for (const t of document.querySelectorAll('.tab')) t.classList.toggle('active', t === tab);
    $('tab-changes').hidden = state.tab !== 'changes';
    $('tab-history').hidden = state.tab !== 'history';
    if (state.tab === 'history') loadHistory();
  });
}

// Commit (y push opcional)
$('commit-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const message = $('commit-message').value.trim();
  if (!message) return;
  const alsoPush = e.submitter?.dataset.push === '1';
  const res = await action('Guardando commit…', ['commit', '-m', message], { cwd: state.current, showOutput: !alsoPush });
  if (!res.ok) return;
  $('commit-message').value = '';
  if (alsoPush) await action('Subiendo…', ['push'], { cwd: state.current });
  refreshCurrent();
});

$('btn-push').addEventListener('click', async () => {
  await action('Subiendo…', ['push'], { cwd: state.current });
  refreshCurrent();
});

$('btn-pull').addEventListener('click', async () => {
  const res = await action('Descargando cambios…', ['pull', '--json'], { cwd: state.current, showOutput: false });
  if (res.ok) toast(res.stdout.trim() || 'Ya tienes la última versión.');
  else {
    const data = parseJSON(res.stdout);
    if (data?.conflicts) {
      toast(
        'No se ha descargado nada: estos archivos han cambiado en el servidor y también en tu carpeta:\n' +
        data.conflicts.map((p) => '  • ' + p).join('\n') +
        '\nGuarda una copia de tus versiones, pulsa «Deshacer» en esos archivos y vuelve a hacer pull.',
        true,
      );
    }
  }
  refreshCurrent();
});

$('branch-select').addEventListener('change', async (e) => {
  const branch = e.target.value;
  const res = await action(`Cambiando a ${branch}…`, ['checkout', branch], { cwd: state.current });
  if (!res.ok) loadBranches();
  refreshCurrent();
});

$('btn-refresh').addEventListener('click', refreshCurrent);
$('btn-folder').addEventListener('click', () => cf.openFolder(state.current));
$('btn-web').addEventListener('click', () => {
  const url = state.info[state.current]?.url;
  if (url) cf.openUrl(url);
});

// ── Clonar ──────────────────────────────────────────────────────────────────
async function openClone() {
  state.cloneSelected = null;
  $('clone-error').hidden = true;
  $('clone-search').value = '';
  $('clone-dest').value = $('clone-dest').value || '';
  updateCloneSubmit();
  $('clone-dialog').showModal();
  $('clone-list').replaceChildren(el('li', { class: 'placeholder', text: 'Cargando repositorios…' }));
  const res = await cli(['repos', '--json']);
  state.cloneRepos = (res.ok && parseJSON(res.stdout)) || [];
  if (!res.ok) showCloneError(res.stderr || 'No se pudieron cargar tus repositorios.');
  renderCloneList();
}

function renderCloneList() {
  const q = $('clone-search').value.trim().toLowerCase();
  const ul = $('clone-list');
  ul.replaceChildren();
  const items = state.cloneRepos.filter((r) => `${r.project}/${r.name} ${r.description || ''}`.toLowerCase().includes(q));
  if (!items.length) ul.append(el('li', { class: 'placeholder', text: state.cloneRepos.length ? 'Ningún repositorio coincide.' : 'No tienes repositorios todavía.' }));
  for (const r of items) {
    ul.append(el('li', {}, el('button', {
      type: 'button',
      'aria-pressed': String(state.cloneSelected?.id === r.id),
      onclick: () => { state.cloneSelected = r; renderCloneList(); updateCloneSubmit(); },
    }, el('strong', { text: `${r.project} / ${r.name}` }), el('span', { class: 'desc', text: r.description || r.visibility || '' }))));
  }
}

function updateCloneSubmit() {
  $('clone-submit').disabled = !(state.cloneSelected && $('clone-dest').value);
}

function showCloneError(msg) {
  $('clone-error').textContent = msg;
  $('clone-error').hidden = false;
}

$('btn-clone').addEventListener('click', openClone);
$('btn-clone-empty').addEventListener('click', openClone);
$('clone-search').addEventListener('input', renderCloneList);
$('clone-pick').addEventListener('click', async () => {
  const dir = await cf.pickFolder('¿Dónde guardo el repositorio?');
  if (dir) $('clone-dest').value = dir;
  updateCloneSubmit();
});

$('clone-submit').addEventListener('click', async () => {
  const r = state.cloneSelected;
  const parent = $('clone-dest').value;
  if (!r || !parent) return;
  const dest = await cf.join(parent, r.name);
  $('clone-error').hidden = true;
  setBusy(true, `Clonando ${r.name}…`);
  let res;
  try {
    res = await cli(['clone', r.url, dest]);
  } finally {
    setBusy(false);
  }
  if (!res.ok) return showCloneError(res.stderr || 'No se pudo clonar.');
  const added = await cf.repos.add(dest);
  if (!added.ok) return showCloneError(added.error);
  $('clone-dialog').close();
  toast(`Clonado en ${dest}`);
  await loadRepos();
  selectRepo(added.list.find((p) => p.endsWith(r.name)) || dest);
});

// ── Refresco automático ─────────────────────────────────────────────────────
window.addEventListener('focus', () => !state.busy && refreshCurrent());
setInterval(() => {
  if (!state.busy && document.hasFocus() && !$('view-app').hidden) refreshCurrent();
}, 10000);

start();
