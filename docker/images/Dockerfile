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
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.output_dir=/var/app" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl install pkcs11 \
    && docker-php-ext-enable pkcs11 \
    && apt-get update && apt-get -y install libxml2-dev libgpgme11-dev \
    && pecl install gnupg \
    && docker-php-ext-enable gnupg \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd

RUN docker-php-ext-install \
          intl \
          mbstring \
          pcntl \
          pdo_mysql \
          pdo_pgsql \
          zip \
          bcmath \
          opcache \
          soap

RUN apt-get install -y libgmp-dev && docker-php-ext-install gmp

ADD ./php-cli/php.ini /usr/local/etc/php/php.ini

RUN wget https://getcomposer.org/installer -O - -q \
    | php -- --install-dir=/bin --filename=composer --quiet \
    apt-get update && apt-get install -y nano htop

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


