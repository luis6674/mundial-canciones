/**
 * results.js — Live bar chart + mini-podium for Mundial de Canciones 2026
 * Polls /api/results.php every 8 seconds while voting is open.
 */

(function () {
  'use strict';

  const POLL_INTERVAL = 8000;
  const API_URL       = './api/results.php';

  const chartWrap    = document.getElementById('chart-wrap');
  const barChart     = document.getElementById('bar-chart');
  const chartLoading = document.getElementById('chart-loading');
  const podiumWrap   = document.getElementById('podium-wrap');
  const mpwItems     = document.getElementById('mpw-items');

  let pollTimer = null;

  /* ---- Fetch & dispatch ---- */
  async function fetchResults() {
    try {
      const resp = await fetch(API_URL, { cache: 'no-store' });
      if (!resp.ok) throw new Error('HTTP ' + resp.status);
      const data = await resp.json();
      if (chartLoading) chartLoading.style.display = 'none';
      const songs = data.songs || [];
      renderChart(songs);
      renderMiniPodium(songs);
      if (typeof VOTING_CLOSED !== 'undefined' && VOTING_CLOSED) {
        renderPodium(songs);
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

  /* ---- Mini-podium widget (hero section) ---- */
  function renderMiniPodium(songs) {
    if (!mpwItems) return;
    const top3 = songs.slice(0, 3);
    if (top3.length === 0) return;

    // Render in podium order: 2nd, 1st, 3rd (middle column is widest)
    const pClasses = ['mpw-p2', 'mpw-p1', 'mpw-p3'];
    const order    = [top3[1], top3[0], top3[2]].map((song, i) => ({ song, pClass: pClasses[i] }))
                       .filter(o => o.song);

    const rankBadges = { 'mpw-p1': 'oro_badge.png', 'mpw-p2': 'Plata_badge.png', 'mpw-p3': 'Bronce_badge.png' };
    const rankNums   = { 'mpw-p1': 1, 'mpw-p2': 2, 'mpw-p3': 3 };

    mpwItems.innerHTML = order.map(({ song, pClass }) => {
      const pct   = parseFloat(song.vote_pct) || 0;
      const rank  = rankNums[pClass];
      const badge = rankBadges[pClass];
      const cover = song.cover_url
        ? `<img src="${escHtml(song.cover_url)}" alt="${escHtml(song.title)}" class="mpw-cover">`
        : `<div class="mpw-cover" style="display:flex;align-items:center;justify-content:center;font-size:1.5rem;background:rgba(255,255,255,0.05)">🎵</div>`;
      return `
        <div class="mpw-item ${pClass}">
          <div class="mpw-cover-wrap">
            ${cover}
            <img src="images/${badge}" alt="${rank}º puesto" class="mpw-badge">
          </div>
          <div class="mpw-song-title">${escHtml(song.title)}</div>
          <div class="mpw-song-artist">${escHtml(song.artist)}</div>
          <div class="mpw-pct">${Math.round(pct)}%</div>
        </div>`;
    }).join('');
  }

  /* ---- Bar chart ---- */
  function renderChart(songs) {
    if (!barChart) return;

    songs.forEach((song, idx) => {
      const rowId = 'bar-row-' + song.id;
      let row     = document.getElementById(rowId);
      const isNew = !row;

      if (isNew) {
        row = document.createElement('div');
        row.id = rowId;
        row.className = 'bar-row';
        row.innerHTML = `
          <div class="bar-rank-num"></div>
          <div class="bar-label">
            <span class="bar-song-title"></span>
            <span class="bar-song-artist"></span>
          </div>
          <div class="bar-track">
            <div class="bar-fill" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label=""></div>
          </div>
          <div class="bar-pts"></div>`;
        barChart.appendChild(row);
      }

      row.classList.remove('rank-1', 'rank-2', 'rank-3');
      if (idx < 3) row.classList.add('rank-' + (idx + 1));

      row.querySelector('.bar-rank-num').textContent  = idx + 1;
      row.querySelector('.bar-song-title').textContent  = song.title;
      row.querySelector('.bar-song-artist').textContent = song.artist;

      const fill = row.querySelector('.bar-fill');
      const pct  = parseFloat(song.score_pct) || 0;
      fill.setAttribute('aria-label', song.title + ': ' + Math.round(pct) + '%');

      if (isNew) {
        requestAnimationFrame(() => requestAnimationFrame(() => {
          fill.style.width = pct + '%';
          fill.setAttribute('aria-valuenow', pct);
        }));
      } else {
        fill.style.width = pct + '%';
        fill.setAttribute('aria-valuenow', pct);
      }

      const votePct = parseFloat(song.vote_pct) || 0;
      const ptsEl   = row.querySelector('.bar-pts');
      ptsEl.textContent = votePct.toFixed(1) + '%';

      if (barChart.children[idx] !== row) {
        barChart.insertBefore(row, barChart.children[idx] || null);
      }
    });

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

    const order = [
      top3[1] ? { ...top3[1], pClass: 'p2', pNum: '🥈' } : null,
      { ...top3[0], pClass: 'p1', pNum: '🥇' },
      top3[2] ? { ...top3[2], pClass: 'p3', pNum: '🥉' } : null,
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
  if (barChart || podiumWrap || mpwItems) {
    startPolling();
  }
})();
