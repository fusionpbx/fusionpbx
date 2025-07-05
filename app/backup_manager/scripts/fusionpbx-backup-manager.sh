//------------------------------------------------------------------------------
// scripts/fusionpbx-backup-manager.sh
// Sample backup script: make executable and allow sudo without password for www-data
//------------------------------------------------------------------------------
#!/usr/bin/env bash
set -euo pipefail

# Ruta al config de FusionPBX
CONFIG_FILE=/etc/fusionpbx/config.conf

# Función helper para leer clave=valor y recortar espacios
_read_conf() {
  local key="$1"
  grep -E "^${key}" "$CONFIG_FILE" \
    | head -n1 \
    | cut -d'=' -f2- \
    | tr -d '[:space:]'
}

# Extraer variables de conexión
DB_HOST=$(_read_conf 'database\.0\.host')
DB_PORT=$(_read_conf 'database\.0\.port')
DB_NAME=$(_read_conf 'database\.0\.name')
DB_USER=$(_read_conf 'database\.0\.username')
DB_PASS=$(_read_conf 'database\.0\.password')

# Exportar contraseña para pg_dump/psql
export PGPASSWORD="$DB_PASS"
# Dump de la base de datos
pg_dump \
  -h "$DB_HOST" \
  -p "$DB_PORT" \
  -U "$DB_USER" \
  -Fc "$DB_NAME" \
  -f "$BASEDIR/postgresql/fusionpbx_${now}.sql"

# Empaquetar archivos
tar -zvcf "$BASEDIR/backup_${now}.tgz" \
  "$BASEDIR/postgresql/fusionpbx_${now}.sql" \
  /etc/fusionpbx \
  /var/www/fusionpbx \
  /usr/share/freeswitch/scripts \
  /usr/share/freeswitch/sounds/music \
  /etc/freeswitch \
  /var/lib/freeswitch/recordings \
  /var/lib/freeswitch/storage \
  /etc/freeswitch \
  /etc/dehydrated

# Limpieza de backups antiguos (por ejemplo, más de 7 días)
find "$BASEDIR" -type f -mtime +7 -delete