FROM php:7.4-alpine

# install basic PHP
RUN apk add icu-dev mysql-client postgresql-client postgresql-dev bash npm git jq neovim && \
    docker-php-ext-install intl pdo_mysql pdo_pgsql

# install Microsoft ODBC drivers
# based on https://github.com/microsoft/msphpsql/issues/300#issuecomment-673143369
RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.6.1.1-1_amd64.apk && \
    curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.6.1.1-1_amd64.apk && \
    printf '\n' | apk add --allow-untrusted msodbcsql17_17.6.1.1-1_amd64.apk && \
    printf '\n' | apk add --allow-untrusted mssql-tools_17.6.1.1-1_amd64.apk && \
    ln -sfnv /opt/mssql-tools/bin/* /usr/bin

# install xdebug, pdo_sqlsrv PHP extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin
RUN install-php-extensions xdebug pdo_sqlsrv

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN npm install -g less clean-css uglify-js
