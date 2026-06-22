/**
 * vote.js — Voting UI for Mundial de Canciones 2026
 */

(function () {
  'use strict';

  const RANKS = [1, 2, 3];

  // picks[rank] = songId | null
  const picks = { 1: null, 2: null, 3: null };

  const grid       = document.getElementById('vote-grid');
  const saveBtn    = document.getElementById('save-btn');
  const saveBtnMob = document.getElementById('save-btn-mobile');
  const saveStatus = document.getElementById('save-status');
  const csrfInput  = document.getElementById('csrf-token');

  /* ---- Init: load saved votes from server ---- */
  async function loadMyVotes() {
    try {
      const resp = await fetch('./api/my-votes.php', { cache: 'no-store' });
      if (!resp.ok) return;
      const data = await resp.json();
      if (data.votes && Array.isArray(data.votes)) {
        data.votes.forEach(v => { picks[v.rank] = v.song_id; });
        applyPicksToUI();
      }
    } catch (err) {
      console.warn('Could not load existing votes:', err);
    }
  }

  /* ---- Apply current picks to all cards and slots ---- */
  function applyPicksToUI() {
    if (!grid) return;

    // Reset all cards
    grid.querySelectorAll('.song-card').forEach(card => {
      const sid    = parseInt(card.dataset.songId, 10);
      const rank   = RANKS.find(r => picks[r] === sid);
      const circle = card.querySelector('.selection-circle');
      const selBtn = card.querySelector('.btn-select');

      card.classList.toggle('selected', rank !== undefined);

      if (circle) {
        if (rank !== undefined) {
          circle.innerHTML = `<img src="images/${rank}_puesto.png" alt="${rank}º puesto" class="selection-badge-img">`;
          circle.classList.add('active');
        } else {
          circle.innerHTML = '';
          circle.classList.remove('active');
        }
      }

      if (selBtn) {
        if (rank !== undefined) {
          selBtn.textContent = 'Quitar';
          selBtn.classList.add('selected-btn');
          selBtn.setAttribute('aria-label', 'Quitar ' + (card.dataset.title || ''));
        } else {
          selBtn.textContent = 'Seleccionar';
          selBtn.classList.remove('selected-btn');
          selBtn.setAttribute('aria-label', 'Seleccionar ' + (card.dataset.title || ''));
        }
      }
    });

    // Update sidebar slots and mobile strip
    RANKS.forEach(rank => {
      updateSidebarSlot(rank);
      updateMobileSlot(rank);
    });

    updateSaveButtons();
  }

  /* ---- Update sidebar slot ---- */
  function updateSidebarSlot(rank) {
    const slot = document.getElementById('sslot-' + rank);
    if (!slot) return;
    const sid       = picks[rank];
    const card      = sid && grid ? grid.querySelector(`.song-card[data-song-id="${sid}"]`) : null;
    const title     = card ? card.dataset.title  : '—';
    const artist    = card ? card.dataset.artist : '';
    const titleEl   = slot.querySelector('.sidebar-slot-title');
    const artistEl  = slot.querySelector('.sidebar-slot-artist');
    const emptyEl   = slot.querySelector('.sidebar-slot-empty');
    const removeBtn = slot.querySelector('.sidebar-remove');

    if (titleEl)  titleEl.textContent  = title;
    if (artistEl) { artistEl.textContent = artist; artistEl.style.display = sid ? '' : 'none'; }
    if (emptyEl)  emptyEl.style.display = sid ? 'none' : '';
    if (removeBtn) removeBtn.classList.toggle('hidden', !sid);
  }

  /* ---- Update mobile strip slot ---- */
  function updateMobileSlot(rank) {
    const slot = document.getElementById('mslot-' + rank);
    if (!slot) return;
    const sid       = picks[rank];
    const card      = sid && grid ? grid.querySelector(`.song-card[data-song-id="${sid}"]`) : null;
    const title     = card ? card.dataset.title  : '';
    const artist    = card ? card.dataset.artist : '';
    const infoEl    = slot.querySelector('.mstrip-song-info');
    const titleEl   = slot.querySelector('.mstrip-slot-title');
    const artistEl  = slot.querySelector('.mstrip-slot-artist');
    const dotsEl    = slot.querySelector('.mstrip-dots');
    const removeBtn = slot.querySelector('.mstrip-remove');

    if (titleEl)  titleEl.textContent  = title;
    if (artistEl) artistEl.textContent = artist;
    if (infoEl)   infoEl.style.display = sid ? '' : 'none';
    if (dotsEl)   dotsEl.style.display = sid ? 'none' : '';
    slot.classList.toggle('empty', !sid);
    if (removeBtn) removeBtn.classList.toggle('hidden', !sid);
  }

  /* ---- Enable/disable save buttons ---- */
  function updateSaveButtons() {
    const allPicked = RANKS.every(r => picks[r] !== null);
    if (saveBtn)    saveBtn.disabled    = !allPicked;
    if (saveBtnMob) saveBtnMob.disabled = !allPicked;
    if (!allPicked) {
      const note = document.getElementById('giveaway-note');
      if (note) note.classList.add('hidden');
    }
  }

  /* ---- Select / deselect a song ---- */
  function toggleSong(sid) {
    const existingRank = RANKS.find(r => picks[r] === sid);
    if (existingRank !== undefined) {
      deselect(existingRank);
      return;
    }

    const emptyRank = RANKS.find(r => picks[r] === null);
    if (emptyRank === undefined) {
      flashHint();
      return;
    }

    picks[emptyRank] = sid;
    applyPicksToUI();
    clearStatus();
  }

  function deselect(rank) {
    picks[rank] = null;
    // Shift higher ranks down
    RANKS.forEach(r => {
      if (r > rank && picks[r] !== null) {
        picks[r - 1] = picks[r];
        picks[r]     = null;
      }
    });
    applyPicksToUI();
    clearStatus();
  }

  function flashHint() {
    if (!grid) return;
    grid.classList.add('shake');
    setTimeout(() => grid.classList.remove('shake'), 600);
  }

  /* ---- Save votes ---- */
  async function saveVotes() {
    if (RANKS.some(r => picks[r] === null)) {
      setStatus('Selecciona tus 3 canciones favoritas primero.', 'error');
      return;
    }

    [saveBtn, saveBtnMob].forEach(btn => {
      if (btn) { btn.disabled = true; btn.textContent = 'Guardando…'; }
    });
    setStatus('', '');

    const votes = RANKS.map(r => ({ song_id: picks[r], rank: r }));
    const csrf  = csrfInput ? csrfInput.value : '';

    try {
      const resp = await fetch('./api/vote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ votes, csrf }),
      });
      const data = await resp.json();
      if (data.success) {
        setStatus('✓ ¡Votos guardados!', 'success');
        const note = document.getElementById('giveaway-note');
        if (note) note.classList.remove('hidden');
      } else {
        setStatus(data.error || 'No se pudieron guardar los votos.', 'error');
      }
    } catch (err) {
      setStatus('Error de red. Por favor, intenta de nuevo.', 'error');
    } finally {
      [saveBtn, saveBtnMob].forEach(btn => {
        if (btn) { btn.disabled = false; btn.textContent = 'Enviar voto'; }
      });
      updateSaveButtons();
    }
  }

  function setStatus(msg, type) {
    if (!saveStatus) return;
    saveStatus.textContent = msg;
    saveStatus.className = 'save-status' + (type ? ' ' + type : '');
  }
  function clearStatus() { setStatus('', ''); }

  /* ---- Wire up events ---- */
  function init() {
    if (!grid) return;

    // Card clicks (whole card toggles selection)
    grid.querySelectorAll('.song-card.selectable').forEach(card => {
      card.addEventListener('click', e => {
        // Ignore clicks on the listen button
        if (e.target.closest('.btn-listen')) return;
        toggleSong(parseInt(card.dataset.songId, 10));
      });
      card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleSong(parseInt(card.dataset.songId, 10));
        }
      });
    });

    // Btn-select buttons
    grid.querySelectorAll('.btn-select').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        toggleSong(parseInt(btn.dataset.songId, 10));
      });
    });

    // Sidebar remove buttons
    RANKS.forEach(rank => {
      const slot = document.getElementById('sslot-' + rank);
      if (!slot) return;
      const removeBtn = slot.querySelector('.sidebar-remove');
      if (removeBtn) removeBtn.addEventListener('click', () => deselect(rank));
    });

    // Mobile strip remove buttons
    RANKS.forEach(rank => {
      const slot = document.getElementById('mslot-' + rank);
      if (!slot) return;
      const removeBtn = slot.querySelector('.mstrip-remove');
      if (removeBtn) removeBtn.addEventListener('click', () => deselect(rank));
    });

    // Save buttons
    if (saveBtn)    saveBtn.addEventListener('click', saveVotes);
    if (saveBtnMob) saveBtnMob.addEventListener('click', saveVotes);

    loadMyVotes();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
