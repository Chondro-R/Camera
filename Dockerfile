FROM php:8.2-apache

# প্রয়োজনীয় সিস্টেম প্যাকেজ, পাইথন এবং পিএইচপি এক্সটেনশন ইনস্টল করা
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    libcurl4-openssl-dev \
    pkg-config \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

# পাইথনের প্যাকেজগুলো (python-telegram-bot এবং flask) ইনস্টল করা
RUN pip3 install --no-cache-dir python-telegram-bot flask --break-system-packages

# অ্যাপ্লিকেশনের সব ফাইল রেন্ডারের ওয়েব রুটে কপি করা
COPY . /var/www/html/

# পারমিশন ঠিক করা
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# রেন্ডারের জন্য পোর্ট এক্সপোজ করা (Render ডিফল্ট পোর্ট ১০০০০ ব্যবহার করে)
EXPOSE 10000

# Apache এবং Python Bot একসাথে ব্যাকগ্রাউন্ডে রান করার জন্য স্টার্ট স্ক্রিপ্ট
RUN echo '#!/bin/bash' > /start.sh \
    && echo 'python3 /var/www/html/bot.py & ' >> /start.sh \
    && echo 'apache2-foreground' >> /start.sh \
    && chmod +x /start.sh

CMD ["/start.sh"]
