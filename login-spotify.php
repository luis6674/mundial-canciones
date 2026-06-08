<?php
/**
 * login-spotify.php
 * Redirects the user to the external presave/auth service.
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

header('Location: ' . PRESAVE_URL);
exit;
