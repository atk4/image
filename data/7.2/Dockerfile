FROM php:7.2-alpine as base

# install basic PHP
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions gmp
RUN apk add $PHPIZE_DEPS icu-libs icu-dev libpng libpng-dev \
        tidyhtml-libs tidyhtml-dev libxslt libxslt-dev libzip libzip-dev \
        mysql-client postgresql-client postgresql-dev c-client imap-dev \
    && docker-php-ext-install bcmath intl exif gd sockets tidy xsl zip mysqli pdo_mysql pdo_pgsql pcntl imap opcache

# install basic PECL extensions
RUN install-php-extensions imagick
RUN install-php-extensions igbinary
RUN install-php-extensions redis

# install xdebug PHP extension
RUN install-php-extensions xdebug

# install Microsoft ODBC drivers & pdo_sqlsrv PHP extension
RUN install-php-extensions pdo_sqlsrv

# install Oracle Instant client & pdo_oci PHP extension
RUN install-php-extensions pdo_oci


# remove build deps
RUN apk del --purge $PHPIZE_DEPS icu-dev libpng-dev \
        tidyhtml-dev libxslt-dev libzip-dev postgresql-dev imap-dev


# install Composer & other tools
RUN install-php-extensions @composer
RUN apk add bash git npm


# run basic tests
COPY test.php ./
RUN php test.php && rm test.php
RUN composer diagnose

FROM base as selenium

# install Selenium
RUN apk add openjdk8-jre-base xvfb ttf-freefont \
    && curl --fail --silent --show-error -L "https://selenium-release.storage.googleapis.com/3.141/selenium-server-standalone-3.141.59.jar" -o /opt/selenium-server-standalone.jar

# install Chrome
RUN apk add chromium chromium-chromedriver

# install Firefox
RUN apk add firefox \
    && curl --fail --silent --show-error -L "https://github.com/mozilla/geckodriver/releases/download/v0.28.0/geckodriver-v0.28.0-linux64.tar.gz" -o /tmp/geckodriver.tar.gz \
    && tar -C /opt -zxf /tmp/geckodriver.tar.gz && rm /tmp/geckodriver.tar.gz \
    && chmod 755 /opt/geckodriver && ln -s /opt/geckodriver /usr/bin/geckodriver
