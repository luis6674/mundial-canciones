<?php
/**
 * callback-spotify.php
 * Spotify OAuth 2.0 callback handler.
 * 1. Validates state
 * 2. Exchanges code for access token
 * 3. Fetches /v1/me profile
 * 4. Upserts user in DB
 * 5. Sets session, redirects to /vote.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---- Helper: show an error page ---- */
function auth_error(string $msg): never {
    render_header('Error de acceso');
    echo '<div class="section-inner" style="padding:3rem 1.25rem">';
    echo '<div class="alert alert-error">' . htmlspecialchars($msg) . '</div>';
    echo '<p><a href="/" class="btn btn-secondary">Volver al inicio</a></p>';
    echo '</div>';
    render_footer();
    exit;
}

/* ---- Validate state ---- */
$state_param    = $_GET['state']    ?? '';
$state_session  = $_SESSION['spotify_oauth_state'] ?? '';
unset($_SESSION['spotify_oauth_state']);

if (!$state_session || !hash_equals($state_session, $state_param)) {
    auth_error('Estado OAuth inválido. Por favor, intenta entrar de nuevo.');
}

/* ---- Check for error from Spotify ---- */
if (isset($_GET['error'])) {
    $err = htmlspecialchars($_GET['error']);
    auth_error("Spotify devolvió un error: $err. Por favor, intenta de nuevo.");
}

$code = $_GET['code'] ?? '';
if ($code === '') {
    auth_error('No se recibió código de autorización de Spotify.');
}

/* ---- Exchange code for token ---- */
$token_response = spotify_post('https://accounts.spotify.com/api/token', [
    'grant_type'   => 'authorization_code',
    'code'         => $code,
    'redirect_uri' => SPOTIFY_REDIRECT_URI,
], [
    'Authorization: Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
    'Content-Type: application/x-www-form-urlencoded',
]);

if (!$token_response || empty($token_response['access_token'])) {
    auth_error('No se pudo obtener el token de acceso de Spotify. Por favor, intenta de nuevo.');
}

$access_token = $token_response['access_token'];

/* ---- Fetch user profile ---- */
$profile = spotify_get('https://api.spotify.com/v1/me', $access_token);

if (!$profile || empty($profile['id'])) {
    auth_error('No se pudo obtener tu perfil de Spotify. Por favor, intenta de nuevo.');
}

$provider_user_id = $profile['id'];
$display_name     = $profile['display_name'] ?? $profile['id'];
$avatar_url       = $profile['images'][0]['url'] ?? null;

/* ---- Upsert into users table ---- */
try {
    $db = get_db();

    $stmt = $db->prepare("
        INSERT INTO users (provider, provider_user_id, display_name, avatar_url)
        VALUES ('spotify', :pid, :name, :avatar)
        ON DUPLICATE KEY UPDATE
            display_name = VALUES(display_name),
            avatar_url   = VALUES(avatar_url)
    ");
    $stmt->execute([
        ':pid'    => $provider_user_id,
        ':name'   => $display_name,
        ':avatar' => $avatar_url,
    ]);

    // Fetch the user id
    $fetch = $db->prepare(
        "SELECT id, display_name, avatar_url FROM users WHERE provider = 'spotify' AND provider_user_id = ?"
    );
    $fetch->execute([$provider_user_id]);
    $user_row = $fetch->fetch();

    if (!$user_row) {
        auth_error('No se pudo recuperar el registro de usuario. Por favor, intenta de nuevo.');
    }

    // Store in session
    $_SESSION['user'] = [
        'id'           => (int)$user_row['id'],
        'display_name' => $user_row['display_name'],
        'avatar_url'   => $user_row['avatar_url'],
        'provider'     => 'spotify',
    ];

    // Issue CSRF token for the vote form
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

} catch (\Throwable $e) {
    auth_error('Error de base de datos al iniciar sesión. Por favor, intenta de nuevo.');
}

/* ---- Redirect ---- */
header('Location: ' . rtrim(APP_URL, '/') . '/vote.php');
exit;

/* ============================================================
   Helper functions
   ============================================================ */

/**
 * POST to a URL with form-encoded body, returns decoded JSON array.
 */
function spotify_post(string $url, array $fields, array $headers): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if ($body === false) return null;
    try {
        return json_decode($body, true, 8, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        return null;
    }
}

/**
 * GET a URL with Bearer token, returns decoded JSON array.
 */
function spotify_get(string $url, string $access_token): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $access_token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if ($body === false) return null;
    try {
        return json_decode($body, true, 8, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        return null;
    }
}
