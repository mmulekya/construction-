#!/bin/bash
# Backup BuildSmart MySQL database

# Configuration
DB_USER="ifo_41680562_work2026"
DB_PASS="YourStrongPassword"
DB_NAME="buildsmart"
BACKUP_DIR="/var/backups/buildsmart"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
FILENAME="$BACKUP_DIR/db_backup_$DATE.sql.gz"

# Create backup directory if not exists
mkdir -p $BACKUP_DIR

# Dump and compress database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $FILENAME

# Check if backup was successful
if [ $? -eq 0 ]; then
  echo "Database backup successful: $FILENAME"
else
  echo "Database backup failed!" | mail -s "BuildSmart Backup Failed" admin@buildsmart.com
fi

# Keep last 7 backups only
ls -1tr $BACKUP_DIR/db_backup_*.sql.gz | head -n -7 | xargs -d '\n' rm -f