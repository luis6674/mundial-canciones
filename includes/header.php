<?php
// Shared header — call render_header($title) from each page
function render_header(string $title = ''): void {
    $app_name  = APP_NAME;
    $app_url = APP_URL;
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
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
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
          <a href="logout.php" class="logout-link">Salir</a>
        </div>
      <?php else: ?>
        <a href="login-spotify.php" class="btn btn-spotify">
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
