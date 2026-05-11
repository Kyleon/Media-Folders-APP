/**
 * Portal de cliente — vanilla JS, sin dependencias.
 * Mountea sobre #yzmf-cp con data-attrs token, api, title, client, message.
 */
(function () {
  'use strict';

  const root = document.getElementById('yzmf-cp');
  if (!root) return;

  const token   = root.dataset.token;
  const apiBase = root.dataset.api;       // .../wp-json/yzmf/v1/cp/
  const title   = root.dataset.title;
  const client  = root.dataset.client;
  const message = root.dataset.message;

  const state = {
    gallery: null,
    images: [],
    favs: new Set(),
    lightboxIdx: -1,
  };

  // ── API helpers ──────────────────────────────────────────────────
  const SESSION_KEY = 'yzmf_cp_session_' + token;

  function getSession() {
    try { return sessionStorage.getItem(SESSION_KEY) || ''; } catch (e) { return ''; }
  }
  function setSession(value) {
    try { sessionStorage.setItem(SESSION_KEY, value); } catch (e) {}
  }
  function clearSession() {
    try { sessionStorage.removeItem(SESSION_KEY); } catch (e) {}
  }

  async function api(path, opts = {}) {
    const session = getSession();
    const headers = {
      'Content-Type': 'application/json',
      ...(opts.headers || {}),
    };
    if (session) headers['X-YZMF-CP-Session'] = session;

    const res = await fetch(apiBase + token + path, {
      credentials: 'include',
      ...opts,
      headers, // headers DESPUÉS del spread para que ganen
    });
    let data = null;
    try { data = await res.json(); } catch (e) {}
    if (!res.ok) {
      const msg = (data && (data.message || data.code)) || ('HTTP ' + res.status);
      throw Object.assign(new Error(msg), { status: res.status, data });
    }
    return data;
  }

  // ── Render: shell ────────────────────────────────────────────────
  function renderShell() {
    root.innerHTML = `
      <header class="cp-header">
        <div>
          <h1>${escape(title)}</h1>
          ${client ? `<span class="sub">Para ${escape(client)}</span>` : ''}
        </div>
        <div class="cp-actions" id="cp-actions"></div>
      </header>
      ${message ? `<div class="cp-message">${message}</div>` : ''}
      <div class="cp-stats" id="cp-stats"></div>
      <div id="cp-content"></div>
    `;
  }

  // ── Login screen ─────────────────────────────────────────────────
  function renderLogin(err) {
    document.getElementById('cp-content').innerHTML = `
      <div class="cp-login">
        <h2>🔒 Galería protegida</h2>
        <p>Introduce la contraseña que te ha facilitado el fotógrafo.</p>
        <form id="cp-login-form">
          <input type="password" id="cp-pwd" placeholder="Contraseña" autofocus required />
          <button type="submit" id="cp-login-btn" class="cp-btn pri" style="width:100%">Entrar</button>
          <p class="err" id="cp-login-err">${err ? escape(err) : ''}</p>
        </form>
      </div>
    `;
    var form = document.getElementById('cp-login-form');
    var btn  = document.getElementById('cp-login-btn');
    var errEl = document.getElementById('cp-login-err');
    var input = document.getElementById('cp-pwd');

    function showError(msg) { errEl.textContent = msg || ''; }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      var pwd = input.value;
      if (!pwd) { showError('Introduce la contraseña'); return; }

      btn.disabled = true;
      btn.textContent = 'Comprobando…';
      showError('');

      try {
        var resp = await api('/login', { method: 'POST', body: JSON.stringify({ password: pwd }) });
        if (resp && resp.session) setSession(resp.session);
        await loadAll();
      } catch (err) {
        btn.disabled = false;
        btn.textContent = 'Entrar';
        if (err.status === 401) showError('Contraseña incorrecta');
        else if (err.status === 410) showError('La galería ha expirado');
        else if (err.status === 404) showError('Galería no encontrada');
        else showError(err.message || 'Error al iniciar sesión');
        try { console.error('[YZMF Portal]', err); } catch (e) {}
      }
    });
  }

  // ── Grid de imágenes ─────────────────────────────────────────────
  function renderGrid() {
    const stats = document.getElementById('cp-stats');
    const favCount = state.images.filter(i => state.favs.has(i.id)).length;
    stats.innerHTML = `${state.images.length} imágenes · <strong>${favCount} favoritas</strong>`;

    const content = document.getElementById('cp-content');
    if (!state.images.length) {
      content.innerHTML = `<div class="cp-loader">Esta galería aún no tiene imágenes.</div>`;
      return;
    }
    content.innerHTML = `
      <div class="cp-grid">
        ${state.images.map((img, idx) => `
          <div class="cp-card" data-idx="${idx}">
            <img src="${img.thumb}" alt="${escape(img.alt || img.title)}" loading="lazy" />
            <div class="cp-fav ${state.favs.has(img.id) ? 'on' : ''}" data-id="${img.id}" title="Favorita">
              ${state.favs.has(img.id) ? '★' : '☆'}
            </div>
          </div>
        `).join('')}
      </div>
    `;

    // Eventos delegados
    content.querySelectorAll('.cp-card').forEach(el => {
      el.addEventListener('click', (e) => {
        if (e.target.closest('.cp-fav')) return;
        openLightbox(parseInt(el.dataset.idx, 10));
      });
    });
    content.querySelectorAll('.cp-fav').forEach(el => {
      el.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleFav(parseInt(el.dataset.id, 10), el);
      });
    });

    // Acciones header
    const actions = document.getElementById('cp-actions');
    actions.innerHTML = `
      ${state.gallery.allow_download ? `<button class="cp-btn" id="cp-download">⬇ Descargar favoritas</button>` : ''}
      <button class="cp-btn ghost" id="cp-share">🔗 Copiar enlace</button>
    `;
    if (state.gallery.allow_download) {
      document.getElementById('cp-download').addEventListener('click', downloadFavs);
    }
    document.getElementById('cp-share').addEventListener('click', () => {
      navigator.clipboard.writeText(location.href).then(() => {
        const btn = document.getElementById('cp-share');
        btn.textContent = '✓ Copiado';
        setTimeout(() => btn.innerHTML = '🔗 Copiar enlace', 2000);
      });
    });
  }

  // ── Toggle favorita ──────────────────────────────────────────────
  async function toggleFav(attId, btn) {
    const wasOn = state.favs.has(attId);
    const on = !wasOn;
    // Optimista
    if (on) state.favs.add(attId); else state.favs.delete(attId);
    btn.classList.toggle('on', on);
    btn.textContent = on ? '★' : '☆';
    document.getElementById('cp-stats').innerHTML =
      `${state.images.length} imágenes · <strong>${state.favs.size} favoritas</strong>`;

    try {
      await api('/favorite', { method: 'POST', body: JSON.stringify({ att_id: attId, on }) });
    } catch (err) {
      // Revertir
      if (on) state.favs.delete(attId); else state.favs.add(attId);
      btn.classList.toggle('on', !on);
      btn.textContent = !on ? '★' : '☆';
      alert(err.message);
    }
  }

  // ── Lightbox ─────────────────────────────────────────────────────
  function openLightbox(idx) {
    state.lightboxIdx = idx;
    const img = state.images[idx];
    const lb = document.createElement('div');
    lb.className = 'cp-lightbox';
    lb.innerHTML = `
      <div class="cp-lb-img-wrap">
        <button class="cp-lb-close" id="lb-close">✕</button>
        <button class="cp-lb-nav prev" id="lb-prev">‹</button>
        <img class="cp-lb-img" id="lb-img" src="${img.full || img.url || img.medium}" alt="" />
        <button class="cp-lb-nav next" id="lb-next">›</button>
      </div>
      <div class="cp-lb-bar">
        <span class="counter" id="lb-counter">${idx + 1} / ${state.images.length}</span>
        <button class="cp-btn" id="lb-fav">${state.favs.has(img.id) ? '★ Favorita' : '☆ Marcar favorita'}</button>
        <span class="spacer"></span>
        ${state.gallery.allow_comments ? `<button class="cp-btn ghost" id="lb-toggle-comments">💬 Comentar</button>` : ''}
        ${state.gallery.allow_download ? `<a class="cp-btn" href="${img.url}" download>⬇ Descargar</a>` : ''}
      </div>
      <div class="cp-comments" id="lb-comments" style="display:none"></div>
    `;
    document.body.appendChild(lb);

    document.getElementById('lb-close').onclick = closeLightbox;
    document.getElementById('lb-prev').onclick  = () => goLb(-1);
    document.getElementById('lb-next').onclick  = () => goLb(1);
    document.getElementById('lb-fav').onclick   = () => {
      const id = state.images[state.lightboxIdx].id;
      const btn = document.querySelector(`.cp-fav[data-id="${id}"]`);
      if (btn) btn.click();
      else toggleFav(id, document.createElement('div'));
      document.getElementById('lb-fav').textContent = state.favs.has(id) ? '★ Favorita' : '☆ Marcar favorita';
    };
    if (state.gallery.allow_comments) {
      document.getElementById('lb-toggle-comments').onclick = toggleComments;
    }

    document.addEventListener('keydown', onLbKey);
    lb.dataset.bound = '1';
  }

  function onLbKey(e) {
    if (e.key === 'Escape')     closeLightbox();
    if (e.key === 'ArrowLeft')  goLb(-1);
    if (e.key === 'ArrowRight') goLb(1);
  }

  function goLb(delta) {
    const newIdx = (state.lightboxIdx + delta + state.images.length) % state.images.length;
    state.lightboxIdx = newIdx;
    const img = state.images[newIdx];
    document.getElementById('lb-img').src = img.full || img.url || img.medium;
    document.getElementById('lb-counter').textContent = `${newIdx + 1} / ${state.images.length}`;
    const fav = document.getElementById('lb-fav');
    if (fav) fav.textContent = state.favs.has(img.id) ? '★ Favorita' : '☆ Marcar favorita';
    document.getElementById('lb-comments').style.display = 'none';
  }

  function closeLightbox() {
    const lb = document.querySelector('.cp-lightbox');
    if (lb) lb.remove();
    document.removeEventListener('keydown', onLbKey);
    state.lightboxIdx = -1;
    // Refrescar grid (favoritas pueden haber cambiado)
    renderGrid();
  }

  function toggleComments() {
    const panel = document.getElementById('lb-comments');
    if (panel.style.display === 'block') {
      panel.style.display = 'none';
      return;
    }
    panel.style.display = 'block';
    const id = state.images[state.lightboxIdx].id;
    panel.innerHTML = `
      <h3>Comentarios sobre esta imagen</h3>
      <div id="cp-comments-list"><em style="color:var(--text-mute);font-size:12px">Cargando…</em></div>
      <form class="cp-comment-form" id="cp-comment-form">
        <textarea name="text" placeholder="Escribe un comentario sobre esta imagen…" required></textarea>
        <button type="submit" class="cp-btn pri">Enviar</button>
      </form>
    `;
    document.getElementById('cp-comment-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const text = e.target.text.value.trim();
      if (!text) return;
      try {
        await api('/comment', {
          method: 'POST',
          body: JSON.stringify({ att_id: id, text, name: client || '' }),
        });
        e.target.text.value = '';
        // Por ahora solo confirmamos al usuario; un endpoint /comments/{att_id}
        // podría devolver la lista para mostrarla.
        document.getElementById('cp-comments-list').innerHTML =
          '<p style="color:var(--ok);font-size:12px">✓ Comentario enviado al fotógrafo.</p>';
      } catch (err) { alert(err.message); }
    });
  }

  // ── Descargar favoritas (zip simple: descarga individual + nombre) ─
  function downloadFavs() {
    const favs = state.images.filter(i => state.favs.has(i.id));
    if (!favs.length) { alert('Marca al menos una favorita primero.'); return; }
    if (!confirm(`Descargar ${favs.length} imágenes? Se abrirán en pestañas.`)) return;
    favs.forEach((img, i) => {
      setTimeout(() => {
        const a = document.createElement('a');
        a.href = img.url;
        a.download = '';
        a.target = '_blank';
        document.body.appendChild(a);
        a.click();
        a.remove();
      }, i * 200);
    });
  }

  // ── Carga inicial ────────────────────────────────────────────────
  async function loadAll() {
    document.getElementById('cp-content').innerHTML = `<div class="cp-loader">Cargando galería…</div>`;
    try {
      state.gallery = await api('');
      if (state.gallery.locked) {
        // Sesión inválida o no presente: limpiamos por si quedó basura
        clearSession();
        renderLogin();
        return;
      }
      const imgs = await api('/images');
      state.images = imgs;
      state.favs = new Set(imgs.filter(i => i.favorited).map(i => i.id));
      renderGrid();
    } catch (err) {
      if (err.status === 401) { clearSession(); renderLogin(); return; }
      document.getElementById('cp-content').innerHTML =
        `<div class="cp-loader">Error: ${escape(err.message)}</div>`;
      try { console.error('[YZMF Portal]', err); } catch (e) {}
    }
  }

  // ── Util ─────────────────────────────────────────────────────────
  function escape(s) {
    return String(s || '').replace(/[&<>"']/g, c =>
      ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  // ── Init ─────────────────────────────────────────────────────────
  renderShell();
  loadAll();
})();
