//------------------------------------------------------------------------------
// scripts/fusionpbx-restore-manager.sh
// Sample restore script: pass backup file and comma-separated options (db,media,certs)
//------------------------------------------------------------------------------
#!/bin/bash
BACKUP_TGZ=$1
OPTIONS=$2
# extract backup
TMPDIR=$(mktemp -d)
tar -xzvf "$BACKUP_TGZ" -C "$TMPDIR"
# restore DB
if [[ ",\$OPTIONS," == *",db,"* ]]; then
  echo "Restoring database..."
  psql -U fusionpbx -c "DROP SCHEMA public CASCADE;"
  psql -U fusionpbx -c "CREATE SCHEMA public;"
  pg_restore -v -Fc --dbname=fusionpbx "$TMPDIR/postgresql/fusionpbx_*.sql"
fi
# restore media
if [[ ",\$OPTIONS," == *",media,"* ]]; then
  echo "Restoring media files..."
  cp -r "$TMPDIR/var/lib/freeswitch/storage/"* /var/lib/freeswitch/storage/
  cp -r "$TMPDIR/var/lib/freeswitch/recordings/"* /var/lib/freeswitch/recordings/
fi
# restore certs
if [[ ",\$OPTIONS," == *",certs,"* ]]; then
  echo "Restoring certificates..."
  cp -r "$TMPDIR/etc/dehydrated" /etc/
fi
# reload services
service freeswitch restart
service nginx reload
echo "Restore complete."
