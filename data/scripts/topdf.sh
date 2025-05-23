#!/bin/bash

#libreoffice adn inotifywait is used to convert into pdf,
#Install them before usage

WATCH_DIR="/var/www/html/data/diktator4ik1/files/topdf"
LOG_FILE="/tmp/topdf.log"
LIBRE_CMD="libreoffice --headless --convert-to pdf"

# Launch  inotifywait so it can monitor directory
inotifywait -m -e close_write,moved_to,create "$WATCH_DIR" --format '%w%f' | while read FILE
do
    EXT="${FILE##*.}"
    FILENAME="$(basename "$FILE")"
    BASENAME="${FILENAME%.*}"

    echo "$(date): Detected file: $FILE with extension $EXT" >> "$LOG_FILE"

    # Skip temp files
    if [[ "$FILENAME" == .* ]] || [[ "$EXT" =~ ^(part|tmp|lock|pdf#)$ ]] || [[ "$FILENAME" == *~ ]]; then
        echo "$(date): Skipping temporary file: $FILENAME" >> "$LOG_FILE"
        continue
    fi

    # Check if file is PDF, if not then convert
    if [[ "$EXT" != "pdf" ]]; then
        PDF_PATH="$WATCH_DIR/$BASENAME.pdf"
        if [[ ! -f "$PDF_PATH" ]]; then
            # libreoffice HOME
            HOME=/tmp $LIBRE_CMD "$FILE" --outdir "$WATCH_DIR" >> "$LOG_FILE" 2>&1
            if [ $? -eq 0 ]; then
                echo "$(date): Successfully converted $FILE to $PDF_PATH" >> "$LOG_FILE"
            else
                echo "$(date):  Conversion failed for $FILE" >> "$LOG_FILE"
            fi
        else
            echo "$(date): PDF $PDF_PATH already exists, skipping conversion" >> "$LOG_FILE"
        fi
    else
        echo "$(date): File is already PDF, skipping" >> "$LOG_FILE"
    fi
done


