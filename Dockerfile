FROM php:8.3-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype6-dev libicu-dev libjpeg62-turbo-dev libpng-dev \
    libpq-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) exif gd intl opcache pgsql pdo_pgsql soap zip \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/www/moodledata \
    && chown www-data:www-data /var/www/moodledata \
    && a2enmod rewrite

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/moodle.ini /usr/local/etc/php/conf.d/moodle.ini

WORKDIR /var/www/moodle
