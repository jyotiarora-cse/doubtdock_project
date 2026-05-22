<?php
// ── DoubtDock Configuration Template ──────────────────────────────────────────
// RENAME THIS FILE TO config.php AND FILL IN YOUR CREDENTIALS.
// Never commit the actual config.php to public repositories.

define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASS',     ''); // Your local DB password
define('DB_NAME',     'doubtdock');

define('MAIL_HOST',   'smtp.gmail.com');
define('MAIL_PORT',   587);
define('MAIL_USER',   'your-email@gmail.com');
define('MAIL_PASS',   'your-gmail-app-password'); // Gmail App Password (16 characters)
define('MAIL_FROM',   'your-email@gmail.com');
define('MAIL_NAME',   'DoubtDock');

define('APP_URL',     'http://localhost/doubtdock_project');
define('NODE_URL',    'http://localhost:3000');
?>
