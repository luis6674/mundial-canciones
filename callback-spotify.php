<?php
/**
 * callback-spotify.php
 * Callback from the external presave/auth service.
 * Expects ?email=user@example.com in the query string.
 * Upserts the user in DB by email, sets session, redirects to /vote.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

require_once __DIR__ . '/includes/session.php';

function auth_error(string $msg): never {
    render_header('Error de acceso');
    echo '<div class="section-inner" style="padding:3rem 1.25rem">';
    echo '<div class="alert alert-error">' . htmlspecialchars($msg) . '</div>';
    echo '<p><a href="/" class="btn btn-secondary">Volver al inicio</a></p>';
    echo '</div>';
    render_footer();
    exit;
}

/* ---- Referer check: must originate from our own domain or Spotify's auth pages ---- */
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$app_origin = parse_url(APP_URL, PHP_URL_SCHEME) . '://' . parse_url(APP_URL, PHP_URL_HOST);
if (!str_starts_with($referer, $app_origin)) {
    if (!str_starts_with($referer, 'https://challenge.spotify.com/') && !str_starts_with($referer, 'https://accounts.spotify.com/')) {
        auth_error('Acceso no autorizado. Por favor, inicia sesión desde el sitio.');
    }
}

/* ---- Check that the state is "thank-you" ---- */
if (($_GET['state'] ?? '') !== 'thank-you') {
    auth_error('No se pudo autenticar al usuario. Por favor, inténtalo de nuevo.');
}

/* ---- Extract and validate email ---- */
$email = trim($_GET['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    auth_error('No se recibió un correo electrónico válido. Por favor, intenta entrar de nuevo.');
}

// Normalise to lowercase
$email = strtolower($email);

// Derive a display name from the local part of the email
$display_name = ucfirst(explode('@', $email)[0]);

/* ---- Upsert user by email ---- */
try {
    $db = get_db();

    $stmt = $db->prepare("
        INSERT INTO users (email, display_name)
        VALUES (:email, :name)
        ON DUPLICATE KEY UPDATE
            display_name = IF(display_name = '' OR display_name IS NULL, VALUES(display_name), display_name)
    ");
    $stmt->execute([':email' => $email, ':name' => $display_name]);

    $fetch = $db->prepare('SELECT id, display_name FROM users WHERE email = ?');
    $fetch->execute([$email]);
    $user_row = $fetch->fetch();

    if (!$user_row) {
        auth_error('No se pudo recuperar el registro de usuario. Por favor, intenta de nuevo.');
    }

    // Regenerate session ID on login to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'           => (int)$user_row['id'],
        'display_name' => $user_row['display_name'],
        'avatar_url'   => null,
    ];

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Set a 30-day remember cookie; store only a SHA-256 hash in the DB
    $token = bin2hex(random_bytes(32));
    $db->prepare('UPDATE users SET remember_token = ? WHERE id = ?')
       ->execute([hash('sha256', $token), (int)$user_row['id']]);
    setcookie('rmb_tok', $token, [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

} catch (\Throwable $e) {
    auth_error('Error de base de datos al iniciar sesión. Por favor, intenta de nuevo.');
}

/* ---- Redirect ---- */
header('Location: ' . rtrim(APP_URL, '/') . '/vote.php');
exit;
