<?php
// Configuration file for POS system

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_mala');
define('DB_USER', 'root');
define('DB_PASS', 'password');

// Beam API configuration
// Replace with your actual Beam API key and webhook secret
define('BEAM_API_KEY', 'your_beam_api_key_here');
define('BEAM_WEBHOOK_SECRET', 'your_beam_webhook_secret_here');

// Site base URL (used for building callback URL)
// Ensure no trailing slash
define('SITE_URL', 'http://localhost');

// Timezone
date_default_timezone_set('Asia/Bangkok');