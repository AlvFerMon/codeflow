# Despliegue de CodeFlow en un servidor propio (Windows + Docker)

Servidor: PC con Windows, Docker Desktop, i7-11700F, 16 GB de RAM y GTX 1660 Super (6 GB).
La web se publica con HTTPS en un subdominio gratuito de DuckDNS, redirigiendo los puertos 80 y 443 del router.

```
Internet ─ router (80/443) ─ PC servidor ─ Docker
                                            ├─ caddy     HTTPS (Let's Encrypt) — único servicio abierto hacia fuera
                                            ├─ web       nginx
                                            ├─ app       Laravel (PHP-FPM)
                                            ├─ queue     análisis con IA en segundo plano
                                            ├─ ollama    modelo de IA en la GPU
                                            ├─ mysql     base de datos (repositorios cifrados)
                                            ├─ mailpit   correos (hasta configurar un SMTP real)
                                            ├─ duckdns   mantiene el subdominio apuntando a la IP de casa
                                            └─ backup    copia diaria en la carpeta backups\
```

Ficheros implicados: `docker-compose.prod.yml` (se suma a `docker-compose.yml`), `docker/caddy/Caddyfile`,
`docker/backup/backup.sh`, `prod.env.example` y `deploy.bat`.

---

## 1. Preparar Windows (una sola vez)

1. **Docker Desktop** con el motor WSL2. En *Settings → General* activar **Start Docker Desktop when you sign in**.
2. **Driver NVIDIA** actualizado (GeForce Experience o nvidia.com). Comprobar en cmd: `nvidia-smi`.
3. **Git para Windows** e iniciar sesión en GitHub la primera vez que se haga `git clone` (el repositorio es privado).
4. **Que el PC no se apague ni se suspenda**: *Configuración → Sistema → Inicio/apagado y suspensión* → Suspender: **Nunca**.
5. **Inicio de sesión automático** (Docker Desktop solo arranca al iniciar sesión): `Win + R` → `netplwiz` →
   desmarcar *Los usuarios deben escribir su nombre y contraseña*. Si no aparece la casilla, usar *Autologon* de Sysinternals.
6. **BIOS**: activar *Restore on AC Power Loss → Power On* para que el PC arranque solo tras un corte de luz.
7. **Windows Update**: configurar *horas activas* para que no reinicie en mitad del día.

## 2. Red

1. **IP fija en la red local**: en el router, reservar una IP (DHCP estático) para el PC servidor, p. ej. `192.168.1.50`.
2. **Redirigir puertos** en el router hacia esa IP (igual que con los servidores de Minecraft):

   | Puerto externo | Puerto interno | Protocolo |
   |---|---|---|
   | 80 | 80 | TCP |
   | 443 | 443 | TCP (y UDP si el router lo permite) |

3. **Firewall de Windows** (cmd como administrador):
   ```
   netsh advfirewall firewall add rule name="CodeFlow HTTP/HTTPS" dir=in action=allow protocol=TCP localport=80,443
   ```
4. **DuckDNS**: entrar en https://www.duckdns.org (con Google o GitHub), crear el subdominio (p. ej. `codeflow-tfg`)
   y copiar el **token**. El token solo va en el `.env` del servidor, nunca al repositorio.

> No hace falta abrir el puerto de MySQL, de Ollama ni de Mailpit: solo se ven desde el propio servidor.

## 3. Instalar

En cmd, en la carpeta donde vaya a vivir el proyecto (p. ej. `C:\CodeFlow`):

```
git clone https://github.com/AlvFerMon/codeflow.git C:\CodeFlow
cd C:\CodeFlow
copy prod.env.example .env
notepad .env
```

En el `.env` cambiar **todas** las contraseñas y poner `CODEFLOW_DOMAIN`, `DUCKDNS_SUBDOMAIN` y `DUCKDNS_TOKEN`.
Después:

```
docker compose up -d --build
docker compose run --rm assets
docker compose exec ollama ollama pull qwen2.5-coder:7b
docker compose ps
```

