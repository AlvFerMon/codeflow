# Integración continua y tareas automáticas

## GitHub Actions (`.github/workflows/ci.yml`)

En cada push a `main` y en cada pull request:

| Job | Qué comprueba |
|---|---|
| **Backend (sqlite)** | Estilo del código (Pint), compila el front y pasa todos los tests con SQLite |
| **Backend (pgsql)** | Los mismos tests contra PostgreSQL 16, el motor de Supabase (`phpunit.pgsql.xml`) |
| **CLI (Go)** | `go vet` y que compila para Linux y Windows |
| **Migraciones en Supabase** | Solo en `main` y si los anteriores pasan: `php artisan migrate --force` contra Supabase |

Si algo falla, el commit o el PR aparece en rojo en GitHub con el detalle del error.

Antes de subir, en local: `cd backend` → `vendor/bin/pint` (formatea) y `php artisan test`.

### Activar las migraciones automáticas

1. En Supabase: **Connect › Session pooler**, copia la cadena de conexión (`postgresql://postgres.xxxx:…@aws-0-eu-west-1.pooler.supabase.com:5432/postgres`).
2. En GitHub: **Settings › Secrets and variables › Actions › New repository secret**:
   - Nombre: `SUPABASE_DB_URL`
   - Valor: esa cadena (con la contraseña de la base de datos).
3. Desde ese momento, cada migración nueva se aplica sola al hacer push a `main`. Sin el secreto, el job se salta.

Opcional: para que Vercel despliegue **después** de migrar (y no a la vez):

- En Vercel crea un *Deploy Hook* (Settings › Git › Deploy Hooks).
- Guárdalo en GitHub como secreto `VERCEL_DEPLOY_HOOK`.
- En Vercel, desactiva el despliegue automático de `main` (Settings › Git › Ignored Build Step → `exit 0`).

## Tareas diarias

`php artisan codeflow:daily` hace la limpieza diaria:

- borra definitivamente lo que lleva más de 30 días en la papelera;
- elimina las invitaciones caducadas;
- elimina las sesiones web caducadas;
- elimina los códigos de restablecer contraseña caducados.

- **Servidor propio (Docker):** el programador de Laravel la lanza a las 04:00 (`routes/console.php`).
- **Vercel:** `vercel.json` define un *cron* diario que llama a `GET /api/cron/daily`.
  - Hay que crear en Vercel la variable **`CRON_SECRET`** con un valor largo aleatorio.
  - Vercel la envía en la cabecera `Authorization: Bearer …`.
  - Sin esa variable, la URL responde 401 y no hace nada.
