FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev pkg-config libssl-dev \
    python3 python3-pip \
    && docker-php-ext-install curl

RUN pip3 install --no-cache-dir python-telegram-bot --break-system-packages

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

# start.sh ছাড়া একসাথে পাইথন এবং অ্যাপাচি রান করার কমান্ড
CMD ["sh", "-c", "python3 /var/www/html/bot.py & apache2-foreground"]
