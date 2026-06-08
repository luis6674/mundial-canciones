<?php
// Shared header — call render_header($title) from each page
function render_header(string $title = ''): void {
    $app_name  = APP_NAME;
    $full_title = $title ? htmlspecialchars($title) . ' | ' . htmlspecialchars($app_name) : htmlspecialchars($app_name);
    $now = time();
    $voting_open   = ($now >= VOTING_OPEN && $now <= VOTING_CLOSE);
    $voting_closed = ($now > VOTING_CLOSE);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user       = $_SESSION['user'] ?? null;
    $logged_in  = (bool)$user;
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $full_title ?></title>
  <link rel="stylesheet" href="/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a href="/" class="logo">
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
          <a href="/vote.php" class="nav-link">Mis votos</a>
        <?php endif; ?>
        <div class="user-chip">
          <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="" class="avatar">
          <?php endif; ?>
          <span><?= htmlspecialchars($user['display_name'] ?? 'Usuario') ?></span>
          <a href="/logout.php" class="logout-link">Salir</a>
        </div>
      <?php else: ?>
        <a href="/login-spotify.php" class="btn btn-spotify">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.516 17.311c-.217.356-.666.468-1.022.252-2.797-1.709-6.319-2.095-10.465-1.148-.4.091-.8-.158-.891-.558-.092-.4.158-.8.558-.891 4.538-1.037 8.43-.591 11.568 1.323.357.217.469.666.252 1.022zm1.471-3.27c-.272.44-.851.578-1.291.306-3.201-1.967-8.082-2.537-11.87-1.389-.492.148-1.013-.133-1.162-.625-.148-.492.133-1.013.625-1.162 4.327-1.314 9.703-.677 13.386 1.579.44.272.578.851.306 1.291zm.128-3.403c-3.841-2.28-10.178-2.49-13.845-1.377-.589.18-1.211-.153-1.391-.742-.18-.59.153-1.211.742-1.391 4.21-1.279 11.204-1.031 15.626 1.593.53.315.706 1.001.39 1.531-.314.53-1 .706-1.53.39l.008-.004z"/></svg>
          Entrar con Spotify
        </a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="site-main">
<?php
}

function render_footer(): void {
    ?>
</main>
<footer class="site-footer">
  <div class="footer-inner">
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?> &mdash; Impulsado por música &amp; pasión</p>
  </div>
</footer>
</body>
</html>
<?php
}
