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
    'mysqlnd',
    'opcache',
    'openssl',
    'pdo_mysql',
    'PDO_OCI',
    'pdo_pgsql',
    'pdo_sqlite',
    'redis',
    'sockets',
    'sqlite3',
    'tidy',
    'xdebug',
    'xml',
    'xsl',
    'zip',
], get_loaded_extensions());

// TODO remove once pdo_sqlsrv is avaiable for PHP 8.0
if (PHP_MAJOR_VERSION === 8) {
    unset($missingExts[array_search('pdo_sqlsrv', $missingExts)]);
}

if (count($missingExts) > 0) {
    echo 'ERROR - missing php extensions: ' . implode(', ', $missingExts) . "\n";
    exit(1);
}
