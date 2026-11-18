<?php
// COPY this file as api/config.php, then replace the placeholder below with a
// unique secret of at least 32 random characters. NEVER commit/share config.php.
// Example of generating a secret: php -r 'echo bin2hex(random_bytes(24)), PHP_EOL;'
define('FT_SETUP_KEY', 'REPLACE_WITH_A_UNIQUE_RANDOM_SECRET_OF_32_OR_MORE_CHARACTERS');
// Optional (v0.0.10): only needed if the app is NOT installed at /marketing-editor/
// and you run the cPanel cron job. Example: define('FT_BASE_PATH', '/signage');
