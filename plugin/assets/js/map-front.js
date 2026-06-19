/* global YZMF_Map, L */
(function () {
'use strict';

const locMap = {};
let activePreview = null;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.yzmf-map-canvas').forEach(initMap);
});

function initMap(canvas) {
    // Capas a pintar: data-layers="locations|photos|both" (default both).
    const layers = canvas.dataset.layers || 'both';
    const wantLocations = layers === 'locations' || layers === 'both';
    const wantPhotos    = layers === 'photos'    || layers === 'both';

    const map = L.map(canvas, {
        center: [YZMF_Map.default_lat, YZMF_Map.default_lng],
        zoom:   YZMF_Map.default_zoom,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20,
    }).addTo(map);

    const preview = buildPreview();
    document.body.appendChild(preview);

    // Cerrar preview al clicar en el mapa (no en un pin)
    map.on('click', function () { closePreview(preview); });

    const restRoot = YZMF_Map.rest_root || '/wp-json/';

    // Capa de ubicaciones curadas (REST público y cacheable)
    if (wantLocations) {
        fetch(restRoot + 'yzmf/v1/map/data')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!Array.isArray(data)) return;
                data.forEach(function (loc) {
                    if (!loc.lat || !loc.lng) return;
                    locMap[loc.id] = loc;
                    addPin(map, loc, preview);
                });
            })
            .catch(console.error);
    }

    // Capa de fotos individuales geolocalizadas (clusterizada)
    if (wantPhotos) {
        fetch(restRoot + 'yzmf/v1/map/photos')
            .then(function (r) { return r.json(); })
            .then(function (photos) {
                if (!Array.isArray(photos) || !photos.length) return;
                addPhotoLayer(map, photos, preview);
            })
            .catch(console.error);
    }
}

/* ── Ubicaciones curadas ── */

function addPin(map, loc, preview) {
    // Usar CircleMarker de Leaflet — tiene eventos nativos fiables
    const circle = L.circleMarker([loc.lat, loc.lng], {
        radius:      7,
        color:       '#c8a97e',
        weight:      2,
        fillColor:   '#c8a97e',
        fillOpacity: 0.9,
        interactive: true,
        bubblingMouseEvents: false,
    }).addTo(map);

    circle.on('click', function (e) {
        L.DomEvent.stop(e);
        showPreview(preview, loc, eventFromLatLng(map, circle.getLatLng()));
    });

    // Hover styles
    circle.on('mouseover', function () {
        circle.setStyle({ radius: 10, color: '#fff', fillColor: '#fff' });
        map.getContainer().style.cursor = 'pointer';
    });
    circle.on('mouseout', function () {
        circle.setStyle({ radius: 7, color: '#c8a97e', fillColor: '#c8a97e' });
        map.getContainer().style.cursor = '';
    });
}

/* ── Fotos individuales (capa clusterizada) ── */

function addPhotoLayer(map, photos, preview) {
    // Si markercluster no cargó por lo que sea, degradamos a un layerGroup
    // plano para no dejar el mapa sin la capa de fotos.
    const cluster = (typeof L.markerClusterGroup === 'function')
        ? L.markerClusterGroup({
            chunkedLoading:             true,   // procesa en chunks, no bloquea el hilo
            spiderfyOnMaxZoom:          true,   // abre en abanico los markers exactos
            showCoverageOnHover:        false,
            zoomToBoundsOnClick:        true,
            maxClusterRadius:           50,
            spiderfyDistanceMultiplier: 1.4,
            disableClusteringAtZoom:    19,
            iconCreateFunction: function (c) {
                const n = c.getChildCount();
                const size = n < 10 ? 'sm' : (n < 100 ? 'md' : 'lg');
                return L.divIcon({
                    html: '<div class="yz-cluster yz-cluster-' + size + '"><span>' + n + '</span></div>',
                    className: 'yz-cluster-wrap',
                    iconSize: L.point(40, 40),
                });
            },
        })
        : L.layerGroup();

    const dotIcon = L.divIcon({
        className: '',
        html: '<div class="yz-photo-pin-front"></div>',
        iconSize: [14, 14], iconAnchor: [7, 7],
    });

    const markers = [];
    photos.forEach(function (p) {
        if (!isFinite(p.lat) || !isFinite(p.lng)) return;
        const m = L.marker([p.lat, p.lng], { icon: dotIcon });
        m.on('click', function (e) {
            L.DomEvent.stop(e);
            showPhotoPreview(preview, p, eventFromLatLng(map, m.getLatLng()));
        });
        markers.push(m);
    });

    // addLayers (bulk) en markercluster es mucho más rápido que N addLayer.
    if (typeof cluster.addLayers === 'function') cluster.addLayers(markers);
    else markers.forEach(function (m) { cluster.addLayer(m); });

    cluster.addTo(map);
}

