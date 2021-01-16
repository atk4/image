FROM php:7.3-alpine as base

# install basic PHP
RUN apk add bash git jq $PHPIZE_DEPS \
        gmp gmp-dev icu-libs icu-dev libpng libpng-dev imagemagick imagemagick-dev \
        tidyhtml-libs tidyhtml-dev libxslt libxslt-dev libzip libzip-dev \
        mysql-client postgresql-client postgresql-dev c-client imap-dev \
        npm && \
    docker-php-ext-install bcmath gmp intl exif gd sockets tidy xsl zip mysqli pdo_mysql pdo_pgsql pcntl imap opcache
RUN wget -q https://getcomposer.org/installer -O - | php -- --install-dir=/usr/local/bin --filename=composer
RUN wget -q https://github.com/FriendsOfPHP/pickle/releases/latest/download/pickle.phar -O /usr/local/bin/pickle && chmod +x /usr/local/bin/pickle
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# install basic PECL extensions
RUN pickle install imagick && docker-php-ext-enable imagick
RUN install-php-extensions igbinary-3.2.1
RUN pickle install redis --no-interaction && docker-php-ext-enable redis

# install xdebug PHP extension
RUN pecl install xdebug && docker-php-ext-enable xdebug


# install Microsoft ODBC drivers
# based on https://github.com/microsoft/msphpsql/issues/300#issuecomment-673143369
RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.6.1.1-1_amd64.apk -sS && \
    curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.6.1.1-1_amd64.apk -sS && \
    printf '\n' | apk add --allow-untrusted msodbcsql17_17.6.1.1-1_amd64.apk && \
    printf '\n' | apk add --allow-untrusted mssql-tools_17.6.1.1-1_amd64.apk && \
    ln -sfnv /opt/mssql-tools/bin/* /usr/bin

# install pdo_sqlsrv PHP extension
RUN apk add libstdc++ unixodbc unixodbc-dev && \
    pecl install pdo_sqlsrv && docker-php-ext-enable pdo_sqlsrv


# install Oracle Instant client & pdo_oci PHP extension
RUN install-php-extensions pdo_oci


# remove build deps
RUN apk del --purge $PHPIZE_DEPS gmp-dev icu-dev libpng-dev imagemagick-dev \
        tidyhtml-dev libxslt-dev libzip-dev postgresql-dev imap-dev unixodbc-dev


# other
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
