FROM php:8.2.28-apache

WORKDIR /var/www/html

# setup time zone
ENV TZ=Europe/London
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get --allow-releaseinfo-change update;

RUN apt-get install -y curl
RUN apt-get install -y git libzip-dev unzip libpq-dev apt-utils && docker-php-ext-install zip pdo pdo_mysql bcmath && a2enmod rewrite headers

# Install opcache
RUN docker-php-ext-install opcache

# Required for messenger worker
RUN docker-php-ext-install pcntl

RUN apt-get install -y libwebp-dev libjpeg62-turbo-dev libpng-dev libxpm-dev \
    libfreetype6-dev zlib1g-dev

RUN set -eux; \
        docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp; \
        docker-php-ext-install -j "$(nproc)" \
        		gd

# Install APCu for caching
RUN pecl install apcu && pecl clear-cache
RUN docker-php-ext-enable apcu

# Install phpredis extension for Redis transport
RUN pecl install redis && pecl clear-cache
RUN docker-php-ext-enable redis

COPY ./vhost.conf /etc/apache2/sites-available/000-default.conf
COPY ./php.ini /usr/local/etc/php/php.ini
COPY ./opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs

# # Ensure runtime folders exist and have correct permissions for the web user
# # Create directories if missing, set ownership to www-data and permissive group write for dev
# RUN mkdir -p var/cache var/log \
#     && chown -R www-data:www-data var/cache var/log \
#     && chmod -R 775 var/cache var/log
RUN npm install -g yarn

