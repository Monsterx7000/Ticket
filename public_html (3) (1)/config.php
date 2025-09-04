<?php
// ===== Rename this file to config.php after filling in your DB details =====
// Hostinger tip: In many shared plans, DB_HOST = 'localhost'. Otherwise copy the MySQL Hostname from hPanel.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'u150331968_db4');
define('DB_USER', getenv('DB_USER') ?: 'u150331968_admin4');
define('DB_PASS', getenv('DB_PASS') ?: '581h94wDB4@');
define('APP_NAME', 'Helpdesk Tickets');
// Set default language: 'ar' or 'en'
define('DEFAULT_LANG', 'ar');
// Max upload in bytes (5 MB)
define('MAX_UPLOAD', 5 * 1024 * 1024);
// Allowed upload extensions (lowercase, no dot)
$ALLOWED_EXT = ['jpg','jpeg','png','gif','pdf','txt'];
