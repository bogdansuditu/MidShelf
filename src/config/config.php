<?php
session_start();

define('DB_PATH', getenv('SQLITE_DB_PATH') ?: '/var/www/data/midshelf.db');
define('APP_NAME', 'MidShelf');
define('APP_VERSION', '1.0.0');

// Authentication settings
define('SESSION_DURATION', 3600); // 1 hour
define('MIN_PASSWORD_LENGTH', 8);

// Application settings
define('ITEMS_PER_PAGE', 20);
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/data/error.log');
