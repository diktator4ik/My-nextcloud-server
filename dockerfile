FROM nextcloud:latest

# Встановлюємо LibreOffice
RUN apt update && apt install -y \
    libreoffice \
    inotify-tools \
    bash \
&& apt clean && rm -rf /var/lib/apt/lists/*
# Копіюємо скрипт у контейнер
#RUN chmod +x /var/www/html/data/scripts/topdf.sh
#RUN chmod +x /var/www/html/data/scripts/watchtopdf.sh

#ENTRYPOINT ["./entrypoint.sh"]

