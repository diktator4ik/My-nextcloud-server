#!/bin/bash

#This script will help you to make sure that any file you add manually to user's folder will be visible in web interface of your nextcloud web site.
#If you are using for example my script for converting files to pdf, this script is mandatory for you.

WATCH_DIR="/var/www/nextcloud/data/your_user/files/topdf"
NC_USER="your_user"

inotifywait -m -e close_write,create,delete,move "$WATCH_DIR" --format '%w%f' | while read FILE
do
    echo "$(date): changes in folder with $FILE, updated..." >> /tmp/watchtopdf.log
    
    # Updating permissions
    chown -R www-data:www-data "$WATCH_DIR"
    
    # This command scans for changes in user's folder and notifies nextcloud
    sudo -u www-data php /var/www/nextcloud/occ files:scan --path="$NC_USER/files/topdf" >> /tmp/watchtopdf.log 2>&1

    echo "$(date): Scan complete" >> /tmp/watchtopdf.log
done

