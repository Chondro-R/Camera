FROM php:8.2-apache

# cURL এক্সটেনশন ইনস্টল করা
RUN docker-php-ext-install curl

# প্রজেক্টের সব ফাইল কপি করা
COPY . /var/www/html/

# পারমিশন ঠিক করা
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# রেন্ডারের পোর্টের জন্য অ্যাপাচি কনফিগারেশন পরিবর্তন (পোর্ট ১০০০০)
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 10000
