// Puente seguro entre la interfaz y el proceso principal: solo estas funciones.
const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('codeflow', {
  cli: (args, opts = {}) => ipcRenderer.invoke('cli', { args, cwd: opts.cwd, stdin: opts.stdin }),
  repos: {
    list: () => ipcRenderer.invoke('repos:list'),
    add: (dir) => ipcRenderer.invoke('repos:add', dir),
    remove: (dir) => ipcRenderer.invoke('repos:remove', dir),
  },
  pickFolder: (title) => ipcRenderer.invoke('dialog:folder', title),
  openFolder: (dir) => ipcRenderer.invoke('shell:folder', dir),
  openUrl: (url) => ipcRenderer.invoke('shell:url', url),
  join: (...parts) => ipcRenderer.invoke('path:join', ...parts),
});
