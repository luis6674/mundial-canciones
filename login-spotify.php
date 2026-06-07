<?php
/**
 * login-spotify.php
 * Builds the Spotify OAuth authorization URL and redirects the user.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in?
if (!empty($_SESSION['user'])) {
    header('Location: ' . rtrim(APP_URL, '/') . '/vote.php');
    exit;
}

// Generate and store a state token to prevent CSRF on the callback
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => SPOTIFY_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri'  => SPOTIFY_REDIRECT_URI,
    'scope'         => 'user-read-private user-read-email',
    'state'         => $state,
    'show_dialog'   => 'false',
]);

header('Location: https://accounts.spotify.com/authorize?' . $params);
exit;
