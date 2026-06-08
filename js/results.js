/**
 * results.js — Live bar chart + podium for King of Songs 2026
 * Polls /api/results.php every 8 seconds while voting is open.
 * After close, renders a static podium.
 */

(function () {
  'use strict';

  const POLL_INTERVAL = 8000; // ms
  const API_URL       = '/api/results.php';

  // These globals are set inline by index.php
  // VOTING_OPEN  (bool)
  // VOTING_CLOSED (bool)

  const chartWrap   = document.getElementById('chart-wrap');
  const barChart    = document.getElementById('bar-chart');
  const chartLoading= document.getElementById('chart-loading');
  const podiumWrap  = document.getElementById('podium-wrap');

  let pollTimer = null;

  /* ---- Fetch & dispatch ---- */
  async function fetchResults() {
    try {
      const resp = await fetch(API_URL, { cache: 'no-store' });
      if (!resp.ok) throw new Error('HTTP ' + resp.status);
      const data = await resp.json();
      if (chartLoading) chartLoading.style.display = 'none';
      renderChart(data.songs || []);
      if (typeof VOTING_CLOSED !== 'undefined' && VOTING_CLOSED) {
        renderPodium(data.songs || []);
        stopPolling();
      }
    } catch (err) {
      console.warn('Results fetch error:', err);
      if (chartLoading) {
        chartLoading.style.display = 'none';
        if (barChart) barChart.innerHTML = '<p style="color:var(--muted);text-align:center;padding:1rem">No se pudieron cargar los resultados. Reintentando…</p>';
      }
    }
  }

  /* ---- Bar chart ---- */
  function renderChart(songs) {
    if (!barChart) return;

    const medals = ['🥇', '🥈', '🥉'];

    songs.forEach((song, idx) => {
      const rowId   = 'bar-row-' + song.id;
      let row       = document.getElementById(rowId);
      const isNew   = !row;

      if (isNew) {
        row = document.createElement('div');
        row.id = rowId;
        row.className = 'bar-row';
        row.innerHTML = `
          <div class="bar-label">
            <span class="bar-song-title"></span>
            <small class="bar-song-artist"></small>
          </div>
          <div class="bar-track" aria-label="score bar">
            <div class="bar-fill" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="bar-pts"></div>`;
        barChart.appendChild(row);
      }

      // Update rank class
      row.classList.remove('rank-1', 'rank-2', 'rank-3');
      if (idx < 3) row.classList.add('rank-' + (idx + 1));

      row.querySelector('.bar-song-title').textContent = song.title;
      row.querySelector('.bar-song-artist').textContent = song.artist;

      const fill = row.querySelector('.bar-fill');
      const pct  = parseFloat(song.score_pct) || 0;
      // Use rAF so CSS transition plays after insertion
      if (isNew) {
        requestAnimationFrame(() => requestAnimationFrame(() => {
          fill.style.width = pct + '%';
          fill.setAttribute('aria-valuenow', pct);
        }));
      } else {
        fill.style.width = pct + '%';
        fill.setAttribute('aria-valuenow', pct);
      }

      const ptsEl = row.querySelector('.bar-pts');
      const medal = idx < 3 ? `<span class="bar-medal">${medals[idx]}</span>` : '';
      ptsEl.innerHTML = song.points + medal;

      // Move to correct position (keep DOM order in sync with sorted data)
      if (barChart.children[idx] !== row) {
        barChart.insertBefore(row, barChart.children[idx] || null);
      }
    });

    // Remove rows for songs no longer in the list
    const ids = new Set(songs.map(s => 'bar-row-' + s.id));
    Array.from(barChart.children).forEach(el => {
      if (!ids.has(el.id)) el.remove();
    });
  }

  /* ---- Podium (shown after voting closes) ---- */
  function renderPodium(songs) {
    if (!podiumWrap) return;
    const top3 = songs.slice(0, 3);
    if (top3.length === 0) return;

    // Podium order: 2nd, 1st, 3rd
    const order = [
      { ...top3[1], pClass: 'p2', pNum: '🥈', height: '2nd' },
      { ...top3[0], pClass: 'p1', pNum: '🥇', height: '1st' },
      top3[2] ? { ...top3[2], pClass: 'p3', pNum: '🥉', height: '3rd' } : null,
    ].filter(Boolean);

    podiumWrap.innerHTML = order.map(item => {
      const coverSrc = item.cover_url
        ? `<img src="${escHtml(item.cover_url)}" alt="${escHtml(item.title)} cover" class="podium-cover">`
        : `<div class="podium-cover" style="background:var(--bg3);display:flex;align-items:center;justify-content:center;font-size:2.5rem">🎵</div>`;
      return `
        <div class="podium-item ${item.pClass}">
          <div class="podium-rank-label">${item.pNum}</div>
          ${coverSrc}
          <div class="podium-title">${escHtml(item.title)}</div>
          <div class="podium-artist">${escHtml(item.artist)}</div>
          <div class="podium-pts">${item.points} pts</div>
          <div class="podium-block"></div>
        </div>`;
    }).join('');
  }

  /* ---- Polling control ---- */
  function startPolling() {
    fetchResults();
    if (typeof VOTING_OPEN !== 'undefined' && VOTING_OPEN) {
      pollTimer = setInterval(fetchResults, POLL_INTERVAL);
    }
  }

  function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  /* ---- Util ---- */
  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  /* ---- Boot ---- */
  if (barChart || podiumWrap) {
    startPolling();
  }
})();
