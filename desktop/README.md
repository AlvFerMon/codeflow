# CodeFlow Desktop

App de escritorio (Windows, también Linux) tipo GitHub Desktop: inicia sesión, clona repositorios a una
carpeta, ve qué archivos has cambiado y haz commit, push y pull con un botón.

## Cómo está hecha

- **Electron** (`src/main.js`): proceso principal. No habla con el servidor directamente: lanza la
  CLI `codeflow` (incluida en el instalador, en `resources/bin`) con `--json` y muestra el resultado.
  Así la lógica de sincronización (conflictos, base64, subidas por partes…) está en un único sitio.
- Seguridad: `contextIsolation`, `sandbox`, sin `nodeIntegration`, CSP estricta. La interfaz solo puede
  usar las funciones de `src/preload.js`; el proceso principal admite una lista blanca de comandos de la
  CLI y los comandos de repositorio solo se ejecutan en carpetas que la app conoce.
- Interfaz (`src/renderer`): HTML + CSS + JavaScript sin frameworks, con la paleta de la web.
- La lista de repositorios del equipo se guarda en `%APPDATA%\CodeFlow Desktop\repositorios.json`;
  la sesión es la misma que la de la CLI (`~/.codeflow/config.json`).

## Desarrollo

```powershell
cd desktop
npm install
npm start          # usa ..\cli\dist\codeflow.exe
```

## Crear el instalador

```powershell
# Necesita cli\dist\codeflow.exe (se compila con Go: cd cli; go build -o dist\codeflow.exe .)
cd desktop
npm install
npm run dist:win   # genera dist\CodeFlow-Setup-1.0.0.exe
```

El instalador no está firmado: Windows SmartScreen mostrará «Windows protegió su PC» →
«Más información» → «Ejecutar de todas formas».
