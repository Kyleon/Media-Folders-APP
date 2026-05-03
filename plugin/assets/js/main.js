/* global YZMF, jQuery */
(function ($) {
'use strict';

/* ════════════════════════════════════════════════
   STATE
════════════════════════════════════════════════ */
const App = {
    folder   : -1,          // -1=todos, 0=sin carpeta, >0=folder id
    selected : new Set(),   // ids seleccionados
    images   : [],          // imágenes actuales en grid
    page     : 1,
    pages    : 1,
    loading  : false,
    view     : 'grid',      // 'grid' | 'list'
    zoom     : 160,
    orderby  : 'date',
    order    : 'DESC',
    mime     : '',
    search   : '',
    // Modal
    modal    : { img: null, tab: 'meta', usedIn: null },
    // Drag state — separado del App para no contaminar
    drag     : {
        active   : false,   // true mientras arrastramos ITEMS
        ids      : [],      // ids que se están arrastrando
        target   : null,    // carpeta sobre la que estamos
    },
};

/* ════════════════════════════════════════════════
   BOOTSTRAP
════════════════════════════════════════════════ */
$(function () {
    buildLayout();   // buildLayout() crea #yzmf-app en el DOM
    positionApp();   // positionApp() lee #wpcontent DESPUÉS de que el DOM esté listo
    buildFolderPanel();
    buildToolbar();
    buildGrid();
    buildModal();
    buildUploadOverlay();
    bindKeyboard();
    syncFromURL();
    loadImages();
});

/**
 * Posiciona #yzmf-app leyendo los valores reales del DOM de WP.
 * Se llama después de buildLayout() para que #yzmf-app ya exista.
 */
function positionApp() {
    function update() {
        const app       = document.getElementById('yzmf-app');
        const wpcontent = document.getElementById('wpcontent');
        const adminbar  = document.getElementById('wpadminbar');
        if (!app || !wpcontent) return;

        const rect = wpcontent.getBoundingClientRect();
        const top  = adminbar ? adminbar.offsetHeight : 32;
        const left = Math.round(rect.left);

        app.style.top    = top  + 'px';
        app.style.left   = left + 'px';
        app.style.right  = '0px';
        app.style.bottom = '0px';
    }

    // Ejecutar ahora y con pequeño delay para asegurar que el DOM de WP
    // ha terminado de posicionarse
    update();
    setTimeout(update, 50);
    setTimeout(update, 200);

    // Re-calcular cuando WP colapsa/expande el sidebar
    const mo = new MutationObserver(function() { setTimeout(update, 60); });
    mo.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    window.addEventListener('resize', update);

    document.addEventListener('click', function(e) {
        if (e.target.closest('#collapse-button')) {
            setTimeout(update, 50);
            setTimeout(update, 300);
        }
    });
}


/* ════════════════════════════════════════════════
   LAYOUT SHELL
════════════════════════════════════════════════ */
function buildLayout() {
    $('#yzmf-app').html(`
      <div id="yzmf-toolbar"></div>
      <div id="yzmf-body">
        <div id="yzmf-panel">
          <div class="yz-panel-header">
            <span class="yz-panel-title">📁 Mis Medios</span>
            <div style="display:flex;gap:4px">
              <button class="yz-icon-btn add" id="yz-add-folder" title="Nueva carpeta">+</button>
              <button class="yz-icon-btn" id="yz-collapse-panel" title="Colapsar">◀</button>
            </div>
          </div>
          <div class="yz-tree" id="yz-tree"></div>
        </div>
        <div id="yzmf-grid-area">
          <div id="yzmf-grid-scroll">
            <div id="yzmf-grid"></div>
            <div id="yzmf-pagination"></div>
          </div>
        </div>
      </div>
    `);

    // Modal y upload overlay se añaden directamente al body
    // para evitar el contexto de apilamiento de #yzmf-app (z-index:1)
    $('body').append(`
      <div id="yzmf-modal-overlay">
        <div id="yzmf-modal">
          <div id="yz-modal-img-pane">
            <div class="yz-modal-img-wrap" id="yz-modal-img-wrap"></div>
            <div class="yz-modal-img-bar">
              <button class="yz-modal-nav prev" id="yz-prev">‹</button>
              <button class="yz-modal-nav next" id="yz-next">›</button>
              <button class="yz-modal-action" id="yz-copy-url">📋 Copiar URL</button>
              <a class="yz-modal-action" id="yz-open-new" href="#" target="_blank">↗ Abrir</a>
              <span class="yz-modal-img-meta" id="yz-img-meta"></span>
            </div>
          </div>
          <div id="yz-modal-edit-pane">
            <div class="yz-modal-edit-header">
              <span class="yz-modal-edit-title" id="yz-edit-title">—</span>
              <button class="yz-modal-close" id="yz-modal-close">✕</button>
            </div>
            <div class="yz-modal-tabs" id="yz-modal-tabs">
              <button class="yz-modal-tab active" data-tab="meta">Datos</button>
              <button class="yz-modal-tab" data-tab="exif">EXIF</button>
              <button class="yz-modal-tab" data-tab="used">Usado en</button>
              <button class="yz-modal-tab" data-tab="tools">Herramientas</button>
            </div>
            <div class="yz-modal-body" id="yz-modal-body"></div>
            <div class="yz-modal-footer">
              <button class="yz-modal-save" id="yz-modal-save">Guardar cambios</button>
              <button class="yz-modal-del-btn" id="yz-modal-del">🗑</button>
            </div>
          </div>
        </div>
      </div>
      <div id="yzmf-upload-overlay"><div class="yz-upload-msg" id="yz-upload-msg">📤 Suelta para subir</div></div>
    `);

    // Panel collapse
    $('#yz-collapse-panel').on('click', () => {
        $('#yzmf-panel').toggleClass('collapsed');
        $('#yz-collapse-panel').text($('#yzmf-panel').hasClass('collapsed') ? '▶' : '◀');
    });

    $('#yz-add-folder').on('click', () => showNewFolderInput(0));
}

/* ════════════════════════════════════════════════
   FOLDER PANEL
════════════════════════════════════════════════ */
function buildFolderPanel() {
    renderTree();
}

function renderTree() {
    const $tree = $('#yz-tree').empty();
    // Fila "Todos"
    $tree.append(makeFolderRow({ id: -1, name: YZMF.i18n.all_media, icon: '🖼️', count: '' }));
    // Fila "Sin carpeta"
    $tree.append(makeFolderRow({ id: 0, name: YZMF.i18n.unassigned, icon: '📭', count: '', dim: true }));
    // Árbol
    renderFolderKids(YZMF.tree, $tree, 0);
    highlightActiveFolder();
}

function makeFolderRow(f) {
    const $wrap = $(`<div class="yz-folder-item" data-id="${f.id || 0}"></div>`);
    const $row  = $(`
    <div class="yz-folder-row${App.folder === f.id ? ' active' : ''}${f.dim ? ' ' : ''}"
         data-id="${f.id}" style="padding-left:8px">
      <span class="yz-f-toggle hidden">▶</span>
      <span class="yz-f-icon">${f.icon || '📁'}</span>
      <span class="yz-f-name"${f.dim ? ' style="color:var(--dim);font-style:italic"' : ''}>${esc(f.name)}</span>
      <span class="yz-f-count">${f.count !== '' ? f.count : ''}</span>
    </div>`);

    $row.on('click', () => selectFolder(f.id));
    bindFolderDrop($row, f.id);
    $wrap.append($row);
    return $wrap;
}

function renderFolderKids(folders, $container, depth) {
    (folders || []).forEach(f => {
        const hasKids = f.children && f.children.length;
        const $wrap   = $(`<div class="yz-folder-item" data-id="${f.id}"></div>`);
        const $row    = $(`
        <div class="yz-folder-row${App.folder === f.id ? ' active' : ''}" data-id="${f.id}"
             style="padding-left:${8 + depth * 14}px">
          <span class="yz-f-toggle${hasKids ? '' : ' hidden'}">▶</span>
          <span class="yz-f-icon">📁</span>
          <span class="yz-f-name">${esc(f.name)}</span>
          <span class="yz-f-count">${f.count}</span>
        </div>`);

        $wrap.append($row);

        if (hasKids) {
            const $kids = $(`<div class="yz-f-children" data-parent="${f.id}"></div>`);
            renderFolderKids(f.children, $kids, depth + 1);
            $wrap.append($kids);
            $row.find('.yz-f-toggle').on('click', e => {
                e.stopPropagation();
                $kids.toggleClass('open');
                $row.find('.yz-f-toggle').toggleClass('open');
            });
        }

        $row.on('click',       e => { e.stopPropagation(); selectFolder(f.id); });
        $row.on('contextmenu', e => { e.preventDefault(); showFolderCtx(e, f); });
        bindFolderDrop($row, f.id);
        $container.append($wrap);
    });
}

/* ── Drop zone en carpeta ──
   Recibe tanto drags de ITEMS como de ARCHIVOS del SO
──────────────────────────────────────────────── */
function bindFolderDrop($row, folderId) {
    let enterCount = 0; // contador para manejar hijos del row

    $row[0].addEventListener('dragenter', e => {
        e.preventDefault(); e.stopPropagation();
        enterCount++;
        $row.addClass('drag-target');
    }, false);

    $row[0].addEventListener('dragleave', e => {
        e.stopPropagation();
        enterCount--;
        if (enterCount <= 0) { enterCount = 0; $row.removeClass('drag-target'); }
    }, false);

    $row[0].addEventListener('dragover', e => {
        e.preventDefault(); e.stopPropagation();
        e.dataTransfer.dropEffect = App.drag.active ? 'move' : 'copy';
    }, false);

    $row[0].addEventListener('drop', e => {
        e.preventDefault(); e.stopPropagation();
        enterCount = 0;
        $row.removeClass('drag-target');

        if (App.drag.active) {
            // Drop de ITEMS — mover a carpeta
            const ids = [...App.drag.ids];
            if (ids.length) assignImages(folderId, ids);
        } else {
            // Drop de ARCHIVOS del SO — subir a carpeta
            const files = e.dataTransfer.files;
            if (files && files.length) uploadFiles(files, folderId);
        }
    }, false);
}

function highlightActiveFolder() {
    $('.yz-folder-row').removeClass('active');
    $(`.yz-folder-row[data-id="${App.folder}"]`).addClass('active');
}

function selectFolder(id) {
    App.folder = id;
    App.page   = 1;
    deselectAll();
    highlightActiveFolder();
    loadImages();
    pushURL();
}

/* ── Inline new folder input ── */
function showNewFolderInput(parentId) {
    const $row = $(`
    <div class="yz-new-folder-row" id="yz-new-folder-row">
      <span>📁</span>
      <input class="yz-new-folder-inp" placeholder="${YZMF.i18n.new_folder}…" maxlength="60">
    </div>`);
    const $target = parentId > 0 ? $(`[data-parent="${parentId}"]`).addClass('open') : null;
    ($target && $target.length ? $target : $('#yz-tree')).append($row);
    const $inp = $row.find('input').focus();
    const confirm = () => {
        const v = $inp.val().trim();
        $row.remove();
        if (v) createFolder(v, parentId);
    };
    $inp.on('keydown', e => {
        if (e.key === 'Enter')  confirm();
        if (e.key === 'Escape') $row.remove();
    }).on('blur', confirm);
}

function createFolder(name, parentId) {
    ajax('yzmf_create_folder', { name, parent: parentId }, data => {
        YZMF.tree = addToTree(YZMF.tree, data, parentId);
        renderTree();
        refreshFlatFolders();
        toast(`📁 "${data.name}" creada`);
    });
}

function renameFolder(folder) {
    const $nm = $(`.yz-folder-row[data-id="${folder.id}"] .yz-f-name`);
    const old = $nm.text();
    $nm.replaceWith(`<input class="yz-rename-inp" value="${esc(old)}" maxlength="60">`);
    const $inp = $(`.yz-folder-row[data-id="${folder.id}"] .yz-rename-inp`).focus().select();
    const done = () => {
        const v = $inp.val().trim();
        if (!v || v === old) { $inp.replaceWith(`<span class="yz-f-name">${esc(old)}</span>`); return; }
        ajax('yzmf_rename_folder', { id: folder.id, name: v }, () => {
            updateTreeName(YZMF.tree, folder.id, v);
            renderTree(); refreshFlatFolders();
            toast(`✏️ Renombrada a "${v}"`);
        });
    };
    $inp.on('keydown', e => {
        if (e.key === 'Enter')  done();
        if (e.key === 'Escape') $inp.replaceWith(`<span class="yz-f-name">${esc(old)}</span>`);
    }).on('blur', done);
}

function deleteFolder(folder) {
    if (!confirm(YZMF.i18n.del_folder + '\n\n"' + folder.name + '"')) return;
    ajax('yzmf_delete_folder', { id: folder.id }, () => {
        YZMF.tree = removeFromTree(YZMF.tree, folder.id);
        if (App.folder === folder.id) selectFolder(-1);
        renderTree(); refreshFlatFolders();
        toast(`🗑 "${folder.name}" eliminada`);
    });
}

function refreshFlatFolders() {
    YZMF.flat_folders = flatTree(YZMF.tree);
    refreshMoveFolderSelect();
}

/* ════════════════════════════════════════════════
   TOOLBAR
════════════════════════════════════════════════ */
function buildToolbar() {
    $('#yzmf-toolbar').html(`
    <div class="yz-tb-group">
      <span class="yz-pill on" data-mime="">Todos</span>
      <span class="yz-pill" data-mime="image">🖼 Fotos</span>
      <span class="yz-pill" data-mime="video">🎬 Vídeo</span>
      <span class="yz-pill" data-mime="pdf">📄 PDF</span>
      <span class="yz-pill" data-mime="audio">🔊 Audio</span>
    </div>
    <div class="yz-tb-sep"></div>
    <div class="yz-tb-group">
      <select class="yz-select" id="yz-sort">
        <option value="date">Fecha</option>
        <option value="title">Nombre</option>
        <option value="meta_value_num">Tamaño</option>
      </select>
      <button class="yz-btn ghost" id="yz-sort-dir">↓</button>
    </div>
    <div class="yz-tb-sep"></div>
    <div class="yz-tb-group" id="yz-sel-group" style="display:none">
      <span class="yz-sel-badge" id="yz-sel-badge"></span>
      <button class="yz-btn" id="yz-desel">✕ Limpiar</button>
      <select class="yz-select" id="yz-move-select"></select>
      <button class="yz-btn pri" id="yz-move-btn">Mover</button>
      <button class="yz-btn" id="yz-copy-btn">Copiar</button>
      <button class="yz-btn danger" id="yz-del-btn">🗑 Borrar</button>
    </div>
    <button class="yz-btn" id="yz-sel-all">☑ Todo</button>
    <div class="yz-spacer"></div>
    <div class="yz-tb-group">
      <input class="yz-search" id="yz-search" type="search" placeholder="Buscar…">
      <div class="yz-tb-sep"></div>
      <button class="yz-btn ghost" id="yz-view-grid" title="Cuadrícula">⊞</button>
      <button class="yz-btn ghost" id="yz-view-list" title="Lista">≡</button>
      <div class="yz-tb-sep"></div>
      <div class="yz-zoom">🔍<input type="range" id="yz-zoom" min="90" max="280" step="10" value="160"></div>
    </div>`);

    refreshMoveFolderSelect();

    // Filter pills
    $('#yzmf-toolbar').on('click', '.yz-pill', function () {
        $('#yzmf-toolbar .yz-pill').removeClass('on');
        $(this).addClass('on');
        App.mime = $(this).data('mime'); App.page = 1; loadImages();
    });

    // Sort
    $('#yz-sort').on('change', function () { App.orderby = $(this).val(); App.page = 1; loadImages(); });
    $('#yz-sort-dir').on('click', function () {
        App.order = App.order === 'DESC' ? 'ASC' : 'DESC';
        $(this).text(App.order === 'DESC' ? '↓' : '↑');
        App.page = 1; loadImages();
    });

    // Selection actions
    $('#yz-sel-all').on('click', selectAll);
    $('#yz-desel').on('click',   deselectAll);
    $('#yz-move-btn').on('click', () => assignImages(parseInt($('#yz-move-select').val()), [...App.selected]));
    $('#yz-copy-btn').on('click', () => copyImages(parseInt($('#yz-move-select').val()),   [...App.selected]));
    $('#yz-del-btn').on('click',  () => deleteImages([...App.selected]));

    // Botón IA en toolbar (lote) — solo si hay API key
    if (YZMF.has_ai) {
        $('#yz-sel-group').append(
            $('<button class="yz-btn" id="yz-ai-batch-btn" title="Generar alt y caption con IA para las imágenes seleccionadas">✨ Generar IA</button>')
                .on('click', () => generateAIBatch([...App.selected]))
        );
    }

    // View toggle
    $('#yz-view-grid').on('click', () => setView('grid'));
    $('#yz-view-list').on('click', () => setView('list'));
    setView('grid'); // initial

    // Zoom
    $('#yz-zoom').on('input', function () { App.zoom = parseInt($(this).val()); applyZoom(); });

    // Search
    let st;
    $('#yz-search').on('input', function () {
        clearTimeout(st);
        st = setTimeout(() => { App.search = $(this).val(); App.page = 1; loadImages(); }, 320);
    });
}

function refreshMoveFolderSelect() {
    const $s = $('#yz-move-select').empty();
    $s.append(`<option value="0">${YZMF.i18n.no_folder}</option>`);
    flatTree(YZMF.tree).forEach(f => {
        $s.append(`<option value="${f.id}">${'  '.repeat(f._d)}${esc(f.name)}</option>`);
    });
}

function setView(v) {
    App.view = v;
    $('#yzmf-grid').toggleClass('list-view', v === 'list');
    $('#yz-view-grid').toggleClass('pri', v === 'grid');
    $('#yz-view-list').toggleClass('pri', v === 'list');
    if (v === 'grid') applyZoom();
    else $('#yzmf-grid').css('grid-template-columns', '');
}

function applyZoom() {
    if (App.view === 'grid') {
        $('#yzmf-grid').css('grid-template-columns', `repeat(auto-fill,minmax(${App.zoom}px,1fr))`);
    }
}

/* ════════════════════════════════════════════════
   GRID
════════════════════════════════════════════════ */
function buildGrid() {
    // Drop zone para archivos del SO sobre el área del grid
    const gridScroll = document.getElementById('yzmf-grid-scroll');
    let enterCount = 0;

    gridScroll.addEventListener('dragenter', e => {
        if (App.drag.active) return; // ignorar drags de items
        if (!hasFiles(e)) return;
        e.preventDefault(); e.stopPropagation();
        enterCount++;
        gridScroll.classList.add('upload-drag-over');
        const name = getFolderName(App.folder);
        $('#yz-upload-msg').text(name ? `📤 Subir a "${name}"` : '📤 Subir archivos');
        $('#yzmf-upload-overlay').addClass('show');
    }, false);

    gridScroll.addEventListener('dragleave', e => {
        if (App.drag.active) return;
        enterCount--;
        if (enterCount <= 0) {
            enterCount = 0;
            gridScroll.classList.remove('upload-drag-over');
            $('#yzmf-upload-overlay').removeClass('show');
        }
    }, false);

    gridScroll.addEventListener('dragover', e => {
        if (App.drag.active) return;
        if (!hasFiles(e)) return;
        e.preventDefault(); e.stopPropagation();
        e.dataTransfer.dropEffect = 'copy';
    }, false);

    gridScroll.addEventListener('drop', e => {
        if (App.drag.active) return;
        e.preventDefault(); e.stopPropagation();
        enterCount = 0;
        gridScroll.classList.remove('upload-drag-over');
        $('#yzmf-upload-overlay').removeClass('show');
        const files = e.dataTransfer.files;
        if (files && files.length) uploadFiles(files, App.folder > 0 ? App.folder : 0);
    }, false);
}

function renderGrid() {
    const $grid = $('#yzmf-grid').empty();
    setView(App.view); // re-apply view class

    if (!App.images.length) {
        $grid.html('<div class="yz-empty">📭 No hay imágenes en esta carpeta</div>');
        renderPagination();
        return;
    }

    App.images.forEach(img => $grid.append(buildItem(img)));
    renderPagination();
}

function buildItem(img) {
    const isSel = App.selected.has(img.id);
    const isImg = img.mime && img.mime.startsWith('image/');
    const exifTags = Object.values(img.exif || {}).slice(0, 3)
        .map(v => `<span class="yz-etag">${esc(v)}</span>`).join('');

    const $item = $(`
    <div class="yz-item${isSel ? ' selected' : ''}" data-id="${img.id}">
      <div class="yz-thumb">
        ${isImg && img.thumb
            ? `<img src="${img.thumb}" alt="${esc(img.title)}" loading="lazy">`
            : `<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:30px">${mimeIcon(img.mime)}</div>`
        }
        <div class="yz-checkbox" title="Seleccionar"></div>
        <div class="yz-exif-ov">${exifTags}</div>
      </div>
      <div class="yz-info">
        <span class="yz-info-name">${esc(img.title || img.filename)}</span>
        <span class="yz-info-size">${img.filesize_h}</span>
      </div>
    </div>`);

    if (App.view === 'list') {
        $item.find('.yz-info').append(`<span class="yz-info-meta">${img.date} · ${img.width || '?'}×${img.height || '?'}</span>`);
    }

    // ── Checkbox click → seleccionar ──────────────────────────────
    $item.find('.yz-checkbox').on('click', e => {
        e.stopPropagation();
        if (e.shiftKey && App.selected.size) {
            rangeSelect(img.id);
        } else {
            toggleSelect(img.id);
        }
    });

    // ── Click principal → abrir modal ─────────────────────────────
    $item.on('click', e => {
        e.stopPropagation();
        openModal(img.id);
    });

    // ── Clic derecho → menú contextual ────────────────────────────
    $item.on('contextmenu', e => {
        e.preventDefault();
        if (!App.selected.has(img.id)) { deselectAll(); toggleSelect(img.id); }
        showImgCtx(e);
    });

    // ═══════════════════════════════════════════════════════════════
    // DRAG & DROP — ITEMS
    // Usamos los eventos nativos del DOM (no jQuery drag) para
    // tener control total sobre dataTransfer y stopPropagation.
    // ═══════════════════════════════════════════════════════════════
    const el = $item[0];

    el.setAttribute('draggable', 'true');

    el.addEventListener('dragstart', e => {
        // Si el item no está seleccionado, seleccionarlo solo
        if (!App.selected.has(img.id)) {
            deselectAll();
            toggleSelect(img.id);
        }

        // Guardar IDs en el estado y en dataTransfer
        App.drag.active = true;
        App.drag.ids    = [...App.selected];

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', App.drag.ids.join(','));

        // Crear ghost image personalizada con el número de items
        const ghost = document.createElement('div');
        ghost.textContent = App.drag.ids.length > 1
            ? `📷 ${App.drag.ids.length} imágenes`
            : `📷 ${img.title || img.filename}`;
        ghost.style.cssText = 'position:fixed;top:-100px;background:#1a1a1a;color:#d2d2d2;border:1px solid #3a3a3a;border-radius:6px;padding:6px 12px;font-size:12px;font-family:-apple-system,sans-serif;white-space:nowrap';
        document.body.appendChild(ghost);
        e.dataTransfer.setDragImage(ghost, 0, 0);
        setTimeout(() => ghost.remove(), 0);

        // Marcar items como dragging (CSS)
        $('body').addClass('yz-dragging-items');
        setTimeout(() => {
            App.selected.forEach(id => {
                const $el = $(`.yz-item[data-id="${id}"]`);
                $el.addClass('dragging');
            });
        }, 0);

    }, false);

    el.addEventListener('dragend', e => {
        App.drag.active = false;
        App.drag.ids    = [];
        $('body').removeClass('yz-dragging-items');
        $('.yz-item').removeClass('dragging');
        $('.yz-folder-row').removeClass('drag-target');
        $('#yzmf-upload-overlay').removeClass('show');
    }, false);

    return $item;
}

function renderPagination() {
    const $pg = $('#yzmf-pagination').empty();
    if (App.pages <= 1) return;
    if (App.page > 1) {
        $('<button class="yz-btn">← Anterior</button>')
            .on('click', () => { App.page--; loadImages(); })
            .appendTo($pg);
    }
    $pg.append(`<span style="font-size:12px;color:var(--dim)">${App.page} / ${App.pages}</span>`);
    if (App.page < App.pages) {
        $('<button class="yz-btn">Siguiente →</button>')
            .on('click', () => { App.page++; loadImages(); })
            .appendTo($pg);
    }
}

/* ════════════════════════════════════════════════
   LOAD IMAGES
════════════════════════════════════════════════ */
function loadImages() {
    if (App.loading) return;
    App.loading = true;
    $('#yzmf-grid').html('<div class="yz-loading"><div class="yz-spinner"></div> Cargando…</div>');

    ajax('yzmf_get_images', {
        folder  : App.folder,
        paged   : App.page,
        per_page: 40,
        search  : App.search,
        orderby : App.orderby,
        order   : App.order,
        mime    : App.mime,
    }, data => {
        App.images  = data.images;
        App.pages   = data.pages;
        App.loading = false;
        renderGrid();
    });
}

/* ════════════════════════════════════════════════
   SELECTION
════════════════════════════════════════════════ */
function toggleSelect(id) {
    App.selected.has(id) ? App.selected.delete(id) : App.selected.add(id);
    $(`.yz-item[data-id="${id}"]`).toggleClass('selected', App.selected.has(id));
    updateSelUI();
}

function selectAll() {
    App.images.forEach(i => App.selected.add(i.id));
    $('.yz-item').addClass('selected');
    updateSelUI();
}

function deselectAll() {
    App.selected.clear();
    $('.yz-item').removeClass('selected');
    updateSelUI();
}

function rangeSelect(toId) {
    const ids  = App.images.map(i => i.id);
    const last = [...App.selected].pop();
    const a = ids.indexOf(last), b = ids.indexOf(toId);
    if (a < 0 || b < 0) return;
    const [from, to] = a < b ? [a, b] : [b, a];
    ids.slice(from, to + 1).forEach(id => App.selected.add(id));
    $('.yz-item').each(function () {
        $(this).toggleClass('selected', App.selected.has(parseInt($(this).data('id'))));
    });
    updateSelUI();
}

function updateSelUI() {
    const n = App.selected.size;
    $('#yz-sel-badge').text(`${n} sel.`);
    $('#yz-sel-group').toggle(n > 0);
}

/* ════════════════════════════════════════════════
   IMAGE ACTIONS
════════════════════════════════════════════════ */
function assignImages(folderId, ids) {
    if (!ids.length) return;
    const name = folderId === 0
        ? YZMF.i18n.no_folder
        : (flatTree(YZMF.tree).find(f => f.id === folderId)?.name || '?');

    ajax('yzmf_assign_images', { folder_id: folderId, image_ids: ids }, data => {
        toast(`✓ ${data.assigned} imagen${data.assigned !== 1 ? 'es' : ''} movida${data.assigned !== 1 ? 's' : ''} a "${name}"`, 'ok');
        deselectAll();
        if (App.folder !== -1) { App.page = 1; loadImages(); }
        reloadTree();
    });
}

function copyImages(folderId, ids) {
    if (!ids.length || !folderId) return;
    const name = flatTree(YZMF.tree).find(f => f.id === folderId)?.name || '?';
    ajax('yzmf_copy_images', { folder_id: folderId, image_ids: ids }, data => {
        toast(`✓ ${data.copied} copiada${data.copied !== 1 ? 's' : ''} a "${name}"`, 'ok');
        reloadTree();
    });
}

function deleteImages(ids) {
    if (!ids.length) return;
    if (!confirm(`${YZMF.i18n.del_images}\n\n${ids.length} imagen${ids.length !== 1 ? 'es' : ''}`)) return;
    ajax('yzmf_delete_images', { image_ids: ids }, data => {
        toast(`🗑 ${data.deleted} eliminada${data.deleted !== 1 ? 's' : ''}`, 'ok');
        deselectAll();
        App.page = 1; loadImages(); reloadTree();
    });
}

/* ════════════════════════════════════════════════
   FILE UPLOAD
════════════════════════════════════════════════ */
function buildUploadOverlay() {
    // El overlay ya está en el HTML — nada que hacer aquí
    // excepto capturar el drag de archivos a nivel document
    // para cuando el usuario suelta FUERA del grid-scroll o de una carpeta

    document.addEventListener('dragenter', e => {
        if (App.drag.active) return;
        if (!hasFiles(e)) return;
        // No hacer nada especial aquí — el grid-scroll y las carpetas
        // tienen sus propios listeners
    }, false);

    // Prevenir el comportamiento por defecto del navegador
    // (que abriría el archivo) cuando soltamos en cualquier lugar
    document.addEventListener('dragover', e => {
        if (App.drag.active) return;
        if (hasFiles(e)) e.preventDefault();
    }, false);

    document.addEventListener('drop', e => {
        if (App.drag.active) return;
        e.preventDefault(); // evitar que el navegador abra el archivo
        $('#yzmf-upload-overlay').removeClass('show');
        $('#yzmf-grid-scroll').removeClass('upload-drag-over');
    }, false);
}

function uploadFiles(files, folderId) {
    let processed = 0, ok = 0, fail = 0;
    const total = files.length;

    toast(`📤 Subiendo ${total} archivo${total !== 1 ? 's' : ''}…`, 'info');

    const finish = () => {
        if (processed !== total) return;
        if (ok > 0) toast(`✓ ${ok} archivo${ok !== 1 ? 's' : ''} subido${ok !== 1 ? 's' : ''}${fail ? ' · ' + fail + ' con error' : ''}`, fail ? 'err' : 'ok');
        else        toast(`⚠ No se pudo subir ningún archivo`, 'err');
        reloadTree();
        loadImages();
    };

    Array.from(files).forEach(file => {
        const fd = new FormData();
        fd.append('action',       'upload-attachment');
        fd.append('_wpnonce',     YZMF.upload_nonce);
        fd.append('async-upload', file);
        if (folderId > 0) fd.append('yzmf_folder', folderId);

        fetch(YZMF.upload_url, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                processed++;
                if (res.success) ok++;
                else { fail++; toast(`⚠ Error subiendo "${file.name}"`, 'err'); }
                finish();
            })
            .catch(() => {
                processed++; fail++;
                toast(`⚠ Error de red en "${file.name}"`, 'err');
                finish();
            });
    });
}

/* ════════════════════════════════════════════════
   MODAL DE EDICIÓN
════════════════════════════════════════════════ */
function buildModal() {
    // Close
    $('#yz-modal-close').on('click', closeModal);
    $('#yzmf-modal-overlay').on('click', e => {
        if ($(e.target).is('#yzmf-modal-overlay')) closeModal();
    });

    // Tabs
    $('#yz-modal-tabs').on('click', '.yz-modal-tab', function () {
        captureModalDirty(); // preservar lo que el usuario haya tecleado en "Datos"
        $('.yz-modal-tab').removeClass('active');
        $(this).addClass('active');
        App.modal.tab = $(this).data('tab');
        renderModalTab();
    });

    // Nav
    $('#yz-prev').on('click', () => navigateModal(-1));
    $('#yz-next').on('click', () => navigateModal(1));

    // Copy URL
    $('#yz-copy-url').on('click', () => {
        const url = App.modal.img?.url;
        if (!url) return;
        navigator.clipboard.writeText(url).then(() => {
            $('#yz-copy-url').addClass('copied').text('✓ Copiada');
            setTimeout(() => $('#yz-copy-url').removeClass('copied').text('📋 Copiar URL'), 1800);
        }).catch(() => toast('No se pudo copiar', 'err'));
    });

    // Save / Delete
    $('#yz-modal-save').on('click', saveModal);
    $('#yz-modal-del').on('click', () => {
        if (!App.modal.img) return;
        if (!confirm(YZMF.i18n.del_images)) return;
        deleteImages([App.modal.img.id]);
        closeModal();
    });
}

function openModal(imgId) {
    App.modal.tab    = 'meta';
    App.modal.img    = null;
    App.modal.usedIn = null;
    App.modal.dirty  = {}; // valores tecleados pendientes de guardar

    $('.yz-modal-tab').removeClass('active');
    $('.yz-modal-tab[data-tab="meta"]').addClass('active');

    $('#yz-modal-img-wrap').html('<div class="yz-loading" style="height:100%"><div class="yz-spinner"></div></div>');
    $('#yz-modal-body').html('<div class="yz-loading"><div class="yz-spinner"></div></div>');
    $('#yzmf-modal-overlay').addClass('open');

    ajax('yzmf_get_image_detail', { id: imgId }, img => {
        App.modal.img = img;
        renderModalImage();
        renderModalTab();
        updateModalNav();

        // Cargar "usado en" en segundo plano
        ajax('yzmf_get_used_in', { id: imgId }, data => {
            App.modal.usedIn = data;
            if (App.modal.tab === 'used') renderModalTab();
        });
    });
}

function closeModal() {
    $('#yzmf-modal-overlay').removeClass('open');
    App.modal.img = null;
}

function navigateModal(dir) {
    if (!App.modal.img) return;
    const idx  = App.images.findIndex(i => i.id === App.modal.img.id);
    const next = App.images[idx + dir];
    if (next) openModal(next.id);
}

function updateModalNav() {
    if (!App.modal.img) return;
    const idx = App.images.findIndex(i => i.id === App.modal.img.id);
    $('#yz-prev').prop('disabled', idx <= 0);
    $('#yz-next').prop('disabled', idx >= App.images.length - 1);
    $('#yz-modal-img-wrap .yz-modal-counter').remove();
    $('#yz-modal-img-wrap').append(`<div class="yz-modal-counter">${idx + 1} / ${App.images.length}</div>`);
}

function renderModalImage() {
    const img = App.modal.img;
    if (!img) return;
    const isImg = img.mime && img.mime.startsWith('image/');

    $('#yz-edit-title').text(img.title || img.filename || 'Imagen');
    $('#yz-open-new').attr('href', img.url || '#');
    $('#yz-img-meta').text([
        img.width && img.height ? `${img.width}×${img.height}` : null,
        img.filesize_h || null,
        img.date || null,
    ].filter(Boolean).join(' · '));

    if (isImg) {
        $('#yz-modal-img-wrap').html(`
        <img src="${img.medium || img.url}" alt="${esc(img.title)}"
             style="max-width:100%;max-height:calc(100vh - 110px);object-fit:contain;border-radius:3px"
             onerror="this.src='${img.url || ''}'">
        <div class="yz-modal-counter">— / —</div>`);
    } else {
        $('#yz-modal-img-wrap').html(`
        <div style="display:flex;flex-direction:column;align-items:center;gap:14px;color:#333">
          <span style="font-size:64px">${mimeIcon(img.mime)}</span>
          <span style="font-size:12px;color:var(--dim)">${esc(img.filename)}</span>
        </div>
        <div class="yz-modal-counter">— / —</div>`);
    }
    updateModalNav();
}

function captureModalDirty() {
    if (!App.modal.img) return;
    const fields = ['title', 'alt', 'seo_title', 'caption', 'description', 'folder'];
    fields.forEach(k => {
        const sel = '#ym-' + (k === 'seo_title' ? 'seo' : k === 'description' ? 'desc' : k);
        const $el = $(sel);
        if ($el.length) App.modal.dirty[k] = $el.val();
    });
}

function applyModalDirty() {
    const d = App.modal.dirty || {};
    if (d.title !== undefined)       $('#ym-title').val(d.title);
    if (d.alt !== undefined)         $('#ym-alt').val(d.alt);
    if (d.seo_title !== undefined)   $('#ym-seo').val(d.seo_title);
    if (d.caption !== undefined)     $('#ym-caption').val(d.caption);
    if (d.description !== undefined) $('#ym-desc').val(d.description);
    if (d.folder !== undefined && $('#ym-folder').length) $('#ym-folder').val(d.folder);
}

function renderModalTab() {
    const img = App.modal.img;
    if (!img) return;
    const $body = $('#yz-modal-body').empty();

    if (App.modal.tab === 'meta') {
        const allFolders = flatTree(YZMF.tree);
        const curFid = img.folder_ids && img.folder_ids.length ? img.folder_ids[0] : 0;
        const folderOpts = `<option value="0">${YZMF.i18n.no_folder}</option>`
            + allFolders.map(f => `<option value="${f.id}"${curFid === f.id ? ' selected' : ''}>${'  '.repeat(f._d)}${esc(f.name)}</option>`).join('');

        $body.html(`
        <div class="yz-field"><label>Título</label><input id="ym-title" value="${esc(img.title || '')}"></div>
        <div class="yz-field"><label>Alt text (SEO)</label><input id="ym-alt" value="${esc(img.alt || '')}" placeholder="Describe la imagen"></div>
        <div class="yz-field"><label>SEO Title</label><input id="ym-seo" value="${esc(img.seo_title || '')}" placeholder="Título para buscadores"></div>
        <div class="yz-field"><label>Pie de foto</label><textarea id="ym-caption" rows="2">${esc(img.caption || '')}</textarea></div>
        <div class="yz-field"><label>Descripción</label><textarea id="ym-desc" rows="3">${esc(img.description || '')}</textarea></div>
        <div class="yz-field">
          <label>URL del archivo</label>
          <div class="yz-url-row">
            <input readonly id="ym-url" value="${esc(img.url || '')}">
            <button class="yz-copy-btn" id="ym-inline-copy">📋</button>
          </div>
        </div>
        <div class="yz-field"><label>Carpeta</label><select id="ym-folder">${folderOpts}</select></div>`);

        $('#ym-inline-copy').on('click', () => {
            navigator.clipboard.writeText($('#ym-url').val()).then(() => {
                $('#ym-inline-copy').addClass('ok').text('✓');
                setTimeout(() => $('#ym-inline-copy').removeClass('ok').text('📋'), 1800);
            });
        });

        applyModalDirty();

        // Botón de IA para generar alt + caption
        if (YZMF.has_ai) {
            const $aiRow = $(`
            <div style="display:flex;gap:8px;align-items:center;padding:10px 0 2px;border-top:1px solid var(--border);margin-top:4px">
              <button class="yz-btn pri" id="ym-ai-btn" style="gap:6px">
                ✨ Generar alt + caption con IA
              </button>
              <span id="ym-ai-status" style="font-size:11px;color:var(--dim)"></span>
            </div>`);
            $body.append($aiRow);

            $('#ym-ai-btn').on('click', function () {
                const $btn = $(this);
                const $status = $('#ym-ai-status');
                $btn.prop('disabled', true).text('⏳ Generando…');
                $status.text('');

                generateAISingle(img.id, function(data) {
                    // Rellenar los campos en el modal
                    $('#ym-alt').val(data.alt);
                    $('#ym-caption').val(data.caption);
                    $btn.prop('disabled', false).text('✨ Generar alt + caption con IA');
                    $status.css('color', 'var(--ok)').text('✓ Generado y guardado');
                    setTimeout(() => $status.text(''), 3000);
                }, function(err) {
                    $btn.prop('disabled', false).text('✨ Generar alt + caption con IA');
                    $status.css('color', 'var(--danger)').text('⚠ ' + err);
                });
            });
        }
    }

    else if (App.modal.tab === 'exif') {
        const exif = img.exif || {};
        const cards = Object.entries(exif)
            .map(([k, v]) => `<div class="yz-exif-card"><div class="yz-exif-k">${esc(k)}</div><div class="yz-exif-v">${esc(v)}</div></div>`)
            .join('') || '<span style="color:var(--dim);font-size:12px">Sin datos EXIF</span>';

        $body.html(`
        <div class="yz-section-label">Técnico</div>
        <div class="yz-exif-grid">${cards}</div>
        <div class="yz-section-label" style="margin-top:8px">Archivo</div>
        <div class="yz-file-rows">
          ${[
              ['Dimensiones', img.width && img.height ? `${img.width} × ${img.height} px` : '—'],
              ['Tamaño',      img.filesize_h || '—'],
              ['Tipo',        img.mime       || '—'],
              ['Fecha',       img.date       || '—'],
              ['Nombre',      img.filename   || '—'],
          ].map(([k, v]) => `<div class="yz-file-row"><span class="yz-file-key">${k}</span><span class="yz-file-val">${esc(v)}</span></div>`).join('')}
        </div>`);
    }

    else if (App.modal.tab === 'used') {
        if (App.modal.usedIn === null) {
            $body.html('<div class="yz-loading"><div class="yz-spinner"></div> Buscando…</div>');
        } else if (!App.modal.usedIn.length) {
            $body.html('<div style="color:var(--dim);font-size:12px;padding:10px 0;text-align:center">No se encontraron usos de esta imagen en el sitio</div>');
        } else {
            $body.html(App.modal.usedIn.map(u => `
            <div class="yz-used-card">
              <div class="yz-used-title">${esc(u.title)}</div>
              <div class="yz-used-meta"><span class="yz-used-type">${esc(u.type)}</span><span class="yz-used-via">${esc(u.via)}</span></div>
              <div class="yz-used-links">
                <a href="${esc(u.url)}" target="_blank">Ver ↗</a>
                <a href="${esc(u.edit)}" target="_blank">Editar ✏️</a>
              </div>
            </div>`).join(''));
        }
    }

    else if (App.modal.tab === 'tools') {
        $body.html(`
        <div class="yz-section-label">Miniaturas</div>
        <button class="yz-tool-btn" id="yz-regen">🔄 Regenerar miniaturas</button>
        <div class="yz-section-label" style="margin-top:8px">WP</div>
        <a class="yz-tool-btn" href="${esc(img.edit_url || '#')}" target="_blank">⚙️ Editor completo de WordPress ↗</a>
        <div class="yz-section-label" style="margin-top:8px">Eliminar</div>
        <button class="yz-tool-btn danger" id="yz-del-from-tools">🗑 Eliminar imagen</button>`);

        $('#yz-regen').on('click', function () {
            const $btn = $(this).addClass('running').text('⏳ Regenerando…').prop('disabled', true);
            ajax('yzmf_regen_thumbnails', { id: img.id }, data => {
                $btn.removeClass('running').text('✓ Listo').prop('disabled', false);
                toast('✓ Miniaturas regeneradas', 'ok');
                // Actualizar thumb en grid
                if (data.thumb) {
                    App.images.forEach(i => { if (i.id === img.id) i.thumb = data.thumb; });
                    $(`.yz-item[data-id="${img.id}"] .yz-thumb img`).attr('src', data.thumb);
                }
            });
        });

        $('#yz-del-from-tools').on('click', () => {
            if (!confirm(YZMF.i18n.del_images)) return;
            deleteImages([img.id]);
            closeModal();
        });
    }
}

function saveModal() {
    if (!App.modal.img) return;
    captureModalDirty(); // por si el usuario está en otro tab al pulsar guardar
    const id   = App.modal.img.id;
    const $btn = $('#yz-modal-save');
    const d    = App.modal.dirty || {};
    const fid  = (d.folder !== undefined) ? parseInt(d.folder) : ($('#ym-folder').length ? parseInt($('#ym-folder').val()) : null);

    const data = { id };
    if (d.title !== undefined)       data.title       = d.title;
    if (d.alt !== undefined)         data.alt         = d.alt;
    if (d.seo_title !== undefined)   data.seo_title   = d.seo_title;
    if (d.caption !== undefined)     data.caption     = d.caption;
    if (d.description !== undefined) data.description = d.description;

    $btn.text('Guardando…').prop('disabled', true);

    ajax('yzmf_save_image_meta', data, () => {
        const done = () => {
            $btn.addClass('saved').text('✓ Guardado');
            toast('✓ Guardado', 'ok');
            setTimeout(() => $btn.removeClass('saved').text('Guardar cambios').prop('disabled', false), 1800);

            // Sincronizar caché local con todos los campos editados
            const local = App.images.find(i => i.id === id);
            if (local) {
                if (data.title       !== undefined) local.title       = data.title;
                if (data.alt         !== undefined) local.alt         = data.alt;
                if (data.seo_title   !== undefined) local.seo_title   = data.seo_title;
                if (data.caption     !== undefined) local.caption     = data.caption;
                if (data.description !== undefined) local.description = data.description;
            }
            if (App.modal.img) {
                Object.assign(App.modal.img, data);
            }
            App.modal.dirty = {};

            // Actualizar nombre visible en grid
            if (data.title !== undefined) {
                $(`.yz-item[data-id="${id}"] .yz-info-name`).text(data.title);
            }

            // Si la imagen ya no pertenece a la carpeta visible, sacarla del grid
            if (fid !== null && App.folder > 0 && fid !== App.folder) {
                App.images = App.images.filter(i => i.id !== id);
                $(`.yz-item[data-id="${id}"]`).remove();
            }

            reloadTree();
        };

        if (fid !== null) {
            ajax('yzmf_assign_images', { folder_id: fid, image_ids: [id] }, done);
        } else {
            done();
        }
    });
}

/* ════════════════════════════════════════════════
   CONTEXT MENUS
════════════════════════════════════════════════ */
function showImgCtx(e) {
    const n       = App.selected.size;
    const folders = flatTree(YZMF.tree);
    let html = `<div class="yz-ctx-head">${n} imagen${n !== 1 ? 'es' : ''}</div><div class="yz-ctx-sep"></div>`;

    if (n === 1) {
        const id = [...App.selected][0];
        html += `<div class="yz-ctx-item" data-act="edit" data-id="${id}">✏️ Editar detalles</div>`;
    }

    html += `<div class="yz-ctx-sub">Mover a carpeta</div>`;
    html += `<div class="yz-ctx-item" data-act="mv" data-fid="0">📭 Sin carpeta</div>`;
    folders.forEach(f => {
        html += `<div class="yz-ctx-item" data-act="mv" data-fid="${f.id}">${'&nbsp;'.repeat(f._d * 3)}📁 ${esc(f.name)}</div>`;
    });

    html += `<div class="yz-ctx-sep"></div><div class="yz-ctx-sub">Copiar a carpeta</div>`;
    folders.forEach(f => {
        html += `<div class="yz-ctx-item" data-act="cp" data-fid="${f.id}">${'&nbsp;'.repeat(f._d * 3)}📂 ${esc(f.name)}</div>`;
    });

    html += `<div class="yz-ctx-sep"></div>`;
    if (App.folder > 0) html += `<div class="yz-ctx-item" data-act="rem">✕ Quitar de esta carpeta</div>`;
    html += `<div class="yz-ctx-item danger" data-act="del">🗑 Eliminar</div>`;

    showCtx(html, e.clientX, e.clientY);

    $('#yz-ctx [data-act="edit"]').on('click', function () { hideCtx(); openModal(parseInt($(this).data('id'))); });
    $('#yz-ctx [data-act="mv"]').on('click',  function () { hideCtx(); assignImages(parseInt($(this).data('fid')), [...App.selected]); });
    $('#yz-ctx [data-act="cp"]').on('click',  function () { hideCtx(); copyImages(parseInt($(this).data('fid')), [...App.selected]); });
    $('#yz-ctx [data-act="rem"]').on('click', () => {
        hideCtx();
        [...App.selected].forEach(id => ajax('yzmf_remove_from_folder', { folder_id: App.folder, image_id: id }, () => {}));
        toast('Quitadas de la carpeta');
        deselectAll();
        setTimeout(() => { App.page = 1; loadImages(); }, 350);
    });
    $('#yz-ctx [data-act="del"]').on('click', () => { hideCtx(); deleteImages([...App.selected]); });
}

function showFolderCtx(e, folder) {
    showCtx(`
    <div class="yz-ctx-head">📁 ${esc(folder.name)}</div>
    <div class="yz-ctx-sep"></div>
    <div class="yz-ctx-item" id="fci-ren">✏️ Renombrar</div>
    <div class="yz-ctx-item" id="fci-sub">📁 Nueva subcarpeta</div>
    <div class="yz-ctx-sep"></div>
    <div class="yz-ctx-item danger" id="fci-del">🗑 Eliminar carpeta</div>`,
    e.clientX, e.clientY);

    $('#fci-ren').on('click', () => { hideCtx(); renameFolder(folder); });
    $('#fci-sub').on('click', () => { hideCtx(); showNewFolderInput(folder.id); });
    $('#fci-del').on('click', () => { hideCtx(); deleteFolder(folder); });
}

let _ctxEl = null;
function showCtx(html, x, y) {
    hideCtx();
    _ctxEl = $(`<div class="yz-ctx" id="yz-ctx">${html}</div>`).appendTo('#yzmf-app')[0];
    const W = $(_ctxEl).outerWidth() || 190;
    const H = $(_ctxEl).outerHeight() || 200;
    $(_ctxEl).css({
        left: Math.min(x, window.innerWidth  - W - 8),
        top:  Math.min(y, window.innerHeight - H - 8),
    });
}
function hideCtx() { $('#yz-ctx').remove(); _ctxEl = null; }

/* ════════════════════════════════════════════════
   KEYBOARD
════════════════════════════════════════════════ */
function bindKeyboard() {
    $(document).on('keydown', e => {
        // Modal abierto
        if ($('#yzmf-modal-overlay').hasClass('open')) {
            if (e.key === 'Escape')     closeModal();
            if (e.key === 'ArrowLeft')  navigateModal(-1);
            if (e.key === 'ArrowRight') navigateModal(1);
            return;
        }
        // Sin modal
        if (e.key === 'Escape') { hideCtx(); deselectAll(); }
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !$(e.target).is('input,textarea,select')) {
            e.preventDefault(); selectAll();
        }
        if (e.key === 'Delete' && App.selected.size && !$(e.target).is('input,textarea')) {
            deleteImages([...App.selected]);
        }
    });

    // Clic en zona vacía → deseleccionar
    $(document).on('click', e => {
        if (!$(e.target).closest('.yz-item,.yz-ctx,#yzmf-toolbar,#yzmf-modal-overlay').length) {
            deselectAll();
        }
        if (!$(e.target).closest('#yz-ctx').length) hideCtx();
    });
}

/* ════════════════════════════════════════════════
   URL STATE
════════════════════════════════════════════════ */
function pushURL() {
    const url = new URL(window.location.href);
    App.folder === -1
        ? url.searchParams.delete('folder')
        : url.searchParams.set('folder', App.folder);
    history.replaceState({}, '', url.toString());
}

function syncFromURL() {
    const f = new URL(window.location.href).searchParams.get('folder');
    if (f !== null) App.folder = parseInt(f);
}

/* ════════════════════════════════════════════════
   TREE HELPERS
════════════════════════════════════════════════ */
function reloadTree() {
    ajax('yzmf_get_tree', {}, data => {
        YZMF.tree = data;
        renderTree();
        refreshMoveFolderSelect();
    });
}

function flatTree(nodes, d = 0) {
    let r = [];
    (nodes || []).forEach(f => {
        r.push({ ...f, _d: d });
        if (f.children?.length) r = r.concat(flatTree(f.children, d + 1));
    });
    return r;
}

function addToTree(tree, folder, parentId) {
    const sortByName = arr => arr.slice().sort((a, b) =>
        (a.name || '').localeCompare(b.name || '', undefined, { sensitivity: 'base' })
    );
    if (!parentId) return sortByName([...tree, { ...folder, children: [] }]);
    return tree.map(f => {
        if (f.id === parentId) return { ...f, children: sortByName([...(f.children || []), { ...folder, children: [] }]) };
        if (f.children?.length) return { ...f, children: addToTree(f.children, folder, parentId) };
        return f;
    });
}

function removeFromTree(tree, id) {
    return tree.filter(f => f.id !== id)
               .map(f => ({ ...f, children: removeFromTree(f.children || [], id) }));
}

function updateTreeName(tree, id, name) {
    (tree || []).forEach(f => {
        if (f.id === id) f.name = name;
        if (f.children?.length) updateTreeName(f.children, id, name);
    });
}

function getFolderName(id) {
    if (id <= 0) return '';
    return flatTree(YZMF.tree).find(f => f.id === id)?.name || '';
}

/* ════════════════════════════════════════════════
   UTILITIES
════════════════════════════════════════════════ */
function ajax(action, data, cb, errCb) {
    $.post(YZMF.ajaxurl, { action, nonce: YZMF.nonce, ...data })
        .done(res => {
            if (res.success) {
                cb(res.data);
            } else {
                const msg = res.data?.message || 'Error';
                if (errCb) errCb(msg); else toast('⚠ ' + msg, 'err');
            }
        })
        .fail(() => {
            const msg = 'Error de conexión';
            if (errCb) errCb(msg); else toast('⚠ ' + msg, 'err');
        });
}

function toast(msg, type = 'info') {
    const $t = $(`<div class="yz-toast ${type}">${msg}</div>`).appendTo('body');
    setTimeout(() => $t.fadeOut(300, () => $t.remove()), 2800);
}

function esc(s) { return $('<div>').text(s || '').html(); }

function mimeIcon(mime) {
    if (!mime) return '📎';
    if (mime.startsWith('video/'))  return '🎬';
    if (mime.startsWith('audio/'))  return '🔊';
    if (mime === 'application/pdf') return '📄';
    if (mime.includes('word'))      return '📝';
    return '📎';
}

function hasFiles(e) {
    try {
        const types = e.originalEvent?.dataTransfer?.types || e.dataTransfer?.types || [];
        return Array.from(types).includes('Files');
    } catch { return false; }
}


/* ════════════════════════════════════════════════
   IA — GENERACIÓN DE ALT + CAPTION
════════════════════════════════════════════════ */

/**
 * Genera alt + caption para UNA imagen.
 * Llama al callback onSuccess(data) o onError(msg).
 */
function generateAISingle(imageId, onSuccess, onError) {
    ajax('yzmf_generate_ai_meta', { image_id: imageId }, data => {
        // Actualizar datos locales en la grid
        const local = App.images.find(i => i.id === imageId);
        if (local) {
            local.alt     = data.alt;
            local.caption = data.caption;
        }
        if (onSuccess) onSuccess(data);
    }, onError);
}

/**
 * Genera alt + caption para MÚLTIPLES imágenes en lote.
 * Muestra una barra de progreso en un toast.
 */
function generateAIBatch(imageIds) {
    if (!imageIds.length) return;

    const total   = imageIds.length;
    let done      = 0;
    let errors    = 0;

    // Toast de progreso persistente — id único para no chocar entre lotes simultáneos
    const uid     = 'yz-ai-progress-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
    const barId   = uid + '-bar';
    const textId  = uid + '-txt';
    const $progress = $(`
    <div class="yz-toast info" id="${uid}" style="pointer-events:auto;min-width:280px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <span>✨ Generando metadatos con IA</span>
        <button class="yz-ai-progress-close" data-target="${uid}" style="background:none;border:none;color:inherit;cursor:pointer;font-size:14px;padding:0 4px">✕</button>
      </div>
      <div style="background:rgba(255,255,255,.1);border-radius:4px;height:6px;overflow:hidden">
        <div id="${barId}" style="background:var(--accent);height:100%;width:0;transition:width .3s"></div>
      </div>
      <div id="${textId}" style="margin-top:6px;font-size:11px;opacity:.8">
        0 / ${total} imágenes…
      </div>
    </div>`).appendTo('body');

    $progress.find('.yz-ai-progress-close').on('click', function () {
        $('#' + $(this).data('target')).remove();
    });

    // Procesar en serie (para no saturar la API)
    const queue = [...imageIds];

    function processNext() {
        if (!queue.length) {
            // Finalizado
            const msg = errors > 0
                ? `✓ ${done} generadas, ${errors} errores`
                : `✓ ${done} imagen${done !== 1 ? 'es' : ''} procesada${done !== 1 ? 's' : ''}`;
            $progress.fadeOut(400, () => $progress.remove());
            toast(msg, errors > 0 ? 'err' : 'ok');
            deselectAll();
            return;
        }

        const id = queue.shift();
        $('#' + textId).text(`Procesando ${done + 1} / ${total}…`);

        generateAISingle(id,
            () => {
                done++;
                const pct = Math.round((done + errors) / total * 100);
                $('#' + barId).css('width', pct + '%');
                $('#' + textId).text(`${done + errors} / ${total} — ${pct}%`);
                processNext();
            },
            (err) => {
                errors++;
                const pct = Math.round((done + errors) / total * 100);
                $('#' + barId).css('width', pct + '%');
                processNext();
            }
        );
    }

    processNext();
}

})(jQuery);
