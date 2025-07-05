#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# fusionpbx-backup-manager.sh
# Create a FusionPBX backup. Intended to be run via sudo by the web interface.
# -----------------------------------------------------------------------------
set -euo pipefail

BASEDIR="/var/backups/fusionpbx"
mkdir -p "$BASEDIR/postgresql"
now=$(date +"%Y%m%d_%H%M%S")

CONFIG_FILE=/etc/fusionpbx/config.conf

_read_conf() {
  local key="$1"
  grep -E "^${key}" "$CONFIG_FILE" | head -n1 | cut -d'=' -f2- | tr -d '[:space:]'
}

DB_HOST=$(_read_conf 'database\.0\.host')
DB_PORT=$(_read_conf 'database\.0\.port')
DB_NAME=$(_read_conf 'database\.0\.name')
DB_USER=$(_read_conf 'database\.0\.username')
DB_PASS=$(_read_conf 'database\.0\.password')

export PGPASSWORD="$DB_PASS"
pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -Fc "$DB_NAME" \
  -f "$BASEDIR/postgresql/fusionpbx_${now}.sql"

tar -zcf "$BASEDIR/backup_${now}.tgz" \
  "$BASEDIR/postgresql/fusionpbx_${now}.sql" \
  /etc/fusionpbx \
  /var/www/fusionpbx \
  /usr/share/freeswitch/scripts \
  /usr/share/freeswitch/sounds/music \
  /etc/freeswitch \
  /var/lib/freeswitch/recordings \
  /var/lib/freeswitch/storage \
  /etc/dehydrated

find "$BASEDIR" -type f -mtime +7 -delete

