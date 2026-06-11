<?php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'mundial_canciones');
define('DB_USER', 'root');
define('DB_PASS', '');

// External auth (Sony Music presave)
define('PRESAVE_BASE_URL',    'https://presaves.sonymusicfans.com/?campaign_id=702950&triggered_sends=&retargeting_consent=0&source_channel=Spotify');
define('PRESAVE_LIST_REQUIRED', 'a0S1p00000UlNguEAF');  // mandatory newsletter
define('PRESAVE_LIST_OPTIONAL', 'a0S61000000ZYfcEAG');  // optional newsletter

// Voting window (Unix timestamps)
define('VOTING_OPEN',  strtotime('2026-07-01 00:00:00'));
define('VOTING_CLOSE', strtotime('2026-07-21 23:59:59'));

// App
define('APP_URL',  'https://yourdomain.com');
define('APP_NAME', 'Mundial de Canciones');

// Session storage
// Leave SESSION_MEMCACHED_HOST empty to use default file-based sessions (local dev).
// Set to the Memcached host for multi-instance production environments.
define('SESSION_MEMCACHED_HOST', '');
define('SESSION_MEMCACHED_PORT', 11211);
