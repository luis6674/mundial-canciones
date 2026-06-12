<?php
// Shared header — call render_header($title) from each page
function render_header(string $title = ''): void {
    $app_name  = APP_NAME;
    $app_url = APP_URL;
    $full_title = $title ? htmlspecialchars($title) . ' | ' . htmlspecialchars($app_name) : htmlspecialchars($app_name);
    $now = time();
    $voting_open   = ($now >= VOTING_OPEN && $now <= VOTING_CLOSE);
    $voting_closed = ($now > VOTING_CLOSE);

    require_once __DIR__ . '/session.php';

    // Auto-login returning users via remember cookie
    if (empty($_SESSION['user']) && !empty($_COOKIE['rmb_tok'])) {
        try {
            $db  = get_db();
            $row = $db->prepare('SELECT id, display_name FROM users WHERE remember_token = ?');
            $row->execute([hash('sha256', $_COOKIE['rmb_tok'])]);
            $row = $row->fetch();
            if ($row) {
                // Regenerate session and rotate the remember token on each auto-login
                session_regenerate_id(true);
                $newToken = bin2hex(random_bytes(32));
                $db->prepare('UPDATE users SET remember_token = ? WHERE id = ?')
                   ->execute([hash('sha256', $newToken), (int)$row['id']]);
                setcookie('rmb_tok', $newToken, [
                    'expires'  => time() + 60 * 60 * 24 * 30,
                    'path'     => '/',
                    'secure'   => true,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
                $_SESSION['user'] = ['id' => (int)$row['id'], 'display_name' => $row['display_name'], 'avatar_url' => null];
                if (empty($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
            } else {
                setcookie('rmb_tok', '', time() - 3600, '/', '', true, true);
            }
        } catch (\Throwable $e) { /* silently skip */ }
    }

    $user       = $_SESSION['user'] ?? null;
    $logged_in  = (bool)$user;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <meta name="description" content="16 canciones finalistas. 3 favoritas. Una campeona. Vota la mejor canción del mundial 2026 y participa en el sorteo de una camiseta de la selección española de fútbol.">
  <meta name="keywords" content="Mundial, canciones, finalistas, campeona, 2026, artistas, shakira, fútbol, football, FIFA, España, Spain, Selección, camiseta, la roja, sorteo, música, Sony, music">
  <title><?= $full_title ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Open Graph -->
  <meta property="og:site_name" content="Mundial de Canciones" />
  <meta property="og:title" content="Mundial de Canciones" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://www.hitsmundialseleccionespanola.com/" />
  <meta property="og:description" content="16 canciones finalistas. 3 favoritas. Una campeona. Vota la mejor canción del mundial 2026 y participa en el sorteo de una camiseta de la selección española de fútbol." />
  <meta property="og:image" content="https://www.hitsmundialseleccionespanola.com/images/portada_redes.jpg" />
  <meta property="og:image:width" content="800" />
  <meta property="og:image:height" content="481" />
  <meta property="og:locale" content="es_ES" />
  <!-- Twitter / X -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@SonyMusicSpain">
  <meta name="twitter:creator" content="@SonyMusicSpain">
  <meta name="twitter:title" content="Mundial de Canciones">
  <meta name="twitter:description" content="16 canciones finalistas. 3 favoritas. Una campeona. Vota la mejor canción del mundial 2026 y participa en el sorteo de una camiseta de la selección española de fútbol.">
  <meta name="twitter:image" content="https://www.hitsmundialseleccionespanola.com/images/portada_redes.jpg">
  <meta name="twitter:domain" content="www.hitsmundialseleccionespanola.com">
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-N4ZXST');</script>
  <!-- End Google Tag Manager -->
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-N4ZXST" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<a class="skip-link" href="#main-content">Saltar al contenido principal</a>
<header class="site-header">
  <div class="header-inner">
    <a href="<?= htmlspecialchars($app_url) ?>" class="logo">
      <span class="trophy">&#127942;</span>
      <span class="logo-text"><?= htmlspecialchars($app_name) ?></span>
    </a>
    <nav class="main-nav">
      <?php if ($voting_open): ?>
        <span class="status-badge open">&#9679; Votación abierta</span>
      <?php elseif ($voting_closed): ?>
        <span class="status-badge closed">&#9632; Votación cerrada</span>
      <?php else: ?>
        <span class="status-badge upcoming">&#9650; Próximamente</span>
      <?php endif; ?>

      <?php if ($logged_in): ?>
        <?php if ($voting_open): ?>
          <a href="vote.php" class="nav-link">Mis votos</a>
        <?php endif; ?>
        <div class="user-chip">
          <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="" class="avatar">
          <?php endif; ?>
          <span><?= htmlspecialchars($user['display_name'] ?? 'Usuario') ?></span>
          <a href="logout.php?csrf=<?= urlencode($_SESSION['csrf_token'] ?? '') ?>" class="logout-link">Salir</a>
        </div>
      <?php else: ?>
        <a href="vote.php" class="btn btn-spotify">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.516 17.311c-.217.356-.666.468-1.022.252-2.797-1.709-6.319-2.095-10.465-1.148-.4.091-.8-.158-.891-.558-.092-.4.158-.8.558-.891 4.538-1.037 8.43-.591 11.568 1.323.357.217.469.666.252 1.022zm1.471-3.27c-.272.44-.851.578-1.291.306-3.201-1.967-8.082-2.537-11.87-1.389-.492.148-1.013-.133-1.162-.625-.148-.492.133-1.013.625-1.162 4.327-1.314 9.703-.677 13.386 1.579.44.272.578.851.306 1.291zm.128-3.403c-3.841-2.28-10.178-2.49-13.845-1.377-.589.18-1.211-.153-1.391-.742-.18-.59.153-1.211.742-1.391 4.21-1.279 11.204-1.031 15.626 1.593.53.315.706 1.001.39 1.531-.314.53-1 .706-1.53.39l.008-.004z"/></svg>
          Entrar con Spotify
        </a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="site-main" id="main-content">
<?php
}

function render_footer(): void {
    ?>
</main>
<footer class="site-footer">
  <div class="footer-inner">
    <p class="footer-links">&copy; <?= date('Y') ?>, <a href="https://www.sonymusic.es/" target="_blank" rel="noopener">Sony Music Entertainment España, S.L.</a></p>
    <p class="footer-links">
      <a href="https://www.sonymusic.es/reservados-todos-los-derechos/" target="_blank" rel="noopener">Reservados todos los derechos</a>
      <span class="footer-sep">|</span>
      <a href="https://www.sonymusic.es/politica-de-privacidad-y-cookies/" target="_blank" rel="noopener">Política de privacidad y cookies</a>
      <span class="footer-sep">|</span>
      <a href="https://www.sonymusic.es/condiciones-generales/" target="_blank" rel="noopener">Condiciones generales</a>
    </p>
  </div>
</footer>
</body>
</html>
<?php
}
