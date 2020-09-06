FROM php:8.0.0beta3-alpine

RUN apk add icu-dev mysql-client postgresql-client postgresql-dev bash npm git jq neovim && \
    docker-php-ext-install intl pdo_mysql pdo_pgsql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Temporary removed - pecl not working in 8.0
#RUN apk add $PHPIZE_DEPS && \
    #pecl install xdebug && \
    #docker-php-ext-enable xdebug && \
    #apk del --purge $PHPIZE_DEPS postgresql-dev
RUN touch /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN npm install -g less clean-css uglify-js

