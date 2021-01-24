<?php

$missingExts = array_diff([
    'bcmath',
    'curl',
    'exif',
    'gd',
    'gmp',
    'igbinary',
    'imagick',
    'imap',
    'intl',
    'mbstring',
    'mysqli',
    'mysqlnd',
    'openssl',
    'pdo_mysql',
    'PDO_OCI',
    'pdo_pgsql',
    'pdo_sqlite',
    'pdo_sqlsrv',
    'pcntl',
    'redis',
    'sockets',
    'sqlite3',
    'tidy',
    'xdebug',
    'xml',
    'xsl',
    'Zend OPcache',
    'zip',
], get_loaded_extensions());

// TODO remove once pdo_sqlsrv is avaiable for PHP 8.1
if (PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 1) {
    unset($missingExts[array_search('pdo_sqlsrv', $missingExts)]);
}

// TODO remove once redis is avaiable for PHP 8.1
if (PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 1) {
    unset($missingExts[array_search('redis', $missingExts)]);
}

if (count($missingExts) > 0) {
    echo 'ERROR - missing php extensions: ' . implode(', ', $missingExts) . "\n";
    exit(1);
}

$xdebugConfPath = '/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini';
if (!file_exists($xdebugConfPath)) {
    echo 'ERROR - missing xdebug configuration: ' . $xdebugConfPath . "\n";
    exit(1);
}
