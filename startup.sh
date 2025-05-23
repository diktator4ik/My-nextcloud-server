#!/bin/bash
sleep 5;
SUPERVISORD_PATH=$(which supervisord)
if [ -z "$SUPERVISORD_PATH" ]; then
  echo "supervisord not found!"
  exit 1
fi

exec "$SUPERVISORD_PATH" -c /data/scripts/supervisord.conf


