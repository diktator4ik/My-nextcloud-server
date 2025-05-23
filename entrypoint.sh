#!/bin/bash
# Стартуємо скрипти, можна через & щоб у фоні
/var/www/html/data/scripts/topdf.sh &
/var/www/html/data/scripts/watchtopdf.sh

# Чекаємо поки watchtopdf.sh не завершиться
wait
