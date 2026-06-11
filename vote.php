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

require_once __DIR__ . '/includes/session.php';

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
      <p class="vote-sub">La votación ha terminado. Estas fueron tus elecciones finales.</p>
    <?php else: ?>
      <p class="vote-sub">La votación abre el <?= date('j/n/Y', VOTING_OPEN) ?>.</p>
    <?php endif; ?>
  </div>

  <?php if (!$logged_in): ?>
    <!-- Not logged in -->
    <div class="login-prompt">
      <h2>Inicia sesión para votar</h2>
      <p>Necesitas entrar con Spotify para votar en el Mundial de Canciones 2026.</p>
      <?php require __DIR__ . '/includes/login-form.php'; ?>
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
      <div class="readonly-notice">&#9632; La votación ha terminado. Los resultados son definitivos.</div>
    <?php endif; ?>

    <!-- Slot strip — 1st / 2nd / 3rd picks summary -->
    <?php if ($voting_open): ?>
    <div class="slots-strip" id="slots-strip">
      <?php foreach ([1 => ['🥇','oro','5pts','primer'], 2 => ['🥈','plata','3pts','segundo'], 3 => ['🥉','bronce','1pt','tercer']] as $rank => [$medal, $label, $pts, $ord]): ?>
        <div class="slot" id="slot-<?= $rank ?>">
          <span class="slot-medal" aria-hidden="true"><?= $medal ?></span>
          <div class="slot-label"><?= $label ?> pick &bull; <?= $pts ?></div>
          <div class="slot-song-title" style="display:none"></div>
          <div class="slot-song-artist" style="display:none"></div>
          <div class="slot-empty-hint">Haz clic en una canción</div>
          <button class="slot-clear" style="display:none" aria-label="Quitar <?= $ord ?> voto">Quitar</button>
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
      <div class="giveaway-note hidden" id="giveaway-note">
        &#9917; ¿Quieres ganar una camiseta de la selección española de fútbol? Participa en el sorteo rellenando el formulario <a href="sorteo.php">aquí</a>.
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
            <div class="song-title"><?= htmlspecialchars($song['title']) ?></div>
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
<div id="preview-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="preview-modal-title" hidden>
  <div class="modal-backdrop" onclick="closePreview()"></div>
  <div class="modal-content">
    <h2 id="preview-modal-title" class="sr-only">Vista previa de la canción</h2>
    <button class="modal-close" onclick="closePreview()" aria-label="Cerrar vista previa">&times;</button>
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
var _previewOpener = null;
function openPreview(trackId) {
  var modal  = document.getElementById('preview-modal');
  var iframe = document.getElementById('preview-iframe');
  _previewOpener = document.activeElement;
  iframe.src = 'https://open.spotify.com/embed/track/' + trackId + '?utm_source=generator&theme=0';
  modal.hidden = false;
  document.body.classList.add('modal-open');
  var closeBtn = modal.querySelector('.modal-close');
  if (closeBtn) closeBtn.focus();
}
function closePreview() {
  var modal  = document.getElementById('preview-modal');
  var iframe = document.getElementById('preview-iframe');
  modal.hidden = true;
  iframe.src = '';
  document.body.classList.remove('modal-open');
  if (_previewOpener) { _previewOpener.focus(); _previewOpener = null; }
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
<script src="js/vote.js"></script>
<?php endif; ?>

<?php render_footer(); ?>
