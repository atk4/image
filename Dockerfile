FROM php:7.3-alpine

RUN apk add icu-dev mysql-client postgresql-client postgresql-dev bash npm git && \
    docker-php-ext-install intl pdo_mysql pdo_pgsql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN apk add $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    apk del --purge $PHPIZE_DEPS postgresql-dev
RUN npm install -g less clean-css uglify-js

