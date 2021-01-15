<?php

$missingExts = array_diff([
    'bcmath',
    'curl',
    'exif',
    'gd',
    'gmp',
#    'igbinary',
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

// TODO remove once pdo_sqlsrv is avaiable for PHP 8.0
if (PHP_MAJOR_VERSION === 8) {
    unset($missingExts[array_search('pdo_sqlsrv', $missingExts)]);
}

if (count($missingExts) > 0) {
    echo 'ERROR - missing php extensions: ' . implode(', ', $missingExts) . "\n";
    exit(1);
}
