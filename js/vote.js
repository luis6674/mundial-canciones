/**
 * vote.js — Voting UI for King of Songs 2026
 * Handles pick 1/2/3, deselection, save via POST /api/vote.php
 * and loading current votes from GET /api/my-votes.php
 */

(function () {
  'use strict';

  const RANKS = [1, 2, 3];
  const RANK_LABELS  = { 1: '1st',  2: '2nd',  3: '3rd' };
  const RANK_MEDALS  = { 1: '🥇', 2: '🥈', 3: '🥉' };
  const RANK_CLASSES = { 1: 'picked-1', 2: 'picked-2', 3: 'picked-3' };
  const BADGE_CLASSES= { 1: 'badge-1',  2: 'badge-2',  3: 'badge-3' };

  // picks[rank] = songId | null
  const picks = { 1: null, 2: null, 3: null };

  const grid      = document.getElementById('vote-grid');
  const saveBar   = document.getElementById('save-bar-inner');
  const saveBtn   = document.getElementById('save-btn');
  const saveStatus= document.getElementById('save-status');
  const csrfInput = document.getElementById('csrf-token');

  /* ---- Init: load saved votes from server ---- */
  async function loadMyVotes() {
    try {
      const resp = await fetch('/api/my-votes.php', { cache: 'no-store' });
      if (!resp.ok) return;
      const data = await resp.json();
      if (data.votes && Array.isArray(data.votes)) {
        data.votes.forEach(v => {
          picks[v.rank] = v.song_id;
        });
        applyPicksToUI();
      }
    } catch (err) {
      console.warn('Could not load existing votes:', err);
    }
  }

  /* ---- Apply current picks state to all cards & slots ---- */
  function applyPicksToUI() {
    if (!grid) return;

    // Reset all cards
    grid.querySelectorAll('.song-card').forEach(card => {
      RANKS.forEach(r => card.classList.remove(RANK_CLASSES[r]));
      const badge = card.querySelector('.rank-badge');
      if (badge) { badge.className = 'rank-badge hidden'; badge.textContent = ''; }
    });

    // Apply current picks
    RANKS.forEach(rank => {
      const sid = picks[rank];
      if (!sid) return;
      const card = grid.querySelector(`.song-card[data-song-id="${sid}"]`);
      if (!card) return;
      card.classList.add(RANK_CLASSES[rank]);
      const badge = card.querySelector('.rank-badge');
      if (badge) {
        badge.className = `rank-badge ${BADGE_CLASSES[rank]}`;
        badge.textContent = rank;
      }
    });

    // Update slots
    RANKS.forEach(rank => {
      updateSlot(rank);
    });

    // Show/hide save bar
    updateSaveBar();
  }

  /* ---- Update a single slot strip ---- */
  function updateSlot(rank) {
    const slot = document.getElementById('slot-' + rank);
    if (!slot) return;
    const sid  = picks[rank];

    RANKS.forEach(r => slot.classList.remove('filled-' + r));

    if (sid) {
      slot.classList.add('filled-' + rank);
      const card   = grid && grid.querySelector(`.song-card[data-song-id="${sid}"]`);
      const title  = card ? card.dataset.title  : '—';
      const artist = card ? card.dataset.artist : '';
      slot.querySelector('.slot-song-title').textContent  = title;
      slot.querySelector('.slot-song-artist').textContent = artist;
      slot.querySelector('.slot-empty-hint').style.display  = 'none';
      slot.querySelector('.slot-song-title').style.display  = '';
      slot.querySelector('.slot-song-artist').style.display = '';
      const clearBtn = slot.querySelector('.slot-clear');
      if (clearBtn) clearBtn.style.display = '';
    } else {
      slot.querySelector('.slot-empty-hint').style.display  = '';
      slot.querySelector('.slot-song-title').style.display  = 'none';
      slot.querySelector('.slot-song-artist').style.display = 'none';
      const clearBtn = slot.querySelector('.slot-clear');
      if (clearBtn) clearBtn.style.display = 'none';
    }
  }

  /* ---- Show/hide save bar ---- */
  function updateSaveBar() {
    if (!saveBar) return;
    const allPicked = RANKS.every(r => picks[r] !== null);
    if (allPicked) {
      saveBar.classList.remove('hidden');
    } else {
      saveBar.classList.add('hidden');
    }
  }

  /* ---- Card click ---- */
  function onCardClick(card) {
    const sid = parseInt(card.dataset.songId, 10);

    // If the card is already picked at some rank, deselect it
    const existingRank = RANKS.find(r => picks[r] === sid);
    if (existingRank !== undefined) {
      deselect(existingRank);
      return;
    }

    // Find the lowest empty rank
    const emptyRank = RANKS.find(r => picks[r] === null);
    if (emptyRank === undefined) {
      // All 3 slots full — flash a hint
      flashHint();
      return;
    }

    picks[emptyRank] = sid;
    applyPicksToUI();
    clearStatus();
  }

  function deselect(rank) {
    picks[rank] = null;
    // Compact: shift higher ranks down
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
    if (!saveBtn) return;
    if (RANKS.some(r => picks[r] === null)) {
      setStatus('Please select all 3 picks first.', 'error');
      return;
    }

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';
    setStatus('', '');

    const votes = RANKS.map(r => ({ song_id: picks[r], rank: r }));
    const csrf  = csrfInput ? csrfInput.value : '';

    try {
      const resp = await fetch('/api/vote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ votes, csrf }),
      });
      const data = await resp.json();
      if (data.success) {
        setStatus('✓ Votes saved!', 'success');
      } else {
        setStatus(data.error || 'Could not save votes.', 'error');
      }
    } catch (err) {
      setStatus('Network error. Please try again.', 'error');
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save Votes';
    }
  }

  function setStatus(msg, type) {
    if (!saveStatus) return;
    saveStatus.textContent = msg;
    saveStatus.className = type;
  }
  function clearStatus() { setStatus('', ''); }

  /* ---- Wire up events ---- */
  function init() {
    if (!grid) return;

    // Card clicks
    grid.querySelectorAll('.song-card.selectable').forEach(card => {
      card.addEventListener('click', () => onCardClick(card));
    });

    // Slot clear buttons
    RANKS.forEach(rank => {
      const slot = document.getElementById('slot-' + rank);
      if (!slot) return;
      const clearBtn = slot.querySelector('.slot-clear');
      if (clearBtn) clearBtn.addEventListener('click', e => { e.stopPropagation(); deselect(rank); });
    });

    // Save button
    if (saveBtn) saveBtn.addEventListener('click', saveVotes);

    // Load existing votes, then apply
    loadMyVotes();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
