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
    <a href="<?= htmlspecialchars($app_url) ?>" class="logo-link">
      <img src="images/logo_home.png" class="logo-img" alt="Mundial de Canciones 2026">
    </a>

    <nav class="site-nav" aria-label="Navegación principal">
      <a href="<?= htmlspecialchars($app_url) ?>#inicio" class="nav-link">Inicio</a>
      <?php if ($voting_open): ?>
        <a href="vote.php" class="nav-link">Vota</a>
      <?php endif; ?>
      <a href="<?= htmlspecialchars($app_url) ?>#premios" class="nav-link">Premios</a>
      <a href="<?= htmlspecialchars($app_url) ?>#como-funciona" class="nav-link">Cómo funciona</a>
      <a href="<?= htmlspecialchars($app_url) ?>#finalistas" class="nav-link">Finalistas</a>
      <a href="<?= htmlspecialchars($app_url) ?>#posiciones" class="nav-link">Posiciones</a>
    </nav>

    <div class="header-right">
      <?php if ($voting_open): ?>
        <span class="status-badge open">&#9679; Votación abierta</span>
      <?php elseif ($voting_closed): ?>
        <span class="status-badge closed">&#9632; Votación cerrada</span>
      <?php else: ?>
        <span class="status-badge upcoming">&#9650; Próximamente</span>
      <?php endif; ?>
      <?php if ($logged_in): ?>
        <div class="user-chip" id="user-chip">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="user-chip-icon" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
          <span><?= htmlspecialchars($user['display_name'] ?? 'Usuario') ?></span>
          <span class="user-chip-arrow">&#9660;</span>
          <div class="user-dropdown" id="user-dropdown">
            <div class="user-dropdown-name"><?= htmlspecialchars($user['display_name'] ?? 'Usuario') ?></div>
            <a href="logout.php?csrf=<?= urlencode($_SESSION['csrf_token'] ?? '') ?>" class="logout-link">Cerrar sesión</a>
          </div>
        </div>
      <?php elseif ($voting_open): ?>
        <a href="vote.php" class="btn btn-spotify btn-sm-header">Entrar</a>
      <?php endif; ?>
    </div>

    <button class="hamburger" id="hamburger-btn" aria-label="Abrir menú" aria-expanded="false" aria-controls="mobile-nav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<!-- Mobile nav overlay -->
<div class="mobile-nav" id="mobile-nav">
  <button class="mobile-nav-close" id="mobile-nav-close" aria-label="Cerrar menú">&times;</button>
  <nav aria-label="Menú móvil">
    <a href="<?= htmlspecialchars($app_url) ?>#inicio" class="nav-link">Inicio</a>
    <?php if ($voting_open): ?>
      <a href="vote.php" class="nav-link">Vota</a>
    <?php endif; ?>
    <a href="<?= htmlspecialchars($app_url) ?>#premios" class="nav-link">Premios</a>
    <a href="<?= htmlspecialchars($app_url) ?>#como-funciona" class="nav-link">Cómo funciona</a>
    <a href="<?= htmlspecialchars($app_url) ?>#finalistas" class="nav-link">Finalistas</a>
    <a href="<?= htmlspecialchars($app_url) ?>#posiciones" class="nav-link">Posiciones</a>
  </nav>
  <?php if ($logged_in): ?>
    <div class="mobile-nav-divider"></div>
    <span style="color:rgba(255,255,255,0.55);font-size:0.85rem"><?= htmlspecialchars($user['display_name'] ?? 'Usuario') ?></span>
    <a href="logout.php?csrf=<?= urlencode($_SESSION['csrf_token'] ?? '') ?>" class="nav-link" style="font-size:1rem">Cerrar sesión</a>
  <?php endif; ?>
</div>

<script>
(function() {
  var hamburger  = document.getElementById('hamburger-btn');
  var mobileNav  = document.getElementById('mobile-nav');
  var closeBtn   = document.getElementById('mobile-nav-close');
  var userChip   = document.getElementById('user-chip');

  function openMenu() {
    mobileNav.classList.add('open');
    hamburger.classList.add('open');
    hamburger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    if (closeBtn) closeBtn.focus();
  }
  function closeMenu() {
    mobileNav.classList.remove('open');
    if (hamburger) {
      hamburger.classList.remove('open');
      hamburger.setAttribute('aria-expanded', 'false');
      hamburger.focus();
    }
    document.body.style.overflow = '';
  }

  if (hamburger) hamburger.addEventListener('click', openMenu);
  if (closeBtn)  closeBtn.addEventListener('click', closeMenu);
  if (mobileNav) {
    mobileNav.querySelectorAll('.nav-link').forEach(function(link) {
      link.addEventListener('click', closeMenu);
    });
  }

  // User chip toggle
  if (userChip) {
    userChip.addEventListener('click', function(e) {
      e.stopPropagation();
      userChip.classList.toggle('open');
    });
    document.addEventListener('click', function() {
      userChip.classList.remove('open');
    });
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      if (mobileNav && mobileNav.classList.contains('open')) closeMenu();
      if (userChip) userChip.classList.remove('open');
    }
  });

  // Smooth-scroll anchor links that point to an ID on the same page
  document.addEventListener('click', function(e) {
    var link = e.target.closest('a[href*="#"]');
    if (!link) return;
    var href = link.getAttribute('href');
    var hash = href.indexOf('#') !== -1 ? href.slice(href.indexOf('#')) : '';
    if (!hash || hash === '#') return;
    // Only intercept if the base URL matches this page (or is hash-only)
    var base = href.slice(0, href.indexOf('#'));
    if (base && base !== window.location.pathname && base !== window.location.href.replace(window.location.hash, '')) return;
    var target = document.querySelector(hash);
    if (!target) return;
    e.preventDefault();
    if (mobileNav && mobileNav.classList.contains('open')) closeMenu();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    history.pushState(null, '', hash);
  });
})();
</script>

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
