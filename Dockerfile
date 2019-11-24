FROM php:7.3-alpine

RUN apk add icu-dev bash
RUN docker-php-ext-install intl > /dev/null
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

