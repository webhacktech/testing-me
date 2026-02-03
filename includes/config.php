<?php
// Global configuration
session_start();

// Database settings are loaded from environment variables for production safety.
// Set these in cPanel or your hosting environment.

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'crypto_exchange');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('BASE_URL', rtrim((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/'));

define('APP_NAME', 'NaijaCryptoX');

define('COINS', ['BTC', 'ETH', 'USDT', 'BNB']);

define('UPLOAD_DIR', __DIR__ . '/../uploads/selfies');

define('KYC_REQUIRED', true);

define('SUPPORT_EMAIL', getenv('SUPPORT_EMAIL') ?: 'support@naijacryptox.com');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'no-reply@naijacryptox.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);
define('MAIL_HOST', getenv('MAIL_HOST') ?: '');
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_PORT', getenv('MAIL_PORT') ?: '587');
define('MAIL_SECURE', getenv('MAIL_SECURE') ?: 'tls');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
