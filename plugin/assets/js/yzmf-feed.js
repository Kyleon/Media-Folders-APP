/**
 * Feed público [yzmf_feed]: visor modal con carrusel, likes y comentarios.
 * Vanilla JS, sin dependencias. Habla con los endpoints públicos de yzmf/v1.
 *
 * Config inyectada por wp_localize_script en window.yzmfFeed:
 *   { rest: 'https://sitio/wp-json/yzmf/v1/', i18n: {...} }
 */
(function () {
	'use strict';

	var CFG = window.yzmfFeed || { rest: '/wp-json/yzmf/v1/', i18n: {} };
	var T = CFG.i18n || {};
	var REST = CFG.rest.replace(/\/?$/, '/');

	/* ─── Identidad anónima para deduplicar likes ─── */
	function clientToken() {
		var k = 'yzmf_feed_token';
		var t = localStorage.getItem(k);
		if (!t) {
			t = (window.crypto && crypto.randomUUID)
				? crypto.randomUUID()
				: String(Date.now()) + Math.random().toString(36).slice(2);
			localStorage.setItem(k, t);
		}
		return t;
	}
	function likedSet() {
		try { return new Set(JSON.parse(localStorage.getItem('yzmf_feed_liked') || '[]')); }
		catch (e) { return new Set(); }
	}
	function saveLiked(set) {
		localStorage.setItem('yzmf_feed_liked', JSON.stringify(Array.from(set)));
	}

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) n.className = cls;
		if (text != null) n.textContent = text;
		return n;
	}

	/* ─── Estado del visor ─── */
	var state = { post: null, index: 0 };
	var modal, carousel, dots, caption, tags, likeBtn, commentsBox, formNote;

	function buildModal() {
		modal = el('div', 'yzmf-feed-modal');
		modal.hidden = true;
		modal.setAttribute('role', 'dialog');
		modal.setAttribute('aria-modal', 'true');

		var dialog = el('div', 'yzmf-feed-dialog');

		var close = el('button', 'yzmf-feed-close', '✕');
		close.setAttribute('aria-label', T.close || 'Cerrar');
		close.addEventListener('click', closeModal);

		carousel = el('div', 'yzmf-feed-carousel');
		var side = el('div', 'yzmf-feed-side');
		var body = el('div', 'yzmf-feed-body');

		caption = el('p', 'yzmf-feed-caption');
		tags = el('div', 'yzmf-feed-tags');
		likeBtn = el('button', 'yzmf-feed-likebtn');
		likeBtn.addEventListener('click', onLike);
		commentsBox = el('div', 'yzmf-feed-comments');

		body.appendChild(caption);
		body.appendChild(tags);
		body.appendChild(likeBtn);
		body.appendChild(commentsBox);

		var form = buildForm();

		side.appendChild(body);
		side.appendChild(form);
		dialog.appendChild(close);
		dialog.appendChild(carousel);
		dialog.appendChild(side);
		modal.appendChild(dialog);

		modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
		document.addEventListener('keydown', function (e) {
			if (modal.hidden) return;
			if (e.key === 'Escape') closeModal();
			else if (e.key === 'ArrowLeft') move(-1);
			else if (e.key === 'ArrowRight') move(1);
		});

		document.body.appendChild(modal);
	}

	function buildForm() {
		var form = el('form', 'yzmf-feed-form');
		var row = el('div', 'row');
		var name = el('input'); name.type = 'text'; name.name = 'author';
		name.placeholder = T.namePh || 'Tu nombre'; name.required = true;
		var email = el('input'); email.type = 'email'; email.name = 'email';
		email.placeholder = T.emailPh || 'Tu email'; email.required = true;
		row.appendChild(name); row.appendChild(email);

		var textarea = el('textarea'); textarea.name = 'content';
		textarea.placeholder = T.commentPh || 'Escribe un comentario…'; textarea.required = true;

		var submit = el('button', null, T.send || 'Enviar'); submit.type = 'submit';
		formNote = el('div', 'yzmf-feed-note'); formNote.hidden = true;

		form.appendChild(row);
		form.appendChild(textarea);
		form.appendChild(submit);
		form.appendChild(formNote);

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			onComment(name.value, email.value, textarea.value, submit, function () {
				textarea.value = '';
			});
		});
		return form;
	}

	/* ─── Render ─── */
	function renderCarousel() {
		carousel.innerHTML = '';
		var photos = state.post.photos || [];
		if (!photos.length) return;
		var p = photos[state.index];
		var img = el('img');
		img.src = p.large || p.full || p.medium || p.url;
		img.alt = p.alt || '';
		carousel.appendChild(img);

		if (photos.length > 1) {
			var prev = el('button', 'yzmf-feed-nav prev', '‹');
			prev.setAttribute('aria-label', T.prev || 'Anterior');
			prev.addEventListener('click', function () { move(-1); });
			var next = el('button', 'yzmf-feed-nav next', '›');
			next.setAttribute('aria-label', T.next || 'Siguiente');
			next.addEventListener('click', function () { move(1); });
			carousel.appendChild(prev);
			carousel.appendChild(next);

			dots = el('div', 'yzmf-feed-dots');
			for (var i = 0; i < photos.length; i++) {
				var d = el('i');
				if (i === state.index) d.className = 'on';
				dots.appendChild(d);
			}
			carousel.appendChild(dots);
		}
	}

	function move(delta) {
		var n = (state.post.photos || []).length;
		if (n < 2) return;
		state.index = (state.index + delta + n) % n;
		renderCarousel();
	}

	function renderMeta() {
		caption.textContent = state.post.caption || '';
		tags.innerHTML = '';
		(state.post.hashtags || []).forEach(function (h) {
			tags.appendChild(el('span', 'yzmf-feed-tag', '#' + h));
		});
		renderLike();
		renderComments(state.post.comments || []);
	}

	function renderLike() {
		var liked = likedSet().has(String(state.post.id));
		likeBtn.className = 'yzmf-feed-likebtn' + (liked ? ' liked' : '');
		likeBtn.innerHTML = '';
		likeBtn.appendChild(el('span', 'heart', liked ? '❤' : '🤍'));
		likeBtn.appendChild(el('span', null, (T.like || 'Me gusta') + ' · ' + (state.post.likes || 0)));
	}

	function renderComments(list) {
		commentsBox.innerHTML = '';
		if (!list.length) {
			commentsBox.appendChild(el('p', 'yzmf-feed-empty', T.noComments || 'Sé el primero en comentar'));
			return;
		}
		list.forEach(function (c) {
			var row = el('div', 'yzmf-feed-comment');
			row.appendChild(el('b', null, c.author || '—'));
			row.appendChild(document.createTextNode(c.content || ''));
			commentsBox.appendChild(row);
		});
	}

	/* ─── Acciones ─── */
	function onLike() {
		var id = state.post.id;
		fetch(REST + 'feed/' + id + '/like', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ token: clientToken() }),
		}).then(function (r) { return r.json(); }).then(function (res) {
			if (typeof res.likes !== 'number') return;
			state.post.likes = res.likes;
			var set = likedSet();
			if (res.liked) set.add(String(id)); else set.delete(String(id));
			saveLiked(set);
			renderLike();
		}).catch(function () {});
	}

	function onComment(author, email, content, submitBtn, done) {
		if (!content.trim()) return;
		submitBtn.disabled = true;
		note('', false, true);
		fetch(REST + 'feed/' + state.post.id + '/comments', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ author: author, email: email, content: content }),
		}).then(function (r) {
			return r.json().then(function (data) { return { ok: r.ok, data: data }; });
		}).then(function (res) {
			submitBtn.disabled = false;
			if (!res.ok) { note(res.data && res.data.message || 'Error', true); return; }
			note(res.data.message || 'Enviado', false);
			done();
			// Si se aprobó al vuelo, refrescamos comentarios.
			if (res.data.approved) reloadComments();
		}).catch(function () {
			submitBtn.disabled = false;
			note('Error de red', true);
		});
	}

	function reloadComments() {
		fetch(REST + 'feed/public/' + state.post.id)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				state.post.comments = data.comments || [];
				renderComments(state.post.comments);
			}).catch(function () {});
	}

	function note(msg, isErr, hide) {
		if (hide) { formNote.hidden = true; return; }
		formNote.hidden = false;
		formNote.textContent = msg;
		formNote.className = 'yzmf-feed-note' + (isErr ? ' err' : '');
	}

	/* ─── Apertura ─── */
	function openPost(id) {
		if (!modal) buildModal();
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
		carousel.innerHTML = '';
		carousel.appendChild(el('div', 'yzmf-feed-empty', T.loading || 'Cargando…'));
		caption.textContent = '';
		tags.innerHTML = '';
		commentsBox.innerHTML = '';

		fetch(REST + 'feed/public/' + id)
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data || !data.id) { closeModal(); return; }
				state.post = data;
				state.index = 0;
				renderCarousel();
				renderMeta();
			}).catch(function () { closeModal(); });
	}

	function closeModal() {
		if (!modal) return;
		modal.hidden = true;
		document.body.style.overflow = '';
		state.post = null;
	}

	/* ─── Bind de la rejilla (delegación) ─── */
	function init() {
		document.querySelectorAll('[data-yzmf-feed]').forEach(function (grid) {
			grid.addEventListener('click', function (e) {
				var card = e.target.closest('.yzmf-feed-card');
				if (card && grid.contains(card)) {
					var id = parseInt(card.getAttribute('data-id'), 10);
					if (id) openPost(id);
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
