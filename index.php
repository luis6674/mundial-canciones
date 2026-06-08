<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

render_header('Mundial de Canciones 2026');

$now          = time();
$voting_open  = ($now >= VOTING_OPEN && $now <= VOTING_CLOSE);
$voting_closed = ($now > VOTING_CLOSE);
$before_voting = ($now < VOTING_OPEN);

// Fetch songs for the grid display
$db    = get_db();
$songs = $db->query('SELECT * FROM songs ORDER BY display_order ASC')->fetchAll();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user      = $_SESSION['user'] ?? null;
$logged_in = (bool)$user;

// Voting opens countdown target
$open_ts  = VOTING_OPEN;
$close_ts = VOTING_CLOSE;
?>

<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-content">
    <div class="hero-eyebrow">&#9917; Edición Mundial 2026</div>
    <h1 class="hero-title">Mundial de <span class="accent">Canciones</span></h1>
    <p class="hero-subtitle">16 canciones legendarias. 3 favoritas. Un campeón.<br>Vota y corona la mejor canción de nuestra era.</p>

    <?php if ($before_voting): ?>
      <div class="countdown-wrap">
        <p class="countdown-label">La votación abre en</p>
        <div class="countdown" data-target="<?= $open_ts ?>">
          <div class="cd-segment"><span class="cd-val" id="cd-days">--</span><span class="cd-unit">días</span></div>
          <div class="cd-sep">:</div>
          <div class="cd-segment"><span class="cd-val" id="cd-hours">--</span><span class="cd-unit">hrs</span></div>
          <div class="cd-sep">:</div>
          <div class="cd-segment"><span class="cd-val" id="cd-mins">--</span><span class="cd-unit">min</span></div>
          <div class="cd-sep">:</div>
          <div class="cd-segment"><span class="cd-val" id="cd-secs">--</span><span class="cd-unit">seg</span></div>
        </div>
      </div>
    <?php elseif ($voting_open): ?>
      <div class="hero-cta">
        <?php if ($logged_in): ?>
          <a href="vote.php" class="btn btn-primary btn-lg">&#127932; Votar ahora</a>
        <?php else: ?>
          <a href="login-spotify.php" class="btn btn-spotify btn-lg">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.516 17.311c-.217.356-.666.468-1.022.252-2.797-1.709-6.319-2.095-10.465-1.148-.4.091-.8-.158-.891-.558-.092-.4.158-.8.558-.891 4.538-1.037 8.43-.591 11.568 1.323.357.217.469.666.252 1.022zm1.471-3.27c-.272.44-.851.578-1.291.306-3.201-1.967-8.082-2.537-11.87-1.389-.492.148-1.013-.133-1.162-.625-.148-.492.133-1.013.625-1.162 4.327-1.314 9.703-.677 13.386 1.579.44.272.578.851.306 1.291zm.128-3.403c-3.841-2.28-10.178-2.49-13.845-1.377-.589.18-1.211-.153-1.391-.742-.18-.59.153-1.211.742-1.391 4.21-1.279 11.204-1.031 15.626 1.593.53.315.706 1.001.39 1.531-.314.53-1 .706-1.53.39l.008-.004z"/></svg>
            Entrar con Spotify para votar
          </a>
        <?php endif; ?>
        <p class="cta-note">Votación cierra el <?= date('j/n/Y', VOTING_CLOSE) ?></p>
      </div>
    <?php else: ?>
      <div class="hero-cta">
        <p class="closed-notice">&#127942; La votación ha cerrado. ¡Mira la clasificación final!</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Live Results / Podium -->
<section class="results-section" id="results">
  <div class="section-inner">
    <?php if ($voting_closed): ?>
      <h2 class="section-title">&#127942; Clasificación final</h2>
      <div id="podium-wrap" class="podium-wrap">
        <!-- Filled by results.js after fetching /api/results.php -->
        <div class="loading-spinner"></div>
      </div>
    <?php else: ?>
      <h2 class="section-title">&#128202; Clasificación en vivo</h2>
      <p class="section-sub">Resultados actualizados cada 8 segundos</p>
    <?php endif; ?>

    <div id="chart-wrap" class="chart-wrap <?= $voting_closed ? 'hidden' : '' ?>">
      <div class="loading-spinner" id="chart-loading"></div>
      <div id="bar-chart" class="bar-chart" aria-live="polite"></div>
    </div>
  </div>
