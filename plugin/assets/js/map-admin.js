/* global YZMF_Map, jQuery, L */
(function ($) {
'use strict';

let map, markers = {}, newMarker = null;
let locations = [], folders = [];
let currentId = null;
let currentTab = 'locations'; // 'locations' | 'edit'

$(function () {
    positionMapApp();
    buildApp();
    initLeaflet();
    loadLocations();
    loadFolders();
});

/* ════════════════════════════════════════════════
   POSICIONAMIENTO — igual que el app principal
════════════════════════════════════════════════ */
function positionMapApp() {
    function update() {
        const app       = document.getElementById('yzmf-map-app');
        const wpcontent = document.getElementById('wpcontent');
        const adminbar  = document.getElementById('wpadminbar');
        if (!app || !wpcontent) return;
        const left = Math.round(wpcontent.getBoundingClientRect().left);
        const top  = adminbar ? adminbar.offsetHeight : 32;
        app.style.top    = top  + 'px';
        app.style.left   = left + 'px';
        app.style.right  = '0px';
        app.style.bottom = '0px';
    }
    update();
    setTimeout(update, 50);
    setTimeout(update, 250);
    const mo = new MutationObserver(() => setTimeout(update, 60));
    mo.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    window.addEventListener('resize', update);
    document.addEventListener('click', e => { if (e.target.closest('#collapse-button')) setTimeout(update, 300); });
}

/* ════════════════════════════════════════════════
   BUILD APP
════════════════════════════════════════════════ */
function buildApp() {
    $('#yzmf-map-app').html(`
    <div id="yzmf-map-toolbar">
      <div class="yzm-tb-title">🗺 Mapa fotográfico <span>Mis Medios</span></div>
      <div class="yzm-spacer"></div>
      <button class="yzm-btn pri" id="yzm-new-btn">+ Nueva ubicación</button>
    </div>
    <div id="yzmf-map-body">

      <!-- MAPA -->
      <div id="yzmf-map-pane">
        <div id="yzmf-leaflet-map"></div>
        <div class="yzm-map-hint" id="yzm-hint">Haz clic en el mapa o selecciona una ubicación</div>
      </div>

      <!-- PANEL DERECHO -->
      <div id="yzmf-map-panel">

        <!-- Lista de ubicaciones -->
        <div id="yzm-panel-locations" style="display:flex;flex-direction:column;flex:1;overflow:hidden;min-height:0">
          <div class="yzm-panel-header">
            <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#5a5a5a">Ubicaciones</span>
            <span id="yzm-loc-count" style="font-size:10px;color:#3a3a3a"></span>
          </div>
          <div id="yzm-loc-list"></div>
        </div>

        <!-- Formulario de edición -->
        <div id="yzm-edit-form">
          <div class="yzm-form-header">
            <span class="yzm-form-title" id="yzm-form-title">Nueva ubicación</span>
            <button class="yzm-close-btn" id="yzm-close-btn">✕</button>
          </div>
          <div class="yzm-form-body">
            <input type="hidden" id="yzm-id">

            <div class="yzm-field">
              <label>Buscar lugar</label>
              <div class="yzm-search-wrap">
                <input type="text" id="yzm-place-search" placeholder="🔍 Ciudad, monumento, lugar…" autocomplete="off">
                <div id="yzm-search-results"></div>
              </div>
              <div id="yzm-geo-status"></div>
              <div id="yzm-coords-display"></div>
              <input type="hidden" id="yzm-lat">
              <input type="hidden" id="yzm-lng">
            </div>

            <div class="yzm-field">
              <label>Nombre *</label>
              <input type="text" id="yzm-name" maxlength="80" placeholder="Nombre de la ubicación">
            </div>

            <div class="yzm-field">
              <label>Etiqueta</label>
              <input type="text" id="yzm-tag" placeholder="Paisaje · Viajes…">
            </div>

            <div class="yzm-field">
              <label>Descripción</label>
              <textarea id="yzm-desc" rows="2" placeholder="Descripción breve"></textarea>
            </div>

            <div class="yzm-field">
              <label>URL galería</label>
              <input type="url" id="yzm-url" placeholder="https://…">
            </div>

            <div class="yzm-field">
              <label>Imagen de portada</label>
              <div class="yzm-hero-wrap">
                <div class="yzm-hero-preview" id="yzm-hero-preview">Sin imagen</div>
                <button type="button" class="yzm-btn" id="yzm-hero-btn" style="width:100%">Elegir imagen</button>
                <input type="hidden" id="yzm-hero-id">
              </div>
            </div>

            <div class="yzm-field">
              <label>Carpetas vinculadas</label>
              <div class="yzm-folders" id="yzm-folders">
                <span style="color:#3a3a3a;font-size:12px">Cargando carpetas…</span>
              </div>
            </div>

            <div class="yzm-field">
              <label>Fotos individuales</label>
              <div id="yzm-photos-grid"></div>
              <button type="button" class="yzm-btn" id="yzm-photos-btn" style="width:100%;margin-top:6px">
                + Añadir fotos
              </button>
              <input type="hidden" id="yzm-photo-ids">
            </div>
          </div>

          <div class="yzm-form-footer">
            <button class="yzm-btn pri" id="yzm-save-btn">💾 Guardar</button>
            <button class="yzm-btn danger" id="yzm-delete-btn" style="display:none">🗑 Eliminar</button>
          </div>
        </div>

      </div>
    </div>`);

    bindUI();
}

/* ════════════════════════════════════════════════
   LEAFLET
════════════════════════════════════════════════ */
function initLeaflet() {
    map = L.map('yzmf-leaflet-map', {
        center: [YZMF_Map.default_lat, YZMF_Map.default_lng],
        zoom:    YZMF_Map.default_zoom,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20,
    }).addTo(map);

    map.on('click', function (e) {
        if ($('#yzm-edit-form').hasClass('visible')) {
            const {lat, lng} = e.latlng;
            setCoords(lat, lng);
            placeNewMarker(lat, lng);
            reverseGeocode(lat, lng);
        }
    });
}

function pinIcon(active) {
    return L.divIcon({
        className: '',
        html: `<div class="yzm-pin${active ? ' active' : ''}"></div>`,
        iconSize: [14, 14], iconAnchor: [7, 7],
    });
}

function newPinIcon() {
    return L.divIcon({
        className: '',
        html: '<div class="yzm-pin-new"></div>',
        iconSize: [16, 16], iconAnchor: [8, 8],
    });
}

function placeNewMarker(lat, lng) {
    if (newMarker) newMarker.remove();
    newMarker = L.marker([lat, lng], { icon: newPinIcon(), draggable: true }).addTo(map);
    newMarker.on('dragend', function () {
        const pos = newMarker.getLatLng();
        setCoords(pos.lat, pos.lng);
        reverseGeocode(pos.lat, pos.lng);
    });
}

function addLocationMarker(loc) {
    if (!loc.lat || !loc.lng) return;
    if (markers[loc.id]) markers[loc.id].remove();
    const m = L.marker([loc.lat, loc.lng], { icon: pinIcon(false) }).addTo(map);
    m.on('click', () => openLocation(loc.id));
    markers[loc.id] = m;
}

function setActiveMarker(id) {
    Object.entries(markers).forEach(([mid, m]) => m.setIcon(pinIcon(parseInt(mid) === id)));
}

/* ════════════════════════════════════════════════
   GEOCODING (Nominatim — gratuito, sin API key)
════════════════════════════════════════════════ */
function reverseGeocode(lat, lng) {
    $('#yzm-geo-status').text('🔍 Buscando…').css('color', '#5a5a5a');

    const url = (YZMF_Map.rest_root || '/wp-json/') + 'yzmf/v1/geocode/reverse?lat=' + lat + '&lng=' + lng;
    fetch(url, { headers: { 'X-WP-Nonce': YZMF_Map.rest_nonce || '' } })
        .then(r => r.json())
        .then(data => {
            if (!data || data.error) { $('#yzm-geo-status').text(''); return; }
            const a = data.address || {};

            const place   = a.tourism || a.natural || a.leisure || a.amenity || a.village || a.town || '';
            const city    = a.city || a.town || a.village || a.municipality || '';
            const country = a.country || '';
            const tag     = [city, country].filter(Boolean).join(', ');

            if (!$('#yzm-name').val().trim()) {
                $('#yzm-name').val([place, city].filter(Boolean).join(', ') || tag);
            }
            if (tag && !$('#yzm-tag').val().trim()) $('#yzm-tag').val(tag);

            const short = data.display_name.split(',').slice(0, 3).join(',');
            $('#yzm-geo-status').text('📍 ' + short).css('color', '#4caf7d');
            setTimeout(() => $('#yzm-geo-status').text(''), 5000);
        })
        .catch(() => $('#yzm-geo-status').text(''));
}

function searchPlace(q) {
    const url = (YZMF_Map.rest_root || '/wp-json/') + 'yzmf/v1/geocode/search?q=' + encodeURIComponent(q);
    fetch(url, { headers: { 'X-WP-Nonce': YZMF_Map.rest_nonce || '' } })
        .then(r => r.json())
        .then(results => {
            const $res = $('#yzm-search-results').empty();
            if (!results.length) { $res.hide(); return; }
            results.forEach(r => {
                const label = r.display_name.split(',').slice(0, 3).join(',');
                $('<div class="yzm-search-result"></div>').text(label)
                    .on('click', function () {
                        const lat = parseFloat(r.lat), lng = parseFloat(r.lon);
                        setCoords(lat, lng);
                        placeNewMarker(lat, lng);
                        map.flyTo([lat, lng], 10, { duration: .8 });
                        reverseGeocode(lat, lng);
                        $('#yzm-place-search').val(label);
                        $res.hide();
                    })
                    .appendTo($res);
            });
            $res.show();
        })
        .catch(() => {});
}

function setCoords(lat, lng) {
    $('#yzm-lat').val(lat.toFixed(6));
    $('#yzm-lng').val(lng.toFixed(6));
    $('#yzm-coords-display').text(lat.toFixed(5) + ', ' + lng.toFixed(5));
}

/* ════════════════════════════════════════════════
   DATA
════════════════════════════════════════════════ */
function loadLocations() {
    ajax('yzmf_map_get_locations', {}, data => {
        locations = data;
        renderLocationList();
        locations.forEach(addLocationMarker);
    });
}

function loadFolders() {
    ajax('yzmf_get_tree', {}, data => {
        folders = flatTree(data);
    });
}

function renderLocationList() {
    const $list = $('#yzm-loc-list').empty();
    $('#yzm-loc-count').text(locations.length + ' ubicaciones');

    if (!locations.length) {
        $list.html('<div style="padding:30px;text-align:center;color:#3a3a3a;font-size:12px">Sin ubicaciones.<br>Crea una con el botón superior.</div>');
        return;
    }

    locations.forEach(loc => {
        const $item = $(`
        <div class="yzm-loc-item${loc.id === currentId ? ' active' : ''}" data-id="${loc.id}">
          ${loc.hero_url
            ? `<img class="yzm-loc-thumb" src="${esc(loc.hero_url)}" alt="">`
            : `<div class="yzm-loc-icon">📍</div>`}
          <div class="yzm-loc-info">
            <div class="yzm-loc-name">${esc(loc.name)}</div>
            <div class="yzm-loc-sub">
              <span>${esc(loc.tag || '—')}</span>
              ${loc.count ? `<span class="yzm-loc-count">${loc.count} fotos</span>` : ''}
            </div>
          </div>
        </div>`);
        $item.on('click', () => openLocation(loc.id));
        $list.append($item);
    });
}

/* ════════════════════════════════════════════════
   OPEN / NEW / CLOSE
════════════════════════════════════════════════ */
function openLocation(id) {
    const loc = locations.find(l => l.id === id);
    if (!loc) return;
    currentId = id;

    showEditForm();
    $('#yzm-form-title').text(loc.name);
    $('#yzm-id').val(loc.id);
    $('#yzm-name').val(loc.name);
    $('#yzm-tag').val(loc.tag || '');
    $('#yzm-desc').val(loc.description || '');
    $('#yzm-url').val(loc.gallery_url || '');
    $('#yzm-lat').val(loc.lat);
    $('#yzm-lng').val(loc.lng);
    $('#yzm-coords-display').text(parseFloat(loc.lat).toFixed(5) + ', ' + parseFloat(loc.lng).toFixed(5));
    $('#yzm-hero-id').val(loc.hero_id || '');
    $('#yzm-hero-preview').html(loc.hero_url ? `<img src="${esc(loc.hero_url)}" alt="">` : 'Sin imagen');
    $('#yzm-place-search').val('');
    $('#yzm-geo-status').text('');
    $('#yzm-delete-btn').show();

    renderFolderChecks(loc.folder_ids || []);
    renderPhotoGrid(loc.photo_ids || [], loc.photo_thumbs || []);
    setActiveMarker(id);
    placeNewMarker(loc.lat, loc.lng);
    map.flyTo([loc.lat, loc.lng], Math.max(map.getZoom(), 7), { duration: .7 });

    $('.yzm-loc-item').removeClass('active');
    $(`.yzm-loc-item[data-id="${id}"]`).addClass('active');
    $('#yzm-hint').text('Arrastra el pin para reposicionar');
}

function newLocation() {
    currentId = null;
    showEditForm();
    $('#yzm-form-title').text('Nueva ubicación');
    $('#yzm-id').val('');
    $('#yzm-name,#yzm-tag,#yzm-desc,#yzm-url,#yzm-lat,#yzm-lng,#yzm-place-search').val('');
    $('#yzm-hero-id').val('');
    $('#yzm-hero-preview').text('Sin imagen');
    $('#yzm-coords-display,#yzm-geo-status').text('');
    $('#yzm-delete-btn').hide();
    renderFolderChecks([]);
    renderPhotoGrid([]);
    if (newMarker) newMarker.remove();
    setActiveMarker(null);
    $('.yzm-loc-item').removeClass('active');
    $('#yzm-hint').text('Haz clic en el mapa o busca un lugar');
}

function closeEdit() {
    $('#yzm-edit-form').removeClass('visible');
    $('#yzm-panel-locations').show();
    if (newMarker) newMarker.remove();
    setActiveMarker(null);
    $('.yzm-loc-item').removeClass('active');
    currentId = null;
    $('#yzm-hint').text('Haz clic en el mapa o selecciona una ubicación');
}

function showEditForm() {
    $('#yzm-panel-locations').hide();
    $('#yzm-edit-form').addClass('visible');
}

function renderFolderChecks(selected) {
    const $c = $('#yzm-folders').empty();
    if (!folders.length) {
        $c.html('<span style="color:#3a3a3a;font-size:12px">No hay carpetas (activa YZ Media Folders)</span>');
        return;
    }
    folders.forEach(f => {
        $c.append($(`
        <label class="yzm-folder-check">
          <input type="checkbox" value="${f.id}" ${selected.includes(f.id) ? 'checked' : ''}>
          <span>${'&nbsp;&nbsp;'.repeat(f._d)}📁 ${esc(f.name)} <small style="color:#3a3a3a">(${f.count})</small></span>
        </label>`));
    });
}

/* ════════════════════════════════════════════════
   SAVE / DELETE
════════════════════════════════════════════════ */
function saveLocation() {
    const lat = $('#yzm-lat').val();
    const lng = $('#yzm-lng').val();
    const name = $('#yzm-name').val().trim();

    if (!name)      { toast('El nombre es obligatorio', 'err'); return; }
    if (!lat || !lng) { toast('Haz clic en el mapa para colocar el pin', 'err'); return; }

    const $btn = $('#yzm-save-btn').prop('disabled', true).text('Guardando…');
    const folder_ids = $('#yzm-edit-form input[type="checkbox"]:checked').map(function () { return $(this).val(); }).get();

    ajax('yzmf_map_save_location', {
        id: $('#yzm-id').val(), name, lat, lng,
        tag:         $('#yzm-tag').val(),
        description: $('#yzm-desc').val(),
        gallery_url: $('#yzm-url').val(),
        hero_id:     $('#yzm-hero-id').val(),
        folder_ids,
    }, res => {
        toast('✓ Guardado', 'ok');
        $btn.prop('disabled', false).text('💾 Guardar');
        // Actualizar marker
        if (markers[parseInt($('#yzm-id').val())]) markers[parseInt($('#yzm-id').val())].remove();
        loadLocations(); // recarga lista y marcadores
        // Mantener formulario abierto con el id actualizado
        $('#yzm-id').val(res.id);
        currentId = res.id;
        $('#yzm-delete-btn').show();
    });
}

function deleteLocation() {
    if (!currentId) return;
    if (!confirm('¿Eliminar esta ubicación?')) return;
    ajax('yzmf_map_delete_location', { id: currentId }, () => {
        toast('Eliminada', 'ok');
        if (markers[currentId]) { markers[currentId].remove(); delete markers[currentId]; }
        locations = locations.filter(l => l.id !== currentId);
        closeEdit();
        renderLocationList();
    });
}

/* ════════════════════════════════════════════════
   BIND UI
════════════════════════════════════════════════ */
function bindUI() {
    $('#yzm-new-btn').on('click', newLocation);
    $('#yzm-close-btn').on('click', closeEdit);
    $('#yzm-save-btn').on('click', saveLocation);
    $('#yzm-delete-btn').on('click', deleteLocation);

    // Media picker — portada
    $('#yzm-hero-btn').on('click', function () {
        const frame = wp.media({ title: 'Imagen de portada', button: { text: 'Usar esta' }, multiple: false });
        frame.on('select', function () {
            const att = frame.state().get('selection').first().toJSON();
            $('#yzm-hero-id').val(att.id);
            $('#yzm-hero-preview').html(`<img src="${att.sizes?.thumbnail?.url || att.url}" alt="">`);
        });
        frame.open();
    });

    // Media picker — fotos individuales (selección múltiple)
    $('#yzmf-map-panel').on('click', '#yzm-photos-btn', function () {
        const frame = wp.media({
            title:    'Seleccionar fotos',
            button:   { text: 'Añadir fotos seleccionadas' },
            multiple: true,
        });
        frame.on('select', function () {
            const selection = frame.state().get('selection').toJSON();
            const currentIds = ($('#yzm-photo-ids').val() || '').split(',').filter(Boolean).map(Number);
            selection.forEach(att => {
                if (!currentIds.includes(att.id)) currentIds.push(att.id);
            });
            $('#yzm-photo-ids').val(currentIds.join(','));
            // Construimos thumbs a partir de la selección del media picker para no pedir REST
            const picked = selection.map(att => ({ id: att.id, url: att.sizes?.thumbnail?.url || att.url }));
            renderPhotoGrid(currentIds, picked);
        });
        frame.open();
    });

    // Eliminar foto individual
    $('#yzmf-map-panel').on('click', '.yzm-photo-remove', function () {
        const id = parseInt($(this).data('id'));
        const ids = ($('#yzm-photo-ids').val() || '').split(',').filter(Boolean).map(Number).filter(x => x !== id);
        $('#yzm-photo-ids').val(ids.join(','));
        // Conservamos los thumbs ya cargados en el DOM
        const remaining = ids.map(pid => ({
            id: pid,
            url: $(`.yzm-photo-item[data-id="${pid}"] img`).attr('src') || '',
        }));
        renderPhotoGrid(ids, remaining);
    });

    // Búsqueda de lugar
    let st;
    $('#yzm-place-search').on('input', function () {
        clearTimeout(st);
        const q = $(this).val().trim();
        if (q.length < 3) { $('#yzm-search-results').hide(); return; }
        st = setTimeout(() => searchPlace(q), 380);
    }).on('blur', function () {
        setTimeout(() => $('#yzm-search-results').hide(), 200);
    });

    // Escape para cerrar
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#yzm-edit-form').hasClass('visible')) closeEdit();
    });
}

