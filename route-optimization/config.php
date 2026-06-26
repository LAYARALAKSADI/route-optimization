<?php

if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!isset($_ENV[$key]) && !getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

define('SUNQUICK_LAT', getenv('SUNQUICK_LAT') ?: 6.9271);
define('SUNQUICK_LNG', getenv('SUNQUICK_LNG') ?: 79.8612);

define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');

define('API_URL', getenv('API_URL') ?: 'https://service-connect.free.beeceptor.com/tickets');

define('CACHE_DURATION', getenv('CACHE_DURATION') ?: 3600);

define('USE_OPENSTREETMAP', filter_var(getenv('USE_OPENSTREETMAP'), FILTER_VALIDATE_BOOLEAN) ?: true);

define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('DEBUG_MODE', filter_var(getenv('DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN) ?: false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

if (!is_dir(__DIR__ . '/cache')) {
    mkdir(__DIR__ . '/cache', 0777, true);
}

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}