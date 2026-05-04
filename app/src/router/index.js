import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  { path: '/login',     name: 'login',     component: () => import('../views/Login.vue'),     meta: { public: true } },
  { path: '/',          name: 'dashboard', component: () => import('../views/Dashboard.vue') },
  { path: '/media',     name: 'media',     component: () => import('../views/Media.vue'), meta: { keepAlive: true } },
  { path: '/media/:id', name: 'media-detail', component: () => import('../views/MediaDetail.vue'), props: true },
  { path: '/folders',   name: 'folders',   component: () => import('../views/Folders.vue') },
  { path: '/exif',      name: 'exif',      component: () => import('../views/ExifStats.vue') },
  { path: '/upload',    name: 'upload',    component: () => import('../views/Upload.vue') },
  { path: '/portfolios', name: 'portfolios', component: () => import('../views/Portfolios.vue'), meta: { keepAlive: true } },
  { path: '/portfolios/categories', name: 'portfolio-categories', component: () => import('../views/PortfolioCategories.vue') },
  { path: '/portfolios/new', name: 'portfolio-new', component: () => import('../views/PortfolioCreate.vue') },
  { path: '/portfolios/:id', name: 'portfolio-detail', component: () => import('../views/PortfolioDetail.vue'), props: true },
  { path: '/map',       name: 'map',       component: () => import('../views/Map.vue') },
  { path: '/settings',  name: 'settings',  component: () => import('../views/Settings.vue') },
  { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    // Al volver atrás/adelante, restaurar posición previa (necesario para que
    // las vistas keepAlive recuperen el scroll donde estaban).
    if (savedPosition) return savedPosition;
    return { top: 0 };
  },
});

router.beforeEach((to) => {
  const auth = useAuthStore();
  if (!to.meta.public && !auth.isAuthed) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }
  if (to.name === 'login' && auth.isAuthed) {
    return { name: 'dashboard' };
  }
});

export default router;
