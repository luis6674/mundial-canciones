<?php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'mundial_canciones');
define('DB_USER', 'root');
define('DB_PASS', '');

// External auth service (Sony Music presave)
define('PRESAVE_URL', 'https://presaves.sonymusicfans.com/?campaign_id=YOUR_CAMPAIGN_ID');

// Voting window (Unix timestamps)
define('VOTING_OPEN',  strtotime('2026-07-01 00:00:00'));
define('VOTING_CLOSE', strtotime('2026-07-21 23:59:59'));

// App
define('APP_URL',  'https://yourdomain.com');
define('APP_NAME', 'Mundial de Canciones');