La primera vez tarda unos minutos (dependencias, migraciones y certificado HTTPS).
Al terminar, la web está en `https://<subdominio>.duckdns.org` y se entra con `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD`.

**Muy importante:** al primer arranque se generan `APP_KEY` y `CODEFLOW_MASTER_KEY` en `backend\.env`.
Sin esas claves los repositorios cifrados no se pueden recuperar: guarda una copia de `backend\.env` fuera del servidor.

### Probar antes de abrir puertos

Con `CODEFLOW_DOMAIN=localhost` y `COMPOSE_PROFILES=ia` (sin `duckdns`), la web queda en `https://localhost`
en el propio servidor, con un certificado local (el navegador avisa la primera vez; se puede continuar).

## 4. Comprobaciones

| Qué | Comando | Resultado esperado |
|---|---|---|
| Servicios | `docker compose ps` | todos *running* / *healthy* |
| Certificado | `docker compose logs caddy` | `certificate obtained successfully` |
| IP de DuckDNS | `docker compose logs duckdns` | `Your IP was updated` |
| IA en la GPU | `docker compose exec ollama ollama ps` (tras un análisis) | `100% GPU` |
| Cola de IA | `docker compose logs --tail 30 queue` | `RunPullRequestAnalysis ... DONE` |
| Copias | carpeta `backups\` | `codeflow-AAAAMMDD-HHMM.sql.gz` |

## 5. Actualizar

Después de subir cambios a `main` en GitHub, en el servidor: **doble clic en `deploy.bat`**
(o `deploy.bat` en cmd). Hace `git pull`, reconstruye, compila CSS/JS y reinicia la app (que migra la base de datos).

## 6. Correo

Mientras no se configure un SMTP, los correos (códigos de verificación) se quedan en Mailpit:
`http://localhost:8025` **en el servidor**. Los usuarios externos no los reciben.
Para enviarlos de verdad, rellenar en `.env` las variables `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
`MAIL_PASSWORD` y `MAIL_FROM_ADDRESS` (ejemplo de Gmail en `prod.env.example`) y ejecutar `docker compose up -d`.

## 7. Copias de seguridad

El servicio `backup` vuelca la base de datos cada `BACKUP_EVERY_HOURS` horas en `backups\` y guarda a su lado una
copia de `backend\.env` (claves). Se conservan `BACKUP_KEEP_DAYS` días. Copia esa carpeta también fuera del PC
(OneDrive, disco externo...).

Restaurar una copia (descomprimir antes el `.sql.gz`, p. ej. con 7-Zip):

```
docker compose exec -T mysql sh -c "mysql -u root -p\"$MYSQL_ROOT_PASSWORD\" codeflow" < backups\codeflow-AAAAMMDD-HHMM.sql
```

y dejar en `backend\.env` las claves `APP_KEY` y `CODEFLOW_MASTER_KEY` de esa misma copia.

## 8. App Android

En la pantalla de inicio de sesión, elegir servidor: `https://<subdominio>.duckdns.org/api/`.

## 9. Problemas frecuentes

| Síntoma | Causa probable |
|---|---|
| Caddy no consigue el certificado | Puertos 80/443 no redirigidos, firewall, o DuckDNS aún sin la IP |
| `ports are not available: ... 80` | Otro programa usa el puerto 80/443 (IIS, Skype, otro servidor web) |
| La web va desde casa pero no desde fuera (o al revés) | Algunos routers no permiten entrar a la IP pública desde dentro: probar con datos móviles |
| La IA tarda mucho / `ollama ps` dice CPU | Driver NVIDIA o Docker Desktop sin GPU; probar `docker run --rm --gpus all nvidia/cuda:12.6.2-base-ubuntu24.04 nvidia-smi` |
| Tras un reinicio no hay web | No se ha iniciado sesión en Windows (Docker Desktop no arranca): revisar el paso 1.5 |
