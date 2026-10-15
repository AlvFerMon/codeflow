@echo off
rem CodeFlow - actualizar el servidor de produccion (ver docs\DESPLIEGUE.md)
rem Uso: doble clic o "deploy.bat" en cmd, dentro de la carpeta del proyecto.
chcp 65001 >nul
cd /d "%~dp0"

if not exist .env (
    echo [codeflow] Falta el fichero .env. Copia prod.env.example como .env y rellenalo.
    exit /b 1
)
findstr /b /c:"COMPOSE_FILE=" .env >nul || (
    echo [codeflow] El .env no tiene COMPOSE_FILE: no parece el .env de produccion.
    exit /b 1
)

echo [1/4] Descargando los ultimos cambios de GitHub...
git pull --ff-only || goto :error

echo [2/4] Construyendo y arrancando los contenedores...
docker compose up -d --build --remove-orphans || goto :error

echo [3/4] Compilando CSS y JS...
docker compose run --rm assets || goto :error

echo [4/4] Reiniciando la app (migraciones y caches)...
docker compose restart app || goto :error
docker compose restart queue >nul 2>&1

echo.
echo [codeflow] Despliegue terminado. Estado de los servicios:
docker compose ps --format "table {{.Service}}\t{{.Status}}"
exit /b 0

:error
echo.
echo [codeflow] ERROR: el despliegue se ha detenido. Revisa el mensaje de arriba.
exit /b 1
