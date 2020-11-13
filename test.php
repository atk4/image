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
    'openssl',
    'pdo_mysql',
    'PDO_OCI',
    'pdo_pgsql',
    'pdo_sqlite',
    ...(PHP_MAJOR_VERSION === 8 ? [] : ['pdo_sqlsrv']), // not available for PHP 8.0 yet
    'redis',
    'sockets',
    'sqlite3',
    'tidy',
    'xdebug',
    'xml',
    'xsl',
    'zip',
], get_loaded_extensions());

if (count($missingExts) > 0) {
    echo 'ERROR - missing php extensions: ' . implode(', ', $missingExts) . "\n";
    exit(1);
}
