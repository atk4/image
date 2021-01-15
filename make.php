<?php

$cfLabelFromName = function(string $prefix, string $n): string {
    return $prefix . preg_replace_callback('~\W~', function ($matches) {
        if ($matches[0] === '.') {
            return '_dot_';
        } elseif ($matches[0] === '-') {
            return '_dash_';
        }

        return '_x' . bin2hex($matches[0]) . '_';
    }, $n);
};

$imageNames = [];
foreach ([''] as $imageType) {
    foreach (['7.2', '7.3', '7.4', '8.0'] as $phpVersion) {
        $dockerFile = 'FROM php:' . $phpVersion . '-alpine as base

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
#RUN pickle install igbinary && docker-php-ext-enable igbinary
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
';

        $dataDir = __DIR__ . '/data';
        $imageName = $phpVersion . ($imageType !== '' ? '-' : '') . $imageType;
        $imageNames[] = $imageName;

        if (!is_dir($dataDir)) {
            mkdir($dataDir);
        }
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
  - build-selenium
  - test
  - test-selenium
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
' . implode("\n", array_map(function ($imageName) use ($cfLabelFromName) {
    return '      ' . $cfLabelFromName('b', $imageName) . ':
        type: build
        image_name: atk4/image
        target: base
        tag: "${{CF_BUILD_ID}}-' . $imageName . '"
        registry: atk4
        dockerfile: data/' . $imageName . '/Dockerfile';
}, $imageNames)) . '

  build_selenium:
    type: parallel
    stage: build-selenium
    steps:
' . implode("\n", array_map(function ($imageName) use ($cfLabelFromName) {
        return '      ' . $cfLabelFromName('b', $imageName.'-selenium') . ':
        type: build
        image_name: atk4/image
        target: selenium
        tag: "${{CF_BUILD_ID}}-' . $imageName . '-selenium"
        registry: atk4
        dockerfile: data/' . $imageName . '/Dockerfile';
    }, $imageNames)) . '

  test:
    type: parallel
    stage: test
    steps:
' . implode("\n", array_map(function ($imageName) use ($cfLabelFromName) {
    return '      ' . $cfLabelFromName('t', $imageName) . ':
        image: "atk4/image:${{CF_BUILD_ID}}-' . $imageName . '"
        registry: atk4
        commands:
          - php test.php';
}, $imageNames)) . '

  test_selenium:
    type: parallel
    stage: test-selenium
    steps:
' . implode("\n", array_map(function ($imageName) use ($cfLabelFromName) {
        return '      ' . $cfLabelFromName('t', $imageName.'-selenium') . ':
        image: "atk4/image:${{CF_BUILD_ID}}-' . $imageName . '-selenium"
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
' . implode("\n", array_map(function ($imageName) use ($cfLabelFromName) {
    $res = [];
    $res[] = '      ' . $cfLabelFromName('p', $imageName) . ':
        candidate: "${{' . $cfLabelFromName('b', $imageName) . '}}"
        type: push
        registry: atk4
        tag: "' . $imageName . '"';
    $imageNameLatest = preg_replace('~7\.4~', 'latest', $imageName);
    if ($imageNameLatest !== $imageName) {
    $res[] = '      ' . $cfLabelFromName('p', $imageNameLatest) . ':
        candidate: "${{' . $cfLabelFromName('b', $imageName) . '}}"
        type: push
        registry: atk4
        tag: "' . $imageNameLatest . '"';
    }

    return implode("\n", $res);
}, array_merge(
    $imageNames,
    array_map(function($image_name) { return $image_name.'-selenium'; }, $imageNames)
))).'
';
file_put_contents(__DIR__ . '/.codefresh/deploy-build-image.yaml', $codefreshFile);


$codefreshFile = 'name: Build

on:
  pull_request:
  push:
  schedule:
    - cron: \'0 0 * * *\'

jobs:
  unit:
    name: Build
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        imageName:
'. implode("\n", array_map(function ($imageName) {
    return '          - "' . $imageName . '"';
}, $imageNames)) . '
    steps:
      - name: Checkout
        uses: actions/checkout@v2

      - name: Build Dockerfile
        run: cd data/${{ matrix.imageName }} && docker build ./
';
file_put_contents(__DIR__ . '/.github/workflows/test-build.yml', $codefreshFile);
