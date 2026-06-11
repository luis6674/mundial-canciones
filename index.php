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

require_once __DIR__ . '/includes/session.php';
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
    <p class="hero-subtitle">16 canciones finalistas. Una campeona.<br>Vota la mejor canción del mundial 2026 y participa en el sorteo de una camiseta de la selección española de fútbol.</p>

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
          <?php require __DIR__ . '/includes/login-form.php'; ?>
        <?php endif; ?>
        <p class="cta-note">Votación cierra el <?= date('j/n/Y', VOTING_CLOSE) ?></p>
      </div>
    <?php else: ?>
      <div class="hero-cta">
        <p class="closed-notice">&#127942; La votación ha terminado. ¡Mira la clasificación final!</p>
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
            <div class="song-title"><?= htmlspecialchars($song['title']) ?></div>
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
<div id="preview-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="preview-modal-title" hidden>
  <div class="modal-backdrop"></div>
  <div class="modal-content">
    <h2 id="preview-modal-title" class="sr-only">Vista previa de la canción</h2>
    <button class="modal-close" id="preview-close" aria-label="Cerrar vista previa">&times;</button>
    <iframe id="preview-iframe"
      src=""
      width="100%"
      height="152"
      frameborder="0"
      allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
      loading="lazy"
      title="Vista previa de Spotify">
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
  let opener = null;

  document.querySelectorAll('.preview-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      opener = btn;
      const track = btn.dataset.track;
      iframe.src = `https://open.spotify.com/embed/track/${track}?utm_source=generator&theme=0`;
      modal.hidden = false;
      document.body.classList.add('modal-open');
      if (closeBtn) closeBtn.focus();
    });
  });

  function closeModal() {
    modal.hidden = true;
    iframe.src = '';
    document.body.classList.remove('modal-open');
    if (opener) { opener.focus(); opener = null; }
  }

  if (closeBtn)  closeBtn.addEventListener('click', closeModal);
  if (backdrop)  backdrop.addEventListener('click', closeModal);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
})();
</script>

<?php render_footer(); ?>
