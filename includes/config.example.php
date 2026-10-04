<?php
// Copy to config.local.php. Never commit that local file.
return [
 'APP_ENV' => 'development', 'APP_TIMEZONE' => 'UTC',
 'DB_HOST' => '127.0.0.1', 'DB_PORT' => 3306, 'DB_NAME' => 'covidtmsdb',
 'DB_USER' => 'root', 'DB_PASSWORD' => '',
 'REPORT_STORAGE' => dirname(__DIR__) . '/storage',
 'ALLOW_LEGACY_MD5' => true,
];
