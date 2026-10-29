// CodeFlow Desktop — proceso principal de Electron.
// La interfaz no ejecuta nada por sí misma: pide al proceso principal que lance la CLI `codeflow`
// (incluida en la app) con una lista blanca de comandos, y la CLI habla con el servidor.
const { app, BrowserWindow, ipcMain, dialog, shell, Menu } = require('electron');
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const ALLOWED = new Set(['login', 'logout', 'whoami', 'repos', 'clone', 'status', 'commit', 'push', 'pull', 'restore', 'log', 'branch', 'checkout']);

function cliPath() {
  if (process.env.CODEFLOW_CLI) return process.env.CODEFLOW_CLI;
  const exe = process.platform === 'win32' ? 'codeflow.exe' : 'codeflow';
  if (app.isPackaged) return path.join(process.resourcesPath, 'bin', exe);
  const dev = process.platform === 'win32' ? 'codeflow.exe' : 'codeflow-linux';
  return path.join(__dirname, '..', '..', 'cli', 'dist', dev);
}

// ── Repositorios locales que la app recuerda ────────────────────────────────
const storeFile = () => path.join(app.getPath('userData'), 'repositorios.json');

function loadRepos() {
  try {
    const list = JSON.parse(fs.readFileSync(storeFile(), 'utf8'));
    return Array.isArray(list) ? list.filter((p) => fs.existsSync(path.join(p, '.codeflow', 'state.json'))) : [];
  } catch {
    return [];
  }
}

function saveRepos(list) {
  fs.mkdirSync(path.dirname(storeFile()), { recursive: true });
  fs.writeFileSync(storeFile(), JSON.stringify([...new Set(list)], null, 2));
}

function isKnownRepo(dir) {
  return loadRepos().some((p) => path.resolve(p) === path.resolve(dir));
}

// ── Ejecutar la CLI ─────────────────────────────────────────────────────────
function runCli(args, { cwd, stdin } = {}) {
  return new Promise((resolve) => {
    const child = spawn(cliPath(), args, { cwd: cwd || app.getPath('home'), windowsHide: true });
    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (d) => (stdout += d));
    child.stderr.on('data', (d) => (stderr += d));
    child.on('error', (err) => resolve({ ok: false, code: -1, stdout, stderr: `No se pudo ejecutar la CLI: ${err.message}` }));
    child.on('close', (code) => resolve({ ok: code === 0, code, stdout, stderr: stderr.replace(/^Error:\s*/, '').trim() }));
    if (stdin !== undefined) child.stdin.write(stdin + '\n');
    child.stdin.end();
  });
}

ipcMain.handle('cli', async (_event, { args, cwd, stdin }) => {
  if (!Array.isArray(args) || !ALLOWED.has(args[0]) || args.some((a) => typeof a !== 'string')) {
    return { ok: false, stderr: 'Comando no permitido.' };
  }
  // Los comandos de un repositorio solo se ejecutan en carpetas que la app conoce
  const repoCommand = !['login', 'logout', 'whoami', 'repos', 'clone'].includes(args[0]);
  if (repoCommand && (!cwd || !isKnownRepo(cwd))) {
    return { ok: false, stderr: 'Carpeta de repositorio desconocida.' };
  }
  return runCli(args, { cwd, stdin });
});

ipcMain.handle('repos:list', () => loadRepos());
ipcMain.handle('repos:add', (_e, dir) => {
  if (typeof dir !== 'string' || !fs.existsSync(path.join(dir, '.codeflow', 'state.json'))) {
    return { ok: false, error: 'Esa carpeta no es un repositorio de CodeFlow (no tiene .codeflow).' };
  }
  saveRepos([...loadRepos(), path.resolve(dir)]);
  return { ok: true, list: loadRepos() };
});
ipcMain.handle('repos:remove', (_e, dir) => {
  saveRepos(loadRepos().filter((p) => path.resolve(p) !== path.resolve(dir)));
  return loadRepos();
});

ipcMain.handle('dialog:folder', async (event, title) => {
  const win = BrowserWindow.fromWebContents(event.sender);
  const res = await dialog.showOpenDialog(win, { title: title || 'Elige una carpeta', properties: ['openDirectory', 'createDirectory'] });
  return res.canceled ? null : res.filePaths[0];
});

ipcMain.handle('shell:folder', (_e, dir) => (isKnownRepo(dir) ? shell.openPath(dir) : null));
ipcMain.handle('shell:url', (_e, url) => {
  if (typeof url === 'string' && /^https?:\/\//.test(url)) shell.openExternal(url);
});
ipcMain.handle('path:join', (_e, ...parts) => path.join(...parts));

// ── Ventana ─────────────────────────────────────────────────────────────────
function createWindow() {
  const win = new BrowserWindow({
    width: 1180,
    height: 760,
    minWidth: 860,
    minHeight: 560,
    title: 'CodeFlow',
    backgroundColor: '#0b0b10',
    icon: path.join(__dirname, '..', 'build', 'icon.png'),
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: true,
    },
  });
  win.loadFile(path.join(__dirname, 'renderer', 'index.html'));
  // Los enlaces externos se abren en el navegador, nunca dentro de la app
  win.webContents.setWindowOpenHandler(({ url }) => {
    if (/^https?:\/\//.test(url)) shell.openExternal(url);
    return { action: 'deny' };
  });
  win.webContents.on('will-navigate', (e) => e.preventDefault());
}

Menu.setApplicationMenu(null);
app.whenReady().then(createWindow);
app.on('window-all-closed', () => app.quit());
