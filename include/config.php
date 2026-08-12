<?php
/**
 * Configuration file for Simple Blog Platform
 * 
 * This file defines configuration constants.
 * Database connection is handled in db.php using environment variables.
 */

// Application Configuration
define('APP_NAME', 'Simple Blog Platform');
define('APP_VERSION', '2.0.0');

// Timezone
date_default_timezone_set('UTC');

// Error Reporting (disable in production)
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>