#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# fusionpbx-restore-manager.sh
# Restore a FusionPBX backup. Usage: script <backup.tgz> <full|media>
# -----------------------------------------------------------------------------
set -euo pipefail

BACKUP_TGZ="$1"
MODE="${2:-full}"

TMPDIR=$(mktemp -d)
tar -xzf "$BACKUP_TGZ" -C "$TMPDIR"

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

restore_db() {
  # SQL dumps reside under var/backups when extracted
  SQL_FILE=$(find "$TMPDIR" -path '*/postgresql/fusionpbx_*.sql' -print -quit)
  if [[ -f "$SQL_FILE" ]]; then
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -c "DROP SCHEMA public CASCADE;"
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -c "CREATE SCHEMA public;"
    pg_restore -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -v -Fc --dbname="$DB_NAME" "$SQL_FILE"
  fi
}

restore_all_files() {
  rsync -a "$TMPDIR/etc/fusionpbx/" /etc/fusionpbx/
  rsync -a "$TMPDIR/var/www/fusionpbx/" /var/www/fusionpbx/
  rsync -a "$TMPDIR/usr/share/freeswitch/scripts/" /usr/share/freeswitch/scripts/
  rsync -a "$TMPDIR/etc/freeswitch/" /etc/freeswitch/
  restore_media_only
  rsync -a "$TMPDIR/etc/dehydrated/" /etc/dehydrated/
}

restore_media_only() {
  rsync -a "$TMPDIR/usr/share/freeswitch/sounds/music/" /usr/share/freeswitch/sounds/music/
  rsync -a "$TMPDIR/var/lib/freeswitch/recordings/" /var/lib/freeswitch/recordings/
  rsync -a "$TMPDIR/var/lib/freeswitch/storage/" /var/lib/freeswitch/storage/
}

case "$MODE" in
  full)
    restore_db
    restore_all_files
    ;;
  media)
    restore_media_only
    ;;
  *)
    IFS=',' read -ra opts <<< "$MODE"
    for opt in "${opts[@]}"; do
      case "$opt" in
        db) restore_db ;;
        media) restore_media_only ;;
        certs) rsync -a "$TMPDIR/etc/dehydrated/" /etc/dehydrated/ ;;
      esac
    done
    ;;
esac

service freeswitch restart
service nginx reload

echo "Restore complete."

