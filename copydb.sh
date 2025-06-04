#!/bin/bash

# Look for apropriate container
DB_CONTAINER=$(docker ps --format '{{.Names}}' | grep -E 'db|mariadb|mysql' | head -n 1)

# creating folder
BACKUP_DIR="./backupdb"
DATE_TAG=$(date +"%Y-%m-%d_%H-%M")
ARCHIVE_NAME="db$DATE_TAG.tar.gz"

# === check ===
if [ -z "$DB_CONTAINER" ]; then
  echo "Don't see container"
  exit 1
fi

echo "found container: $DB_CONTAINER"
mkdir -p "$BACKUP_DIR"

# === packing tar.gz ===
docker exec "$DB_CONTAINER" bash -c "cd /var/lib/mysql && tar czf /tmp/db_backup.tar.gz ."

# === copy from docker to folder ===
docker cp "$DB_CONTAINER":/tmp/db_backup.tar.gz "$BACKUP_DIR/$ARCHIVE_NAME"

# === clean tmp ===
docker exec "$DB_CONTAINER" rm /tmp/db_backup.tar.gz

# === success ===
echo "saved $BACKUP_DIR/$ARCHIVE_NAME"

