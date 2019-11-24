FROM php:7.3-alpine

RUN apk add icu-dev
RUN docker-php-ext-install intl > /dev/null



