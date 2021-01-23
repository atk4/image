FROM php:7.3-alpine as base

# install common PHP extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath
RUN install-php-extensions gmp
RUN install-php-extensions intl
RUN install-php-extensions exif
RUN install-php-extensions gd
RUN install-php-extensions imagick
RUN install-php-extensions sockets
RUN install-php-extensions tidy
RUN install-php-extensions xsl
RUN install-php-extensions zip
RUN install-php-extensions mysqli
RUN install-php-extensions pdo_mysql
RUN install-php-extensions pdo_pgsql
RUN install-php-extensions pdo_sqlsrv
RUN install-php-extensions pdo_oci
RUN install-php-extensions redis
RUN install-php-extensions igbinary
RUN install-php-extensions pcntl
RUN install-php-extensions imap
RUN install-php-extensions opcache
RUN install-php-extensions xdebug

# install Composer
RUN install-php-extensions @composer

# install  other tools
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
