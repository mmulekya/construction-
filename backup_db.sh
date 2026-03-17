#!/bin/bash
# BuildSmart Database Backup Script

DB_USER="ifo_41680562_work2026"
DB_PASS="StrongPassword123"
DB_NAME="buildsmart"
BACKUP_DIR="/var/backups/buildsmart/db"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
FILENAME="$BACKUP_DIR/db_backup_$DATE.sql.gz"

mkdir -p $BACKUP_DIR

# Backup and compress
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $FILENAME

# Verify backup
if [ $? -eq 0 ]; then
    echo "Database backup successful: $FILENAME"
else
    echo "Database backup failed!" | mail -s "BuildSmart DB Backup Failed" admin@buildsmart.com
fi

# Keep only last 7 backups
ls -1tr $BACKUP_DIR/db_backup_*.sql.gz | head -n -7 | xargs -d '\n' rm -f