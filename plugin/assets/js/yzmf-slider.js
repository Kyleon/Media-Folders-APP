/**
 * yzmf-slider - inicialización del slider con Swiper 11.
 * Cargado como módulo ES para no chocar con la versión de Swiper que el
 * tema YPVA registra en window.Swiper.
 */

import Swiper from 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.mjs';

const initSlider = (root) => {
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
        // Pausar vídeos no activos para ahorrar batería/CPU
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}
