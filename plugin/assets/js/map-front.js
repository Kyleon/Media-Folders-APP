/* global YZMF_Map, L */
(function () {
'use strict';

const locMap = {};
let activePreview = null;

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.yzmf-map-canvas').forEach(initMap);
});

function initMap(canvas) {
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

    // Cargar ubicaciones y añadir pins (REST público y cacheable)
    var url = (YZMF_Map.rest_root || '/wp-json/') + 'yzmf/v1/map/data';
    fetch(url)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            // El endpoint REST devuelve directamente el array
            if (!Array.isArray(data)) return;
            data.forEach(function (loc) {
                if (!loc.lat || !loc.lng) return;
                locMap[loc.id] = loc;
                addPin(map, loc, preview);
            });
        })
        .catch(console.error);
}

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
        const containerPoint = map.latLngToContainerPoint(circle.getLatLng());
        const mapRect = map.getContainer().getBoundingClientRect();
        const fakeEvt = {
            clientX: mapRect.left + containerPoint.x,
            clientY: mapRect.top  + containerPoint.y,
        };
        showPreview(preview, loc, fakeEvt);
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

    // Cerrar preview al clicar en el mapa (no en un pin)
    map.on('click', function () { closePreview(preview); });
}

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
    el.querySelector('#yzm-pv-count').textContent = (loc.count || 0) + ' fotografías';

    const link = el.querySelector('#yzm-pv-link');
    link.href          = loc.gallery_url || '#';
    link.style.display = loc.gallery_url ? '' : 'none';

    el.querySelector('#yzm-pv-thumbs').innerHTML = (loc.thumbs || []).slice(0, 4).map(function (t) {
        return '<img class="yzm-prev-thumb" src="' + esc(t) + '" alt="" loading="lazy">';
    }).join('');

    // Posición junto al pin, ajustada al viewport
    var W = 290, H = 370, M = 12;
    var vw = window.innerWidth, vh = window.innerHeight;
    var x = evt.clientX + 18;
    var y = evt.clientY - Math.round(H / 2);

    if (x + W > vw - M) x = evt.clientX - W - 12;
    if (y < M)          y = M;
    if (y + H > vh - M) y = vh - H - M;

    el.style.left = x + 'px';
    el.style.top  = y + 'px';
    el.classList.add('open');
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
