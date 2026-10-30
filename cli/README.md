# CodeFlow CLI

Trabaja con los repositorios de CodeFlow desde la terminal (cmd, PowerShell, bash): `clone`, `status`,
`commit`, `push`, `pull`, ramas… Es un único ejecutable sin dependencias que habla con la API REST del
servidor. **No es Git**: CodeFlow tiene su propio control de versiones (con el código cifrado en el
servidor), y esta CLI es su cliente.

## Instalación

**Windows (lo más fácil):** descarga `codeflow.exe`, ábrelo con **doble clic** y responde «S». Se copia a
`%LOCALAPPDATA%\Programs\CodeFlow` y se añade a tu PATH de usuario (sin permisos de administrador). Abre una
terminal nueva y escribe `codeflow login`.

- Desde una terminal hace lo mismo: `.\codeflow.exe install`. Para quitarlo: `codeflow uninstall`.
- La **app de escritorio** trae la CLI dentro: botón «Usar CodeFlow en la terminal». Comparten la sesión
  (`~/.codeflow/config.json`), así que si ya entraste en la app, la terminal también está conectada.

**Linux / macOS:** `chmod +x codeflow-linux && ./codeflow-linux install` → se copia a `~/.local/bin/codeflow` y
se añade al PATH en `~/.profile` (y `~/.zshrc` / `~/.bashrc` si existen).

## Uso

```
codeflow login https://codeflow-backend-wheat.vercel.app     (pide correo y contraseña)
codeflow clone https://codeflow-backend-wheat.vercel.app/repositories/1 mi-carpeta
cd mi-carpeta

codeflow status                  qué has cambiado
codeflow commit -m "Arreglo el login"
codeflow push                    sube tus commits
codeflow pull                    baja los cambios de los demás
codeflow restore archivo.php     deshace tus cambios en un archivo (o "." para todos)
codeflow log                     historial de la rama
codeflow branch                  lista las ramas · codeflow branch feature/x  crea una
codeflow checkout feature/x      cambia de rama
codeflow repos                   lista los repositorios que puedes clonar
codeflow whoami · codeflow logout
```

Con `--json`, `status`, `log`, `branch`, `repos`, `whoami` y `login` devuelven JSON (lo usa la app de
escritorio). `login --email correo --password-stdin` lee la contraseña de la entrada estándar.

La URL de `clone` es la de la página del repositorio en la web (`…/repositories/<número>`).

## Cómo funciona

- Cada carpeta clonada tiene una subcarpeta `.codeflow/` (como `.git/`): servidor, repositorio, rama,
  el commit del servidor sobre el que trabajas y tus commits locales sin subir.
- `commit` guarda un commit **local** con todos los cambios (nuevos, modificados y borrados).
  `push` los sube en orden indicando al servidor sobre qué commit se hicieron: si alguien ha subido antes,
  el servidor lo rechaza (409) y la CLI pide hacer `pull` primero, así nunca se pisan cambios ajenos.
- `pull` descarga solo los archivos que han cambiado. Si un archivo ha cambiado en el servidor **y** en tu
  carpeta, no descarga nada y te avisa (conflicto): guarda tu versión, haz `restore` y repite el `pull`.
- Los commits muy grandes se suben en varias partes (el servidor en Vercel admite ~4,5 MB por petición).
- Los archivos binarios (imágenes, PDF…) viajan en base64 y llegan idénticos.
- La sesión (token) se guarda en `~/.codeflow/config.json` (Windows: `C:\Users\<usuario>\.codeflow`).

### `.codeflowignore`

Igual que `.gitignore`, una regla por línea: `carpeta/`, `*.log`, `secreto.env`, `src/*.tmp`.
Siempre se ignoran `.codeflow/`, `.git/`, `node_modules/`, `vendor/`, `.idea/`, `.vscode/` y similares.

## Compilar

Requiere Go 1.22 o superior: `./build.sh 1.0.0` genera `dist/codeflow.exe`, `dist/codeflow-linux` y
`dist/codeflow-macos`.

## API que usa

| Comando | Llamadas |
|---|---|
| login | `POST /api/auth/login` |
| repos | `GET /api/projects`, `GET /api/projects/{id}/repositories` |
| clone, checkout | `GET /api/repositories/{id}`, `GET /api/repositories/{id}/branches`, `GET /api/branches/{id}/manifest`, `POST /api/branches/{id}/files/batch` |
| push | `POST /api/branches/{id}/commits` con `base_commit_id` y contenido en base64 |
| pull | `GET /api/branches/{id}/manifest` + `POST /api/branches/{id}/files/batch` |
| log, branch | `GET /api/branches/{id}/commits`, `POST /api/repositories/{id}/branches` |
