# Project context for Claude

This repo is the full stack of **yezraelperez.es** (fotógrafo). Tres piezas
acopladas:

1. **`plugin/`** — Plugin WordPress propio `yz-media-folders` (PHP). Carpetas de
   medios, REST API, IA alt+caption, mapa, geo, bridge con kotlis portfolios.
2. **`app/`** — PWA Vue 3 + Vite (panel admin móvil/desktop). Consume el REST
   del plugin. Despliega como `app.yezraelperez.es`.
3. **`wp/`** — Espejo del WP local (`C:\dev\proyectos\yezraelperez.es`):
   - `wp/themes/ypva` (parent), `wp/themes/ypva-child` (custom).
   - `wp/plugins/kotlis-plugin` (companion del tema).

   La fuente de verdad es el repo. `scripts/sync-wp.ps1` mueve cambios entre
   repo y WP local (push/pull/diff). El WP local es el playground; producción
   es Hostinger via SFTP.

## Convenciones que importan

- **Stack**: WordPress 6.x + PHP 7.4+ / Vue 3 (script setup, Composition API) +
  Pinia + vue-router + Vite 6 + vite-plugin-pwa + Leaflet + vuedraggable.
- **Idioma del producto**: español. Comentarios y UI en español. Identificadores
  de código en inglés.
- **Estilo Vue**: SFCs con `<script setup>`. Prefiere `<style scoped>`. Tokens
  CSS via custom properties en `app/src/styles/`.
- **REST namespace**: `yzmf/v1` (todo lo del plugin). Auth: WordPress
  Application Passwords (Basic Auth).
- **Theme bridge**: el plugin lee/escribe meta keys del tema kotlis (`rnr_*`)
  para no obligar a editar el portfolio en el editor de WP.
- **Layouts kotlis**: st1=Column Grid Sidebar, st2=Carousel, st3=Full Width,
  st4=Slider. Cada uno tiene su propio meta key de galería.

## Despliegue

- **Plugin propio + theme + theme-plugin**: SFTP a Hostinger
  (`app/.vscode/sftp.json` con credenciales).
- **PWA**: `app/.\deploy.ps1` hace `npm run build` + sube `dist/` por FTP.

## Multi-equipo

El usuario trabaja desde dos máquinas (desktop + portátil) sincronizando el
chat de Claude Code via Dropbox (junction `~/.claude/projects/...` →
Dropbox). Ver `docs/MULTI-EQUIPO.md` y `scripts/setup-laptop.ps1`.

## Cosas que NO hay que hacer

- No editar directamente `C:\dev\proyectos\yezraelperez.es\wp-content\...` para
  cambios que deban persistir; edita en `wp/` y sincroniza.
- No commitear `app/.vscode/sftp.json` (tiene la password). Está gitignored.
- No tocar `plugin/` para cosas del theme — para eso está `wp/themes/ypva-child`.
