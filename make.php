<?php

$imageNames = [];
foreach ([''/*, '-browser'*/] as $imageType) {
    foreach (['7.2', '7.3', '7.4', '8.0'] as $phpVersion) {
        $dockerFile = 'FROM php:' . $phpVersion . '-alpine

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
' . (
$phpVersion < 8
? 'RUN pickle install imagick && docker-php-ext-enable imagick'
// @TODO install using pickle once imagick 3.4.5 is released, see https://github.com/Imagick/imagick/releases
: 'RUN git clone https://github.com/Imagick/imagick && cd imagick \
    && phpize && ./configure && make all && make install \
    && echo "extension=imagick.so" >> /usr/local/etc/php/conf.d/docker-php-ext-imagick.ini'
) . '
RUN pickle install igbinary && docker-php-ext-enable igbinary
RUN pickle install redis --no-interaction && docker-php-ext-enable redis

# install xdebug PHP extension
' . (
$phpVersion < 8
// @TODO install using pickle once https://bugs.xdebug.org/view.php?id=1886 is resolved
? 'RUN pecl install xdebug && docker-php-ext-enable xdebug'
// @TODO install using pickle once xdebug 3.0 stable is released, see https://github.com/xdebug/xdebug/releases
: 'RUN git clone https://github.com/xdebug/xdebug && cd xdebug \
    && phpize && ./configure --enable-xdebug-dev && make all && make install \
    && echo "zend_extension=xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini'
) . '


# install Microsoft ODBC drivers
# based on https://github.com/microsoft/msphpsql/issues/300#issuecomment-673143369
RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.6.1.1-1_amd64.apk -sS && \
    curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.6.1.1-1_amd64.apk -sS && \
    printf \'\n\' | apk add --allow-untrusted msodbcsql17_17.6.1.1-1_amd64.apk && \
    printf \'\n\' | apk add --allow-untrusted mssql-tools_17.6.1.1-1_amd64.apk && \
    ln -sfnv /opt/mssql-tools/bin/* /usr/bin

# install pdo_sqlsrv PHP extension
' . (
$phpVersion < 8
// @TODO install using pickle once https://github.com/microsoft/msphpsql/issues/1210 is resolved
? 'RUN apk add libstdc++ unixodbc unixodbc-dev && \
    pecl install pdo_sqlsrv && docker-php-ext-enable pdo_sqlsrv'
// @TODO install pdo_sqlsrv once available for PHP8, see https://github.com/atk4/image/issues/8
: '# n/a'
) . '


# install Oracle Instant client & pdo_oci PHP extension
RUN install-php-extensions pdo_oci


# remove build deps
RUN apk del --purge $PHPIZE_DEPS gmp-dev icu-dev libpng-dev imagemagick-dev \
        tidyhtml-dev libxslt-dev libzip-dev postgresql-dev imap-dev' . (
$phpVersion < 8
? ' unixodbc-dev'
: ''
) . '


# other
RUN npm install -g less clean-css uglify-js


# run basic tests
COPY test.php ./
RUN php test.php && rm test.php
RUN composer diagnose
';

        $dataDir = __DIR__ . '/data';
        $imageName = $phpVersion . $imageType;
        $imageNames[] = $imageName;

        if (!is_dir($dataDir . '/' . $imageName)) {
            mkdir($dataDir . '/' . $imageName);
        }
        file_put_contents($dataDir . '/' . $imageName . '/Dockerfile', $dockerFile);
    }
}

$codefreshFile = 'version: "1.0"
stages:
  - prepare
  - build
  - test
  - push
steps:
  main_clone:
    stage: prepare
    type: git-clone
    repo: atk4/image
    revision: "${{CF_BRANCH}}"

  build:
    type: parallel
    stage: build
    steps:
' . implode("\n", array_map(function ($imageName) {
    return '      b' . $imageName . ':
        type: build
        image_name: atk4/image
        tag: "${{CF_BUILD_ID}}-' . $imageName . '"
        registry: atk4
        dockerfile: ' . $imageName . '/Dockerfile';
}, $imageNames)) . '

  test:
    type: parallel
    stage: test
    steps:
' . implode("\n", array_map(function ($imageName) {
    return '      t' . $imageName . ':
        image: "atk4/image:${{CF_BUILD_ID}}-' . $imageName . '"
        registry: atk4
        commands:
          - php test.php';
}, $imageNames)) . '

  push:
    type: parallel
    stage: push
    when:
      branch:
        only:
          - master
    steps:
' . implode("\n", array_map(function ($imageName) {
    $res = [];
    $res[] = '      p' . $imageName . ':
        candidate: "${{b' . $imageName . '}}"
        type: push
        registry: atk4
        tag: "' . $imageName . '"';
    $imageNameLatest = preg_replace('~7.4~', 'latest', $imageName);
    if ($imageNameLatest !== $imageName) {
    $res[] = '      p' . $imageNameLatest . ':
        candidate: "${{b' . $imageName . '}}"
        type: push
        registry: atk4
        tag: "' . $imageNameLatest . '"';
    }

    return implode("\n", $res);
}, $imageNames)) . '
';
file_put_contents(__DIR__ . '/.codefresh/deploy-build-image.yaml', $codefreshFile);
