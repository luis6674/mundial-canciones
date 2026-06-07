<?php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'mundial_canciones');
define('DB_USER', 'root');
define('DB_PASS', '');

// Spotify OAuth
define('SPOTIFY_CLIENT_ID', 'YOUR_SPOTIFY_CLIENT_ID');
define('SPOTIFY_CLIENT_SECRET', 'YOUR_SPOTIFY_CLIENT_SECRET');
define('SPOTIFY_REDIRECT_URI', 'https://yourdomain.com/callback-spotify.php');

// Voting window (Unix timestamps)
define('VOTING_OPEN',  strtotime('2026-07-01 00:00:00'));
define('VOTING_CLOSE', strtotime('2026-07-21 23:59:59'));

// App
define('APP_URL',  'https://yourdomain.com');
define('APP_NAME', 'King of Songs 2026');
