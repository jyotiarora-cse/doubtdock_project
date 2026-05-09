<?php
// ── DoubtDock Configuration ───────────────────────────────────────────────────
// Move this file OUTSIDE the web root in production for maximum security.
// All sensitive credentials live here. Never commit this to public repos.

define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_NAME',     'doubtdock');

define('MAIL_HOST',   'smtp.gmail.com');
define('MAIL_PORT',   587);
define('MAIL_USER',   'doubtdock.system@gmail.com');
define('MAIL_PASS',   'pnln jjit xtwq movz');   // Gmail App Password
define('MAIL_FROM',   'doubtdock.system@gmail.com');
define('MAIL_NAME',   'DoubtDock');

define('APP_URL',     'http://localhost/doubtdock_project');
define('NODE_URL',    'http://localhost:3000');
?>
