FROM php:8.0-rc-alpine


# install basic PHP
RUN apk add bash git jq $PHPIZE_DEPS \
        gmp gmp-dev icu-libs icu-dev libpng libpng-dev imagemagick imagemagick-dev \
        tidyhtml-libs tidyhtml-dev libxslt libxslt-dev libzip libzip-dev \
        mysql-client postgresql-client postgresql-dev c-client imap-dev \
        npm && \
    docker-php-ext-install bcmath gmp intl exif gd sockets tidy xsl zip pdo_mysql pdo_pgsql pcntl imap opcache && \
    wget -q https://getcomposer.org/installer -O - | php -- --install-dir=/usr/local/bin --filename=composer && \
    wget -q https://github.com/FriendsOfPHP/pickle/releases/latest/download/pickle.phar -O /usr/local/bin/pickle && chmod +x /usr/local/bin/pickle

# install basic PECL extensions
# TODO - install using pickle once imagick 3.4.5 is released, see https://github.com/Imagick/imagick/releases
RUN git clone https://github.com/Imagick/imagick && cd imagick \
    && phpize && ./configure && make all && make install \
    && echo "extension=imagick.so" >> /usr/local/etc/php/conf.d/docker-php-ext-imagick.ini
RUN pickle install igbinary && docker-php-ext-enable igbinary
RUN pickle install redis --no-interaction && docker-php-ext-enable redis

# install xdebug PHP extension
# TODO - install using pickle once xdebug 3.0 stable is released, see https://github.com/xdebug/xdebug/releases
RUN git clone https://github.com/xdebug/xdebug && cd xdebug \
    && phpize && ./configure --enable-xdebug-dev && make all && make install \
    && echo "zend_extension=xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini


# install Microsoft ODBC drivers
# based on https://github.com/microsoft/msphpsql/issues/300#issuecomment-673143369
RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.6.1.1-1_amd64.apk -sS && \
    curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.6.1.1-1_amd64.apk -sS && \
    printf '\n' | apk add --allow-untrusted msodbcsql17_17.6.1.1-1_amd64.apk && \
    printf '\n' | apk add --allow-untrusted mssql-tools_17.6.1.1-1_amd64.apk && \
    ln -sfnv /opt/mssql-tools/bin/* /usr/bin

# install pdo_sqlsrv PHP extension
# TODO install pdo_sqlsrv once available for PHP8, see https://github.com/atk4/image/issues/8
#RUN apk add libstdc++ unixodbc unixodbc-dev && \
#    pickle install pdo_sqlsrv && docker-php-ext-enable pdo_sqlsrv


# install Oracle Instant client
RUN git clone https://github.com/adrianharabula/php7-with-oci8.git && (cd php7-with-oci8 && git reset --hard d57bf99af398c0cd0e1c2a4c9f8006590600c951) && \
    mv php7-with-oci8/instantclient/12.2.0.1.0/* /tmp && rm -rf php7-with-oci8
RUN unzip /tmp/instantclient-basiclite-linux.x64-12.2.0.1.0.zip -d /usr/local && \
    unzip /tmp/instantclient-sdk-linux.x64-12.2.0.1.0.zip -d /usr/local && \
    unzip /tmp/instantclient-sqlplus-linux.x64-12.2.0.1.0.zip -d /usr/local && \
    ln -s /usr/local/instantclient_12_2 /usr/local/instantclient && \
    ln -s /usr/local/instantclient/libclntsh.so.12.1 /usr/local/instantclient/libclntsh.so && \
    ln -s /usr/local/instantclient/sqlplus /usr/bin/sqlplus
ENV LD_LIBRARY_PATH /usr/local/instantclient:$LD_LIBRARY_PATH

# install pdo_oci PHP extension
RUN apk add libaio libnsl libc6-compat && \
    docker-php-ext-configure pdo_oci --with-pdo-oci=instantclient,/usr/local/instantclient && \
    ln -s /usr/lib/libnsl.so.2 /usr/lib/libnsl.so.1 && \
    ln -s /lib/libc.so.6 /usr/lib/libresolv.so.2 && \
    ln -s /lib64/ld-linux-x86-64.so.2 /usr/lib/ld-linux-x86-64.so.2 && \
    docker-php-ext-install pdo_oci


# remove build deps
RUN apk del --purge $PHPIZE_DEPS gmp-dev icu-dev libpng-dev imagemagick-dev \
        tidyhtml-dev libxslt-dev libzip-dev postgresql-dev imap-dev unixodbc-dev


# other
RUN npm install -g less clean-css uglify-js


# run basic tests
COPY test.php ./
RUN php test.php && rm test.php
RUN composer diagnose
