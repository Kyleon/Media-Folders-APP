/**
 * yzmf-slider - inicialización del slider con Swiper 11.
 * Cargado como módulo ES para no chocar con la versión de Swiper que el
 * tema YPVA registra en window.Swiper.
 *
 * Soporta tanto sliders presentes al cargar la página (frontend normal)
 * como sliders inyectados después por AJAX (preview de Elementor): un
 * MutationObserver inicializa los nuevos automáticamente.
 */

import Swiper from 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.mjs';

const initSlider = (root) => {
    if (!root || root._yzmfInitialized) return;
    root._yzmfInitialized = true;

    let settings = {};
    try {
        settings = JSON.parse(root.dataset.settings || '{}');
    } catch (e) {
        console.warn('yzmf-slider: settings JSON inválido', e);
    }

    const swiperEl = root.querySelector('.yzmf-swiper');
    if (!swiperEl) return;

    const navigationCfg = settings.navigation
        ? {
            prevEl: root.querySelector('.yzmf-nav-prev'),
            nextEl: root.querySelector('.yzmf-nav-next'),
        }
        : false;

    let paginationCfg = false;
    if (settings.pagination && settings.pagination !== 'none') {
        const el = root.querySelector('.yzmf-pagination');
        if (el) {
            paginationCfg = {
                el,
                clickable: true,
                type: settings.pagination === 'progress' ? 'progressbar' : 'bullets',
            };
        }
    }

    const autoplayCfg = settings.autoplay
        ? {
            delay: Number(settings.speed) || 6000,
            disableOnInteraction: false,
            pauseOnMouseEnter: false,
        }
        : false;

    const effect = settings.transition === 'fade' ? 'fade' : 'slide';

    const swiper = new Swiper(swiperEl, {
        loop: !!settings.loop,
        autoplay: autoplayCfg,
        effect,
        fadeEffect: { crossFade: true },
        speed: 600,
        navigation: navigationCfg,
        pagination: paginationCfg,
        on: {
            slideChange: (sw) => {
                root.querySelectorAll('.yzmf-slide-video').forEach((v) => {
                    try { v.pause(); v.currentTime = 0; } catch (_) {}
                });
                const active = sw.slides[sw.activeIndex];
                if (active) {
                    const v = active.querySelector('.yzmf-slide-video');
                    if (v) { try { v.play(); } catch (_) {} }
                }
            },
            init: (sw) => {
                const active = sw.slides[sw.activeIndex];
                if (active) {
                    const v = active.querySelector('.yzmf-slide-video');
                    if (v) { try { v.play(); } catch (_) {} }
                }
            },
        },
    });

    root._yzmfSwiper = swiper;
};

const initAll = () => {
    document.querySelectorAll('[data-yzmf-slider]').forEach(initSlider);
};

// Init inicial al cargar la página
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}

// Observar el DOM para sliders inyectados después (preview de Elementor,
// drag & drop del widget, navegaciones SPA, etc.). Marca los ya inicializados
// para no duplicar.
if (typeof MutationObserver !== 'undefined') {
    const mo = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (!(node instanceof HTMLElement)) continue;
                if (node.matches && node.matches('[data-yzmf-slider]')) {
                    initSlider(node);
                }
                node.querySelectorAll && node.querySelectorAll('[data-yzmf-slider]')
                    .forEach(initSlider);
            }
        }
    });
    mo.observe(document.body, { childList: true, subtree: true });
}

// Exponer un trigger global por si Elementor (u otro) quiere reinicializar
// manualmente tras una actualización de DOM.
window.yzmfInitSliders = initAll;