/* ════════════════════════════════════════════════
   PHOTO GRID
════════════════════════════════════════════════ */
function renderPhotoGrid(photoIds, photoThumbs) {
    const $grid = $('#yzm-photos-grid').empty();
    $('#yzm-photo-ids').val(photoIds.join(','));

    if (!photoIds.length) {
        $grid.html('<div style="color:#3a3a3a;font-size:11px;padding:4px 0">Sin fotos individuales</div>');
        return;
    }

    const thumbMap = {};
    (photoThumbs || []).forEach(t => { thumbMap[t.id] = t.url; });

    photoIds.forEach(id => {
        const src = thumbMap[id] || '';
        const $item = $(`
        <div class="yzm-photo-item" data-id="${id}">
          <img src="${esc(src)}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:3px">
          <button type="button" class="yzm-photo-remove" data-id="${id}" title="Quitar">✕</button>
        </div>`);

        // Si el thumb no vino precalculado (foto recién añadida), pedirlo al REST
        if (!src) {
            fetch(`${window.location.origin}/wp-json/wp/v2/media/${id}?_fields=id,source_url,media_details`)
                .then(r => r.json())
                .then(data => {
                    const thumb = data.media_details?.sizes?.thumbnail?.source_url || data.source_url;
                    if (thumb) $item.find('img').attr('src', thumb);
                })
                .catch(() => {});
        }

        $grid.append($item);
    });
}

/* ════════════════════════════════════════════════
   UTILS
════════════════════════════════════════════════ */
function ajax(action, data, cb) {
    $.post(YZMF_Map.ajaxurl, { action, nonce: YZMF_Map.nonce, ...data })
        .done(res => { if (res.success) cb(res.data); else toast(res.data?.message || 'Error', 'err'); })
        .fail(() => toast('Error de conexión', 'err'));
}

function toast(msg, type = 'info') {
    const $t = $(`<div class="yzm-toast ${type}">${msg}</div>`).appendTo('body');
    setTimeout(() => $t.fadeOut(300, () => $t.remove()), 2600);
}

function esc(s) { return $('<div>').text(s || '').html(); }

function flatTree(nodes, d = 0) {
    let r = [];
    (nodes || []).forEach(f => {
        r.push({ ...f, _d: d });
        if (f.children?.length) r = r.concat(flatTree(f.children, d + 1));
    });
    return r;
}

})(jQuery);
