# This is a dockerfile to be used as a base for ATK demo, homepage etc
# Comes with a pre-installed PHP but no ATK dependencies

FROM php:8.0.0beta3-alpine
RUN apk add bash git jq unzip npm neovim && \
    docker-php-ext-install intl pdo pdo_mysql && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
