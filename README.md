# CodeFlow

Plataforma colaborativa para equipos de desarrollo (TFG · 2.º DAM), inspirada en GitHub/GitLab:
proyectos, miembros con roles, repositorios con **control de versiones propio y cifrado**, ramas,
commits, issues, pull requests con revisiones y fusión, actividad y notificaciones.
Incluye una app Android (Kotlin + Jetpack Compose) que consume la API REST.

## Estructura del repositorio

```
backend/   Laravel 13 (API REST + web Blade/Tailwind)
android/   App Android (Kotlin, Compose, Retrofit)
docker/    Imágenes y configuración (PHP-FPM, nginx)
docs/      Documentación del TFG (ARQUITECTURA.md)
docker-compose.yml
```

## Puesta en marcha con Docker

Requisitos: Docker Desktop.

```powershell
copy .env.example .env      # y cambia las contraseñas
docker compose up -d --build
docker compose logs -f app  # espera a ver "[codeflow] Listo"
```

| Servicio | URL |
|---|---|
| Web | http://localhost:8080 |
| API | http://localhost:8080/api |
| Correos de prueba (Mailpit) | http://localhost:8025 |
| MySQL (DBeaver, etc.) | `localhost:3307`, usuario/contraseña de `.env` (`DB_USERNAME` / `DB_PASSWORD`) |

En DBeaver, si aparece "Public Key Retrieval is not allowed", pon `allowPublicKeyRetrieval=true`
en las propiedades del driver.

El primer arranque genera en `backend/.env` la `APP_KEY` y la **`CODEFLOW_MASTER_KEY`** (clave maestra
del cifrado de repositorios). **Haz copia de `backend/.env`**: si se pierde la clave maestra, el código
de los repositorios no se puede descifrar.

Tras cambiar vistas o estilos: `docker compose run --rm assets` (recompila CSS/JS).

### Subida de archivos y carpetas

Desde *Código → Subir archivos o carpetas* se pueden seleccionar archivos, una carpeta completa o arrastrar
archivos y carpetas; se conserva la estructura y se omiten `.git`, `node_modules` y archivos del sistema.
Límites por defecto (variables de `.env`): `CODEFLOW_MAX_FILE_MB=50` por archivo, `CODEFLOW_MAX_UPLOAD_MB=500`
por subida y `CODEFLOW_MAX_FILES=1000` archivos por subida. Para subirlos más, ajusta también
`docker/php/php.ini` (`upload_max_filesize`, `post_max_size`, `max_file_uploads`), `client_max_body_size` en
`docker/nginx/default.conf` y `--max-allowed-packet` de MySQL en `docker-compose.yml`, y reconstruye con
`docker compose up -d --build`. Un archivo ocupa unas 2,4 veces su tamaño en la base de datos al cifrarse.

### Rendimiento en Docker (Windows)

- `vendor/` y las cachés de Laravel viven en volúmenes de Docker (sistema de archivos Linux); solo el código
  fuente se monta desde Windows. OPcache revisa cambios cada 2 s y nginx comprime y cachea los assets.
- Al arrancar se ejecuta `php artisan optimize` (config, rutas, eventos y vistas en caché). Si cambias rutas,
  configuración o `.env`: `docker compose restart app`. Para desactivarlo: `CODEFLOW_OPTIMIZE=false` en `.env`.
- Con `APP_DEBUG=true` cada respuesta incluye la cabecera **`Server-Timing`** (tiempo total, tiempo de base de
  datos y nº de consultas): DevTools del navegador → pestaña *Network* → petición → *Timing*.
- Si aun así va lento, la mejora mayor es clonar el proyecto dentro de WSL (`\\wsl$\Ubuntu\home\...`) en
  lugar de `C:\Users\...`: Docker Desktop lee los archivos de Windows mucho más despacio.

### Registro y verificación del email

Al crear una cuenta (en la web o desde la app con `POST /api/auth/register`) se envía un correo con un
**código de 6 dígitos**. En desarrollo los correos no salen a Internet: se ven en **Mailpit**
(http://localhost:8025).

- Hasta verificar, la web solo muestra la pantalla «Esperando la verificación de tu email» (introducir el
  código, reenviarlo, corregir el email o cerrar sesión). La API responde `403` con `"code": "email_not_verified"`.
- El código caduca a los 30 minutos y admite 5 intentos; se guarda solo su hash.
- API: `POST /api/auth/email/verify` con `{"code": "123456"}` y `POST /api/auth/email/verification-notification` para reenviarlo.
- Los visitantes sin sesión siguen viendo los repositorios públicos como siempre.
- Las cuentas que ya existían se dan por verificadas (migración `2026_10_09_000001`).
- Para enviar correos reales, cambia en `.env` las variables `MAIL_*` (por ejemplo, SMTP de Gmail con una contraseña de aplicación).

### Usuarios de prueba

Se crean automáticamente (`CODEFLOW_SEED_DEMO=true`) con las contraseñas de `.env`:

| Usuario | Rol |
|---|---|
| `admin@codeflow.test` (`SEED_ADMIN_PASSWORD`) | Administrador de la plataforma |
| `propietaria@codeflow.test` (`SEED_DEMO_PASSWORD`) | Propietaria del "Proyecto DAM" |
| `colaborador@codeflow.test` (`SEED_DEMO_PASSWORD`) | Colaborador |
| `observadora@codeflow.test` (`SEED_DEMO_PASSWORD`) | Observadora (solo lectura + comentarios) |

## Roles

- **Administrador**: acceso total.
- **Propietario**: gestiona el proyecto, sus miembros y roles, repositorios y visibilidad.
- **Colaborador**: sube código, ramas, issues y pull requests, revisa y fusiona PR aprobados.
- **Observador**: solo lo añade el propietario; lee todo (también repos privados) y solo puede escribir
  comentarios en issues y pull requests (no en el código). Sus comentarios llegan como **alerta** al
  apartado de notificaciones del resto de miembros.

Los repositorios **públicos** se pueden consultar sin iniciar sesión.

## Tests

```powershell
docker compose exec app php artisan test
```

Los tests usan siempre SQLite en memoria (forzado en `phpunit.xml`), nunca la base de datos MySQL del contenedor.

## Documentación

- [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md): arquitectura, modelo de datos, motor VCS, cifrado, seguridad y plan.
- [android/README.md](android/README.md): app Android.
