# Set master image
FROM php:8.4-fpm-alpine

RUN docker-php-ext-install mysqli && \
    docker-php-ext-install pdo && \
    docker-php-ext-install pdo_mysql

RUN apk add --no-cache $PHPIZE_DEPS php-pear linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN curl https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer
RUN composer self-update

RUN apk add --no-cache python3 py3-pip ffmpeg yt-dlp

WORKDIR /code
