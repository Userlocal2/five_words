FROM php:8.1-fpm

RUN apt-get update && apt-get install -y \
      wget \
      libicu-dev \
      libpq-dev \
      libmcrypt-dev \
      mariadb-client \
      git \
      zip \
      libzip-dev \
      unzip \
      zlib1g-dev\
      libonig-dev \
    && rm -r /var/lib/apt/lists/* \
    && pecl install xdebug \
    && pecl install pkcs11 \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-install \
      intl \
      mbstring \
      pcntl \
      pdo_mysql \
      pdo_pgsql \
      zip \
      bcmath \
      opcache

ADD ./php.ini /usr/local/etc/php/php.ini

RUN wget https://getcomposer.org/installer -O - -q \
    | php -- --install-dir=/bin --filename=composer --quiet

WORKDIR /var/app
#
## install all PHP dependencies
#RUN ls -la
#
## install all PHP dependencies
#RUN composer install --no-interaction
#
##change ownership of our applications
#RUN chown -R www-data:www-data .


