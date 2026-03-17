#!/bin/bash
# BuildSmart Uploaded Files Backup Script

SOURCE_DIR="/var/www/buildsmart/uploads"
BACKUP_DIR="/var/backups/buildsmart/files"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
FILENAME="$BACKUP_DIR/files_backup_$DATE.tar.gz"

mkdir -p $BACKUP_DIR

tar -czf $FILENAME $SOURCE_DIR

if [ $? -eq 0 ]; then
    echo "Files backup successful: $FILENAME"
else
    echo "Files backup failed!" | mail -s "BuildSmart Files Backup Failed" admin@buildsmart.com
fi

# Keep only last 7 backups
ls -1tr $BACKUP_DIR/files_backup_*.tar.gz | head -n -7 | xargs -d '\n' rm -f