<?php
/**
 * vote.php — Voting page. Requires login.
 * Shows 16 song cards; users pick top 3 (1st/2nd/3rd).
 * After voting closes, shows read-only final picks.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user      = $_SESSION['user'] ?? null;
$logged_in = (bool)$user;

$now           = time();
$voting_open   = ($now >= VOTING_OPEN && $now <= VOTING_CLOSE);
$voting_closed = ($now > VOTING_CLOSE);

// Ensure CSRF token exists
if ($logged_in && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'] ?? '';

// Fetch all songs
$db    = get_db();
$songs = $db->query('SELECT * FROM songs ORDER BY display_order ASC')->fetchAll();

// If voting is closed and user is logged in, load their final picks for read-only display
$saved_picks = []; // rank => song_id
if ($logged_in && ($voting_closed || $voting_open)) {
    $stmt = $db->prepare(
        'SELECT rank_position, song_id FROM votes WHERE user_id = ? ORDER BY rank_position ASC'
    );
    $stmt->execute([(int)$user['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $saved_picks[(int)$row['rank_position']] = (int)$row['song_id'];
    }
}

// Build song lookup by id
$songs_by_id = [];
foreach ($songs as $s) {
    $songs_by_id[(int)$s['id']] = $s;
}

render_header('Vota tus favoritas');
?>

<div class="section-inner vote-page">

  <div class="vote-header">
    <h1 class="vote-title">&#127932; Elige tus <span class="accent">favoritas</span></h1>
    <?php if ($voting_open): ?>
      <p class="vote-sub">Elige tus 3 canciones favoritas. Puedes cambiar tu voto cuando quieras antes del <?= date('j/n/Y', VOTING_CLOSE) ?>.</p>
    <?php elseif ($voting_closed): ?>
      <p class="vote-sub">La votación ha cerrado. Estas fueron tus elecciones finales.</p>
    <?php else: ?>
      <p class="vote-sub">La votación abre el <?= date('j/n/Y', VOTING_OPEN) ?>.</p>
    <?php endif; ?>
  </div>

  <?php if (!$logged_in): ?>
    <!-- Not logged in -->
    <div class="login-prompt">
      <h2>Inicia sesión para votar</h2>
      <p>Necesitas entrar con Spotify para votar en el Mundial de Canciones 2026.</p>
      <a href="/login-spotify.php" class="btn btn-spotify btn-lg">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.516 17.311c-.217.356-.666.468-1.022.252-2.797-1.709-6.319-2.095-10.465-1.148-.4.091-.8-.158-.891-.558-.092-.4.158-.8.558-.891 4.538-1.037 8.43-.591 11.568 1.323.357.217.469.666.252 1.022zm1.471-3.27c-.272.44-.851.578-1.291.306-3.201-1.967-8.082-2.537-11.87-1.389-.492.148-1.013-.133-1.162-.625-.148-.492.133-1.013.625-1.162 4.327-1.314 9.703-.677 13.386 1.579.44.272.578.851.306 1.291zm.128-3.403c-3.841-2.28-10.178-2.49-13.845-1.377-.589.18-1.211-.153-1.391-.742-.18-.59.153-1.211.742-1.391 4.21-1.279 11.204-1.031 15.626 1.593.53.315.706 1.001.39 1.531-.314.53-1 .706-1.53.39l.008-.004z"/></svg>
        Entrar con Spotify
      </a>
    </div>

  <?php elseif (!$voting_open && !$voting_closed): ?>
    <!-- Before voting -->
    <div class="login-prompt">
      <h2>La votación aún no ha empezado</h2>
      <p>¡Vuelve el <strong><?= date('j/n/Y', VOTING_OPEN) ?></strong> para votar!</p>
      <a href="/" class="btn btn-secondary">&#8592; Volver al inicio</a>
    </div>

  <?php else: ?>

    <?php if ($voting_closed): ?>
      <div class="readonly-notice">&#9632; La votación ha cerrado. Los resultados son definitivos.</div>
    <?php endif; ?>

    <!-- Slot strip — 1st / 2nd / 3rd picks summary -->
    <?php if ($voting_open): ?>
    <div class="slots-strip" id="slots-strip">
      <?php foreach ([1 => ['🥇','oro','5pts'], 2 => ['🥈','plata','3pts'], 3 => ['🥉','bronce','1pt']] as $rank => [$medal, $label, $pts]): ?>
        <div class="slot" id="slot-<?= $rank ?>">
          <span class="slot-medal"><?= $medal ?></span>
          <div class="slot-label"><?= $label ?> pick &bull; <?= $pts ?></div>
          <div class="slot-song-title" style="display:none"></div>
          <div class="slot-song-artist" style="display:none"></div>
          <div class="slot-empty-hint">Haz clic en una canción</div>
          <button class="slot-clear" style="display:none">Quitar</button>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Sticky save bar -->
    <div class="save-bar" id="save-bar">
      <div class="save-bar-inner hidden" id="save-bar-inner">
        <button class="btn btn-primary" id="save-btn">Guardar votos</button>
        <span id="save-status"></span>
        <input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrf_token) ?>">
      </div>
    </div>
    <?php endif; ?>

    <!-- Song grid -->
    <div class="songs-grid" id="vote-grid">
      <?php foreach ($songs as $song):
        $sid = (int)$song['id'];
        $picked_rank = array_search($sid, $saved_picks, true);
        $card_class  = 'song-card';
        if ($voting_open) $card_class .= ' selectable';
        if ($picked_rank !== false) $card_class .= ' picked-' . $picked_rank;

        $badge_num   = ($picked_rank !== false) ? $picked_rank : '';
        $badge_cls   = ($picked_rank !== false) ? "rank-badge badge-$picked_rank" : 'rank-badge hidden';
      ?>
        <div
          class="<?= $card_class ?>"
          data-song-id="<?= $sid ?>"
          data-title="<?= htmlspecialchars($song['title']) ?>"
          data-artist="<?= htmlspecialchars($song['artist']) ?>"
          <?php if ($voting_open): ?>role="button" tabindex="0" aria-label="Seleccionar <?= htmlspecialchars($song['title']) ?>"<?php endif; ?>
        >
          <!-- Rank badge (shown when picked) -->
          <div class="<?= $badge_cls ?>"><?= $badge_num ?></div>

          <!-- Cover art -->
          <div class="song-cover">
            <?php if (!empty($song['cover_url'])): ?>
              <img
                src="<?= htmlspecialchars($song['cover_url']) ?>"
                alt="<?= htmlspecialchars($song['title']) ?> cover art"
                class="cover-img"
                loading="lazy"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
              >
            <?php endif; ?>
            <div class="cover-placeholder" style="<?= !empty($song['cover_url']) ? 'display:none' : '' ?>">
              <span>&#127925;</span>
            </div>
            <div class="song-number"><?= (int)$song['display_order'] ?></div>
          </div>

          <!-- Info -->
          <div class="song-info">
            <div class="song-title" title="<?= htmlspecialchars($song['title']) ?>"><?= htmlspecialchars($song['title']) ?></div>
            <div class="song-artist"><?= htmlspecialchars($song['artist']) ?></div>
          </div>

          <!-- Spotify embed preview -->
          <?php if (!empty($song['spotify_track_id'])): ?>
          <div class="song-preview">
            <button
              class="preview-btn"
              data-track="<?= htmlspecialchars($song['spotify_track_id']) ?>"
              aria-label="Escuchar <?= htmlspecialchars($song['title']) ?>"
              onclick="event.stopPropagation(); openPreview(this.dataset.track)"
            >
              &#9654; Escuchar
            </button>
          </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>
</div><!-- /.vote-page -->

<!-- Spotify Preview Modal -->
<div id="preview-modal" class="modal" role="dialog" aria-modal="true" aria-label="Song Preview" hidden>
  <div class="modal-backdrop" onclick="closePreview()"></div>
  <div class="modal-content">
    <button class="modal-close" onclick="closePreview()" aria-label="Cerrar">&times;</button>
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
function openPreview(trackId) {
  var modal  = document.getElementById('preview-modal');
  var iframe = document.getElementById('preview-iframe');
  iframe.src = 'https://open.spotify.com/embed/track/' + trackId + '?utm_source=generator&theme=0';
  modal.hidden = false;
  document.body.classList.add('modal-open');
}
function closePreview() {
  var modal  = document.getElementById('preview-modal');
  var iframe = document.getElementById('preview-iframe');
  modal.hidden = true;
  iframe.src = '';
  document.body.classList.remove('modal-open');
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePreview(); });

// Keyboard accessibility for song cards
document.querySelectorAll('.song-card.selectable').forEach(function(card) {
  card.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
  });
});
</script>

<?php if ($voting_open): ?>
<script src="/js/vote.js"></script>
<?php endif; ?>

<?php render_footer(); ?>
