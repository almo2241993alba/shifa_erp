FROM php:8.2-apache

# تثبيت المكتبات الأساسية وإضافات PHP المطلوبة لـ Laravel وقواعد البيانات
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# تفعيل mod_rewrite في Apache لعمل روابط Laravel بسلاسة
RUN a2enmod rewrite

# توجيه مجلد السيرفر العام إلى مجلد public الخاص بـ Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# تثبيت حزم PHP وحزم الواجهات وبناء ملفات CSS/JS
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm install && npm run build

# ضبط صلاحيات مجلدات التخزين والكاش
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# نسخ وتفعيل سكربت التشغيل
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
