#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# fusionpbx-pre-restore.sh
# Prepare the system before running a FusionPBX restore.
# This script creates a safety backup and stops services to avoid conflicts.
# -----------------------------------------------------------------------------
set -euo pipefail

BACKUP_SCRIPT="/var/www/fusionpbx/app/backup_manager/scripts/fusionpbx-backup-manager.sh"

if [[ -x "$BACKUP_SCRIPT" ]]; then
  echo "Creating pre-restore backup..."
  "$BACKUP_SCRIPT"
else
  echo "Backup script not found: $BACKUP_SCRIPT" >&2
  exit 1
fi

# Stop services to ensure files are consistent during restore
service freeswitch stop
service nginx stop

echo "Pre-restore tasks completed."
