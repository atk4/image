FROM php:7.2-alpine as base

# install system tools
RUN apk add bash git jq mysql-client postgresql-client npm

# install PHP extensions and Composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    gmp \
    igbinary \
    imagick \
    imap \
    intl \
    mysqli \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_oci \
    pdo_pgsql \
    pdo_sqlsrv \
    redis \
    sockets \
    tidy \
    xdebug \
    xsl \
    zip \
    @composer

# Install other tools
RUN npm install -g less clean-css uglify-js

# run basic tests
COPY test.php ./
RUN php test.php && rm test.php
RUN composer diagnose

FROM base as selenium

# install Selenium
RUN apk add openjdk8-jre-base xvfb ttf-freefont && \
    curl --fail --silent --show-error -L "https://selenium-release.storage.googleapis.com/3.141/selenium-server-standalone-3.141.59.jar" -o /opt/selenium-server-standalone.jar

# install Chrome
RUN apk add chromium chromium-chromedriver

# install Firefox
RUN apk add firefox && \
    curl --fail --silent --show-error -L "https://github.com/mozilla/geckodriver/releases/download/v0.28.0/geckodriver-v0.28.0-linux64.tar.gz" -o /tmp/geckodriver.tar.gz && \
    tar -C /opt -zxf /tmp/geckodriver.tar.gz && rm /tmp/geckodriver.tar.gz && \
    chmod 755 /opt/geckodriver && ln -s /opt/geckodriver /usr/bin/geckodriver