</section>

<!-- Song Grid -->
<section class="songs-section">
  <div class="section-inner">
    <h2 class="section-title">&#127925; Las 16 canciones</h2>
    <div class="songs-grid">
      <?php foreach ($songs as $song): ?>
        <div class="song-card" data-song-id="<?= (int)$song['id'] ?>">
          <div class="song-cover">
            <?php if (!empty($song['cover_url'])): ?>
              <img
                src="<?= htmlspecialchars($song['cover_url']) ?>"
                alt="<?= htmlspecialchars($song['title']) ?> cover"
                class="cover-img"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
              >
            <?php endif; ?>
            <div class="cover-placeholder" style="<?= !empty($song['cover_url']) ? 'display:none' : '' ?>">
              <span>&#127925;</span>
            </div>
            <div class="song-number"><?= (int)$song['display_order'] ?></div>
          </div>
          <div class="song-info">
            <div class="song-title" title="<?= htmlspecialchars($song['title']) ?>"><?= htmlspecialchars($song['title']) ?></div>
            <div class="song-artist"><?= htmlspecialchars($song['artist']) ?></div>
          </div>
          <?php if (!empty($song['spotify_track_id'])): ?>
            <div class="song-preview">
              <button class="preview-btn" data-track="<?= htmlspecialchars($song['spotify_track_id']) ?>" aria-label="Escuchar <?= htmlspecialchars($song['title']) ?>">
                &#9654; Escuchar
              </button>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Spotify Preview Modal -->
<div id="preview-modal" class="modal" role="dialog" aria-modal="true" aria-label="Vista previa" hidden>
  <div class="modal-backdrop"></div>
  <div class="modal-content">
    <button class="modal-close" id="preview-close" aria-label="Cerrar">&times;</button>
    <iframe id="preview-iframe"
      src=""
      width="100%"
      height="152"
      frameborder="0"
      allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
      loading="lazy"
      title="Spotify Preview">
    </iframe>
  </div>
</div>

<script>
  const VOTING_OPEN   = <?= $voting_open   ? 'true' : 'false' ?>;
  const VOTING_CLOSED = <?= $voting_closed ? 'true' : 'false' ?>;
  const OPEN_TS       = <?= $open_ts ?>;
</script>
<script src="js/results.js"></script>
<script>
// Countdown
(function() {
  const target = OPEN_TS * 1000;
  function tick() {
    const diff = target - Date.now();
    if (diff <= 0) { location.reload(); return; }
    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    const pad = n => String(n).padStart(2, '0');
    const el = id => document.getElementById(id);
    if (el('cd-days'))  el('cd-days').textContent  = pad(d);
    if (el('cd-hours')) el('cd-hours').textContent = pad(h);
    if (el('cd-mins'))  el('cd-mins').textContent  = pad(m);
    if (el('cd-secs'))  el('cd-secs').textContent  = pad(s);
  }
  if (document.getElementById('cd-days')) {
    tick();
    setInterval(tick, 1000);
  }
})();

// Song preview modal
(function() {
  const modal   = document.getElementById('preview-modal');
  const iframe  = document.getElementById('preview-iframe');
  const closeBtn = document.getElementById('preview-close');
  const backdrop = modal ? modal.querySelector('.modal-backdrop') : null;

  document.querySelectorAll('.preview-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const track = btn.dataset.track;
      iframe.src = `https://open.spotify.com/embed/track/${track}?utm_source=generator&theme=0`;
      modal.hidden = false;
      document.body.classList.add('modal-open');
    });
  });

  function closeModal() {
    modal.hidden = true;
    iframe.src = '';
    document.body.classList.remove('modal-open');
  }

  if (closeBtn)  closeBtn.addEventListener('click', closeModal);
  if (backdrop)  backdrop.addEventListener('click', closeModal);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
})();
</script>

<?php render_footer(); ?>
