#!/bin/sh
# Copia de seguridad periódica de CodeFlow (servicio "backup" de docker-compose.prod.yml).
# Genera en /backups:  codeflow-AAAAMMDD-HHMM.sql.gz (o .sql)  y  backend-env-AAAAMMDD-HHMM.txt
set -u
KEEP="${BACKUP_KEEP_DAYS:-14}"
EVERY="${BACKUP_EVERY_HOURS:-24}"
mkdir -p /backups

while true; do
    STAMP="$(date +%Y%m%d-%H%M)"
    echo "[backup] $(date '+%F %T') copia $STAMP"
    OUT="/backups/codeflow-$STAMP.sql"
    if mysqldump -h mysql -u root --single-transaction --quick --routines --triggers \
        --max-allowed-packet=512M "$DB_DATABASE" > "$OUT.tmp"; then
        mv "$OUT.tmp" "$OUT"
        # Comprimir si gzip está disponible (si no, se deja el .sql)
        command -v gzip >/dev/null 2>&1 && gzip -f "$OUT" && OUT="$OUT.gz"
        cp /codeflow/backend/.env "/backups/backend-env-$STAMP.txt" 2>/dev/null || true
        echo "[backup] OK $OUT ($(du -h "$OUT" | cut -f1))"
    else
        rm -f "$OUT.tmp"
        echo "[backup] ERROR al volcar la base de datos"
    fi
    # Borrar copias antiguas
    find /backups -name 'codeflow-*.sql*' -mtime +"$KEEP" -delete
    find /backups -name 'backend-env-*.txt' -mtime +"$KEEP" -delete
    sleep $((EVERY * 3600))
done
