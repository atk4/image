<?php

$cfLabelFromName = function(string $prefix, string $n): string {
    return $prefix . preg_replace_callback('~\W~', function ($matches) {
        return '_'
            . ([
                '.' => 'dot',
                '-' => 'dash',
            ][$matches[0]] ?? '0x' . bin2hex($matches[0]))
            . '_';
    }, $n);
};

$imageNames = [];
foreach ([''] as $imageType) {
    foreach (['7.2', '7.3', '7.4', '8.0'] as $phpVersion) {
        $dockerFile = 'FROM php:' . $phpVersion . '-alpine as base

# install common PHP extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath
RUN install-php-extensions exif
RUN install-php-extensions gd
RUN install-php-extensions gmp
RUN install-php-extensions igbinary
RUN install-php-extensions imagick
RUN install-php-extensions imap
RUN install-php-extensions intl
RUN install-php-extensions mysqli
RUN install-php-extensions opcache
RUN install-php-extensions pcntl
RUN install-php-extensions pdo_mysql
RUN install-php-extensions pdo_oci
RUN install-php-extensions pdo_pgsql
RUN install-php-extensions pdo_sqlsrv
RUN install-php-extensions redis
RUN install-php-extensions sockets
RUN install-php-extensions tidy
RUN install-php-extensions xdebug
RUN install-php-extensions xsl
RUN install-php-extensions zip

# install Composer
RUN install-php-extensions @composer

# install other tools
RUN apk add bash git

# run basic tests
COPY test.php ./
RUN php test.php && rm test.php
RUN composer diagnose


FROM base as npm

# install npm
RUN apk add npm


FROM npm as selenium

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

$targetNames = ['base', 'npm', 'selenium'];

$imageNamesExtended = array_merge(
    ...array_map(function ($targetName) use ($imageNames) {
        return array_map(function($imageName) use ($targetName) {
            return $imageName . ($targetName === 'base' ? '' : '-' . $targetName);
        }, $imageNames);
    }, $targetNames)
);

$codefreshFile = 'version: "1.0"
stages:
  - prepare
' . implode("\n", array_map(function ($targetName) use ($cfLabelFromName) {
    return '  - ' . $cfLabelFromName('build_', $targetName);
}, $targetNames)) . '
  - test
  - push
steps:
  main_clone:
    stage: prepare
    type: git-clone
    repo: atk4/image
    revision: "${{CF_BRANCH}}"

' . implode("\n\n", array_map(function ($targetName) use ($imageNames, $cfLabelFromName) {
    return '  ' . $cfLabelFromName('build_', $targetName) . ':
    type: parallel
    stage: ' . $cfLabelFromName('build_', $targetName) . '
    steps:
' . implode("\n", array_map(function ($imageName) use ($targetName, $cfLabelFromName) {
        return '      ' . $cfLabelFromName('b', $imageName . ($targetName === 'base' ? '' : '-' . $targetName)) . ':
        type: build
        image_name: atk4/image
        target: ' . $targetName . '
        tag: "${{CF_BUILD_ID}}-' . $imageName . ($targetName === 'base' ? '' : '-' . $targetName) . '"
        registry: atk4
        dockerfile: data/' . $imageName . '/Dockerfile';
    }, $imageNames));
}, $targetNames)) . '

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
}, $imageNamesExtended)) . '

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
    $imageNameLatest = preg_replace('~(?<!\d)' . preg_quote('8.0', '~') . '(?!\d)~', 'latest', $imageName);
    if ($imageNameLatest !== $imageName) {
    $res[] = '      ' . $cfLabelFromName('p', $imageNameLatest) . ':
        candidate: "${{' . $cfLabelFromName('b', $imageName) . '}}"
        type: push
        registry: atk4
        tag: "' . $imageNameLatest . '"';
    }

    return implode("\n", $res);
}, $imageNamesExtended)).'
';
file_put_contents(__DIR__ . '/.codefresh/deploy-build-image.yaml', $codefreshFile);


$ciFile = 'name: CI

on:
  pull_request:
  push:
  schedule:
    - cron: \'20 */2 * * *\'

jobs:
  unit:
    name: Templating
    runs-on: ubuntu-latest
    container:
      image: atk4/image
    steps:
      - name: Checkout
        uses: actions/checkout@v2

      - name: "Check if files are in-sync"
        run: |
          rm -rf data/
          php make.php
          git diff --exit-code

  build:
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
        # try to build twice to suppress random network issues with Github Actions
        run: >-
          docker build -f data/${{ matrix.imageName }}/Dockerfile ./
          || docker build -f data/${{ matrix.imageName }}/Dockerfile ./
';
file_put_contents(__DIR__ . '/.github/workflows/ci.yml', $ciFile);