/* ── Preview compartido ── */

function buildPreview() {
    const el = document.createElement('div');
    el.className = 'yzm-preview';
    el.innerHTML =
        '<button class="yzm-prev-close" type="button">✕</button>' +
        '<div class="yzm-prev-img-wrap" id="yzm-pv-img"></div>' +
        '<div class="yzm-prev-body">' +
          '<div class="yzm-prev-tag"  id="yzm-pv-tag"></div>' +
          '<div class="yzm-prev-name" id="yzm-pv-name"></div>' +
          '<div class="yzm-prev-desc" id="yzm-pv-desc"></div>' +
          '<div class="yzm-prev-thumbs" id="yzm-pv-thumbs"></div>' +
          '<div class="yzm-prev-footer">' +
            '<span class="yzm-prev-count" id="yzm-pv-count"></span>' +
            '<a class="yzm-prev-link" id="yzm-pv-link" href="#" target="_blank">Ver galería →</a>' +
          '</div>' +
        '</div>';

    el.querySelector('.yzm-prev-close').addEventListener('click', function () {
        closePreview(el);
    });

    return el;
}

function showPreview(el, loc, evt) {
    activePreview = el;

    const imgWrap = el.querySelector('#yzm-pv-img');
    imgWrap.innerHTML = loc.hero
        ? '<img class="yzm-prev-img" src="' + esc(loc.hero) + '" alt="">'
        : '<div class="yzm-prev-noimg">📍</div>';

    el.querySelector('#yzm-pv-tag').textContent   = loc.tag         || '';
    el.querySelector('#yzm-pv-name').textContent  = loc.name        || '';
    el.querySelector('#yzm-pv-desc').textContent  = loc.description || '';
    el.querySelector('#yzm-pv-desc').style.display = '';
    el.querySelector('#yzm-pv-count').textContent = (loc.count || 0) + ' fotografías';

    const link = el.querySelector('#yzm-pv-link');
    link.textContent   = 'Ver galería →';
    link.href          = loc.gallery_url || '#';
    link.style.display = loc.gallery_url ? '' : 'none';

    el.querySelector('#yzm-pv-thumbs').innerHTML = (loc.thumbs || []).slice(0, 4).map(function (t) {
        return '<img class="yzm-prev-thumb" src="' + esc(t) + '" alt="" loading="lazy">';
    }).join('');

    positionPreview(el, evt);
}

function showPhotoPreview(el, photo, evt) {
    activePreview = el;

    const imgWrap = el.querySelector('#yzm-pv-img');
    const src = photo.medium || photo.thumb || photo.full || '';
    imgWrap.innerHTML = src
        ? '<img class="yzm-prev-img" src="' + esc(src) + '" alt="' + esc(photo.alt || '') + '" loading="lazy">'
        : '<div class="yzm-prev-noimg">📷</div>';

    el.querySelector('#yzm-pv-tag').textContent   = photo.place || '';
    el.querySelector('#yzm-pv-name').textContent  = photo.title || '';
    // Sin descripción ni thumbs ni contador para una foto suelta.
    const desc = el.querySelector('#yzm-pv-desc');
    desc.textContent = ''; desc.style.display = 'none';
    el.querySelector('#yzm-pv-thumbs').innerHTML = '';
    el.querySelector('#yzm-pv-count').textContent = '';

    const link = el.querySelector('#yzm-pv-link');
    link.textContent   = 'Ver foto →';
    link.href          = photo.full || src || '#';
    link.style.display = (photo.full || src) ? '' : 'none';

    positionPreview(el, evt);
}

// Posiciona la tarjeta junto al pin, ajustada al viewport.
function positionPreview(el, evt) {
    const W = 290, H = 370, M = 12;
    const vw = window.innerWidth, vh = window.innerHeight;
    let x = evt.clientX + 18;
    let y = evt.clientY - Math.round(H / 2);

    if (x + W > vw - M) x = evt.clientX - W - 12;
    if (y < M)          y = M;
    if (y + H > vh - M) y = vh - H - M;

    el.style.left = x + 'px';
    el.style.top  = y + 'px';
    el.classList.add('open');
}

// Construye un evento sintético (clientX/Y) a partir de una latlng del mapa,
// para reutilizar el posicionamiento de la tarjeta tanto en pins como en fotos.
function eventFromLatLng(map, latlng) {
    const cp = map.latLngToContainerPoint(latlng);
    const r  = map.getContainer().getBoundingClientRect();
    return { clientX: r.left + cp.x, clientY: r.top + cp.y };
}

function closePreview(el) {
    if (el) el.classList.remove('open');
    activePreview = null;
}

function esc(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

})();
