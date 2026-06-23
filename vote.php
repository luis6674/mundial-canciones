<?php
/**
 * vote.php — Voting page. Requires login.
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

if ($logged_in && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'] ?? '';

$db    = get_db();
$songs = $db->query('SELECT * FROM songs ORDER BY display_order ASC')->fetchAll();

$saved_picks = [];
if ($logged_in && ($voting_closed || $voting_open)) {
    $stmt = $db->prepare('SELECT rank_position, song_id FROM votes WHERE user_id = ? ORDER BY rank_position ASC');
    $stmt->execute([(int)$user['id']]);
    foreach ($stmt->fetchAll() as $row) {
        $saved_picks[(int)$row['rank_position']] = (int)$row['song_id'];
    }
}

$songs_by_id = [];
foreach ($songs as $s) {
    $songs_by_id[(int)$s['id']] = $s;
}

$months_es = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
$close_day_num    = (int)date('j', VOTING_CLOSE);
$close_month_name = $months_es[(int)date('n', VOTING_CLOSE)];

render_header('Vota tus favoritas');
?>

<div class="vote-outer<?= ($logged_in && ($voting_open || $voting_closed)) ? '' : ' vote-outer--centered' ?>">
  <div class="vote-page-wrap">

    <?php if (!$logged_in): ?>
      <div class="vote-login-prompt">
        <h1 class="vote-title">Elige tus <span class="accent">3 Favoritas</span></h1>
        <p class="vote-sub">16 canciones finalistas. 3 favoritas. Una campeona.</p>
        <div class="login-box">
          <h2 class="login-box-title">Inicia sesión para votar</h2>
          <p class="login-box-sub">Necesitas entrar con Spotify para votar en el Mundial de Canciones 2026.</p>
          <?php require __DIR__ . '/includes/login-form.php'; ?>
        </div>
      </div>

    <?php elseif (!$voting_open && !$voting_closed): ?>
      <div class="vote-login-prompt">
        <h1 class="vote-title">La votación aún no ha empezado</h1>
        <p class="vote-sub">¡Vuelve el <strong><?= $close_day_num ?> de <?= $close_month_name ?></strong> para votar!</p>
        <a href="index.php" class="btn btn-secondary">&#8592; Volver al inicio</a>
      </div>

    <?php else: ?>
      <?php
      $rank_badges = [1 => 'oro_badge.png', 2 => 'Plata_badge.png', 3 => 'Bronce_badge.png'];
      $rank_labels = [1 => '1er puesto', 2 => '2º puesto', 3 => '3er puesto'];
      ?>

      <!-- Page header (spans full width) -->
      <div class="vote-main-header">
          <h1 class="vote-page-title">Elige tus <span class="accent">3 Favoritas</span></h1>
          <div class="vote-page-subtitle">
            <img src="images/adorno_titulo_izquierdo.png" alt="" aria-hidden="true">
            Las 16 Finalistas
            <img src="images/adorno_titulo_derecho.png" alt="" aria-hidden="true">
          </div>
          <?php if ($voting_closed): ?>
            <p class="vote-sub">La votación ha terminado. Estas fueron tus elecciones finales.</p>
          <?php else: ?>
            <p class="vote-sub">Elige tus 3 canciones favoritas. Puedes cambiar tu voto antes del <?= $close_day_num ?> de <?= $close_month_name ?>.</p>
          <?php endif; ?>
        </div>

        <?php if ($voting_open): ?>
        <!-- Mobile selection strip (3 slots, visible on mobile only) -->
        <div class="mobile-selection-strip" id="mobile-strip">
          <div class="mstrip-slots">
            <?php foreach ([1, 2, 3] as $rank): ?>
              <?php if ($rank > 1): ?><div class="mstrip-divider"></div><?php endif; ?>
              <div class="mstrip-slot empty" id="mslot-<?= $rank ?>">
                <img src="images/<?= $rank ?>_puesto.png" alt="<?= $rank ?>º puesto" class="mstrip-placeholder-img">
                <img src="images/<?= $rank_badges[$rank] ?>" alt="<?= $rank_labels[$rank] ?>" class="mstrip-badge-img" style="display:none">
                <div class="mstrip-song-info" style="display:none">
                  <div class="mstrip-slot-title"></div>
                  <div class="mstrip-slot-artist"></div>
                </div>
                <span class="mstrip-dots" aria-hidden="true">• • •</span>
                <button class="mstrip-remove hidden" data-rank="<?= $rank ?>" aria-label="Quitar <?= $rank ?>º voto">
                  <img src="images/papelera_icono.png" alt="" aria-hidden="true">
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="giveaway-note hidden mobile-only" id="giveaway-note-mobile">
          &#9917; ¿Quieres ganar una camiseta de la selección española? <a href="sorteo.php">Participa en el sorteo</a>.
        </div>
        <?php endif; ?>

        <!-- Song grid -->
        <div class="vote-grid" id="vote-grid">
          <?php foreach ($songs as $song):
            $sid         = (int)$song['id'];
            $picked_rank = array_search($sid, $saved_picks, true);
            $is_selected = ($picked_rank !== false);
            $card_class  = 'song-card' . ($voting_open ? ' selectable' : '') . ($is_selected ? ' selected' : '');
          ?>
            <div
              class="<?= $card_class ?>"
              data-song-id="<?= $sid ?>"
              data-title="<?= htmlspecialchars($song['title']) ?>"
              data-artist="<?= htmlspecialchars($song['artist']) ?>"
              <?php if ($voting_open): ?>role="button" tabindex="0" aria-pressed="<?= $is_selected ? 'true' : 'false' ?>"<?php endif; ?>
            >
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
                <div class="song-number-badge"><?= (int)$song['display_order'] ?></div>
                <?php if ($voting_open): ?>
                  <div class="selection-circle" aria-hidden="true"></div>
                <?php elseif ($is_selected): ?>
                  <div class="selection-circle selected-static" aria-hidden="true">
                    <?= (int)$picked_rank ?>
                  </div>
                <?php endif; ?>
              </div>

              <div class="song-info">
                <div class="song-title"><?= htmlspecialchars($song['title']) ?></div>
                <div class="song-artist"><?= htmlspecialchars($song['artist']) ?></div>
              </div>

              <?php if ($voting_open): ?>
              <div class="song-actions">
                <?php if (!empty($song['spotify_track_id'])): ?>
                  <button class="btn-listen preview-btn" data-track="<?= htmlspecialchars($song['spotify_track_id']) ?>" aria-label="Escuchar <?= htmlspecialchars($song['title']) ?>">
                    &#9654; Escuchar
                  </button>
                <?php endif; ?>
                <button class="btn-select<?= $is_selected ? ' selected-btn' : '' ?>" data-song-id="<?= $sid ?>" aria-label="<?= $is_selected ? 'Quitar' : 'Seleccionar' ?> <?= htmlspecialchars($song['title']) ?>">
                  <?= $is_selected ? 'Quitar' : 'Seleccionar' ?>
                </button>
              </div>
              <?php elseif (!empty($song['spotify_track_id'])): ?>
              <div class="song-actions">
                <button class="btn-listen preview-btn" data-track="<?= htmlspecialchars($song['spotify_track_id']) ?>" aria-label="Escuchar <?= htmlspecialchars($song['title']) ?>">
                  &#9654; Escuchar
                </button>
              </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

      <?php if ($voting_open): ?>
      <!-- SIDEBAR (desktop) -->
      <aside class="vote-sidebar" aria-label="Tu selección">
        <div class="sidebar-header">
          <img src="images/adorno_corona.png" alt="" class="sidebar-crown" aria-hidden="true">
          <span class="sidebar-label">— Tu Selección —</span>
        </div>

        <?php foreach ([1, 2, 3] as $rank):
          $has_pick  = isset($saved_picks[$rank]);
          $pick_song = $has_pick ? ($songs_by_id[$saved_picks[$rank]] ?? null) : null;
        ?>
          <div class="sidebar-slot" id="sslot-<?= $rank ?>">
            <img src="images/<?= $rank ?>_puesto.png" alt="<?= $rank_labels[$rank] ?>" class="sidebar-slot-placeholder<?= $has_pick ? ' hidden' : '' ?>">
            <img src="images/<?= $rank_badges[$rank] ?>" alt="<?= $rank_labels[$rank] ?>" class="sidebar-badge-img<?= $has_pick ? '' : ' hidden' ?>">
            <div class="sidebar-slot-info<?= $has_pick ? '' : ' hidden' ?>">
              <div class="sidebar-slot-title"><?= ($has_pick && $pick_song) ? htmlspecialchars($pick_song['title']) : '' ?></div>
              <div class="sidebar-slot-artist"><?= ($has_pick && $pick_song) ? htmlspecialchars($pick_song['artist']) : '' ?></div>
            </div>
            <button class="sidebar-remove<?= $has_pick ? '' : ' hidden' ?>" data-rank="<?= $rank ?>" aria-label="Quitar <?= $rank_labels[$rank] ?>">
              <img src="images/papelera_icono.png" alt="" aria-hidden="true">
            </button>
          </div>
        <?php endforeach; ?>

        <button class="sidebar-vote-btn" id="save-btn" disabled>Enviar voto</button>
        <span id="save-status"></span>
        <input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="giveaway-note hidden" id="giveaway-note">
          &#9917; ¿Quieres ganar una camiseta de la selección española? <a href="sorteo.php">Participa en el sorteo</a>.
        </div>
      </aside>

      <!-- Mobile bottom bar -->
      <div class="vote-bottom-bar" id="vote-bottom-bar">
        <button class="mobile-vote-btn" id="save-btn-mobile" disabled>Enviar voto</button>
        <div class="vote-bottom-note">
          <img src="images/candado_icono.png" alt="" aria-hidden="true">
          <span>Cierra el <?= $close_day_num ?> de <?= $close_month_name ?></span>
        </div>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div><!-- /.vote-page-wrap -->
</div><!-- /.vote-outer -->

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
// Song preview modal
(function() {
  var modal    = document.getElementById('preview-modal');
  var iframe   = document.getElementById('preview-iframe');
  var closeBtn = document.getElementById('preview-close');
  var backdrop = modal ? modal.querySelector('.modal-backdrop') : null;
  var opener   = null;

  document.querySelectorAll('.preview-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      opener = btn;
      iframe.src = 'https://open.spotify.com/embed/track/' + btn.dataset.track + '?utm_source=generator&theme=0';
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
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeModal(); return; }
    if (e.key === 'Tab' && modal && !modal.hidden) {
      var focusable = [closeBtn, iframe].filter(Boolean);
      var first = focusable[0], last = focusable[focusable.length - 1];
      if (e.shiftKey) { if (document.activeElement === first) { e.preventDefault(); last.focus(); } }
      else            { if (document.activeElement === last)  { e.preventDefault(); first.focus(); } }
    }
  });
})();
</script>

<?php if ($voting_open): ?>
<script src="js/vote.js"></script>
<?php endif; ?>

<?php render_footer(); ?>
