# Auditoría — hallazgos diferidos

Este documento lista los hallazgos de la auditoría profesional (commit del informe en el historial Claude) que **NO** se aplicaron en el sweep automático y por qué. Cada uno tiene "plan de ataque" para retomarlo cuando proceda.

Ordenados por la severidad original.

---

## ALTOS

### H-12 · Vistas Vue desbordadas (>600 LOC) — composables / split

**Estado:** diferido por scope.
**Por qué:** Media.vue (1076), MediaDetail.vue (640), Map.vue (632), PortfolioDetail.vue (628) mezclan estado, fetch, side-effects DOM y UI. Cada cambio arrastra acoplamiento.

**Plan:**
1. Empezar por **Media.vue** (mayor LOC y más manipulación DOM):
   - `useMediaSelectionUX(media, options)` → long-press, IntersectionObserver, bulk actions UI state.
   - `useFolderDnD(folders, media)` → drag&drop nativo.
   - `useMediaQuickFilters()` → preset + activeQuickFilter + sync URL.
   - Sheets a componentes con su propio estado (`MediaBulkActionsSheet`, `FolderCreatorInline`).
2. Map.vue: `useGeoSearch`, `useMapLayers(photos|portfolios|locations)`.
3. PortfolioDetail.vue: extraer la galería+drag a `<PortfolioGallery>`.

Requiere tests E2E previos para el flujo de Media (subida + selección + bulk action) para no romper nada al partir.

### H-13 · Schemas st1-st4 hardcodeados — data-driven schema

**Estado:** diferido por riesgo.
**Por qué:** las 4 funciones `schema_st1..st4` en class-portfolio-bridge.php son 600+ LOC mayoritariamente copy-paste. El audit señaló que st3 reutiliza `rnr_port_carousel_info_description_opt` que en realidad ya está en los 4 layouts. Sin saber qué consume el tema kotlis y sin tests, modificarlo puede romper portfolios en producción.

**Plan:**
1. Snapshot test: para cada layout, enumerar todas las meta keys efectivas y verificar que la actual implementación devuelve exactamente lo mismo que la nueva.
2. Modelar como datos en `plugin/data/portfolio-schema.php`:
   ```php
   return [
     'st1' => [ 'prefix' => 'rnr_portfolio_column_grid_', 'sections' => [...] ],
     'st2' => [...],
     'st3' => [...],
     'st4' => [...],
   ];
   ```
3. `render_schema($layout)` materializa los campos a partir del array.
4. Auditar si `rnr_port_carousel_info_description_opt` es genuinamente compartido (intencional) o un bug. Lo más seguro es **mantener la compartición actual** y solo prefijar para nuevas toggles.

### H-14 · static-only → instancias con DI

**Estado:** diferido por scope.
**Por qué:** las 14 clases del plugin son namespaces de funciones globales. Imposible mockear sin Brain Monkey. El cambio toca CADA fichero del plugin.

**Plan:**
1. Mantener `YZMF_Foo::method()` calls funcionando (BC).
2. Refactorizar a singleton:
   ```php
   class YZMF_REST {
     private static $instance;
     public static function instance() { return self::$instance ??= new self(); }
     public function register_routes() { ... }
   }
   ```
3. Cada call estático sigue siendo un wrapper:
   ```php
   public static function register_routes() { self::instance()->_register_routes(); }
   ```
4. Capa Repository (MediaRepo, PortfolioRepo) que aísla WP_Query/get_post_meta.
5. Inyectar repositorios en los services.

Hacerlo gradualmente, una clase por commit.

---

## MEDIOS

### M-10 · Tags IA → taxonomía `yzmf_ai_tag`

**Estado:** diferido por migración necesaria.
**Por qué:** hoy los tags viven en `_yzmf_ai_tags` (postmeta serializado). Hacer LIKE sobre eso es full-scan. Una taxonomía dedicada da JOIN indexado.

**Plan:**
1. Registrar `yzmf_ai_tag` taxonomy.
2. Migración: leer cada `_yzmf_ai_tags`, crear términos, `wp_set_object_terms`.
3. Mantener el meta como fallback en `generate_ai_for_image` (escribe en ambos) durante una versión.
4. Cambiar `list_media` filtro de tag a `tax_query`.
5. Después de N versiones, deprecar el meta.

### M-13 · Stats EXIF/colors/tags con cola en background

**Estado:** parcial (LIMIT 5000 aplicado).
**Por qué:** para >5000 imágenes hace falta procesar por batches via Action Scheduler.

**Plan:**
1. `composer require woocommerce/action-scheduler` (o usar `wp_cron` con offset).
2. Crear job `yzmf_recompute_stats` que procesa 500 attachments y avanza un cursor en `wp_options`.
3. Endpoint admin `/stats/refresh` que dispara el job.
4. El resultado final se guarda en `option` persistente (no transient — algunos hosts evictan transients agresivamente).

### M-15 / M-33 · Migración `window.confirm/prompt` → `ui.confirm/prompt`

**Estado:** infra lista (Sprint 3.3). Migración progresiva.
**Por qué:** hay ~26 callsites de `confirm()`/`prompt()` en views/components. Migrar todos a la API async cambia el flujo y hay que probarlos uno a uno.

**Plan:** ir migrando con cada PR que toque una vista. La API ya es `await ui.confirm({...})` así que es solo s/confirm(/await ui.confirm({message: /g (más cuidado en signatures).

### M-35 · `<button>` en items del grid Media

**Estado:** diferido.
**Por qué:** hoy MediaPicker usa `<div role="button" tabindex>` con handlers manuales (Enter/Space). Convertir a `<button>` real cambia el comportamiento de focus/scroll y puede romper el long-press. Hay que diseñarlo con cuidado.

**Plan:**
1. Refactorizar `Media.vue:469-497` (también `<button>` actual, pero sin `aria-label`).
2. Añadir `aria-label="Abrir imagen: {title}"` o `aria-pressed` en selectMode.
3. Hacer lo mismo en MediaPicker (sustituir div por button real).
4. Test E2E con teclado/lector de pantalla.

---

## BAJOS

### L-06 · Migrar deploy de FTP → SFTP/FTPS

**Estado:** diferido por dependencia externa.
**Por qué:** `deploy.ps1` y `deploy-plugin.ps1` usan FTP puro (puerto 21) con credenciales en `app/.vscode/sftp.json`. Hostinger soporta SFTP pero PowerShell estándar no lo trae nativo.

**Plan:** instalar `Posh-SSH` o usar WinSCP CLI. Reescribir las funciones `Upload-FtpFile`/`Ensure-FtpDir` con el nuevo cliente. Documentar en `docs/MULTI-EQUIPO.md`. Considerar Credential Manager o `.env` cifrado en lugar del JSON plano.

### L-07 · watch deep sobre locations.items

**Estado:** diferido.
**Por qué:** `watch(() => locations.items, () => renderMarkers(), { deep: true })` en Map.vue:204 dispara walk profundo en cada mutación.

**Plan:** Añadir un `_revision` numérico en el store de locations que se incremente al `load()`/save/delete. `watch(() => locations._revision, renderMarkers)`. Cambio aislado, sin riesgo, pero necesita pasada por todos los stores que se usan así.

### L-11 · console.warn/error sin guard

**Estado:** parcial (en Map.vue/Dashboard.vue se quitaron implícitamente al refactor de a11y). Quedan en otros sitios.

**Plan:** sweep `grep -rn "console\." app/src` y envolver en `if (import.meta.env.DEV)`. Borrar los que ya no aportan nada.

### L-13 · PortfolioMetaForm reactive

**Estado:** diferido.
**Por qué:** `values.value = { ...values.value, [key]: [...] }` reconstruye el objeto completo en cada `addRow/removeRow/moveRow`. Es subóptimo pero funciona.

**Plan:** convertir `values` de `ref({})` a `reactive({})` y mutar directamente: `values[field.key].push(row)`. Test manual del campo repeater de portfolios.

### L-15 · Idioma de comentarios

**Estado:** cosmético, defer.
**Por qué:** mezcla de español/inglés en comentarios de sección. No bloquea nada.

**Plan:** pasada cosmética con sed en algún momento. No urge.

### L-16 · `remove_filter` CORS dentro de condicional

**Estado:** funciona, defer.
**Por qué:** el patrón actual quita `rest_send_cors_headers` solo cuando el Origin coincide. Es correcto, pero hace que el estado del filter dependa del orden de requests. El patrón canónico WP sería hook único con check interno.

**Plan:** refactor a `add_filter('rest_pre_serve_request', ...)` con check de origin dentro. Cambio aislado.

### L-20 · Boilerplate try/await/ui.toast

**Estado:** diferido.
**Por qué:** 9 funciones en PortfolioDetail.vue siguen el patrón `try { ... } catch (e) { ui.toast(e.message, 'err'); } finally { flag.value = false; }`. Repetitivo pero claro.

**Plan:** helper `withFlag(flagRef, fn, { errPrefix='' })` que envuelva el patrón. Reducción de ~40% en líneas pero cambia el shape de las funciones.

### L-22 · Leaflet popup XSS — auditar bindPopup

**Estado:** sin uso de bindPopup identificado en el codebase actual; queda para vigilancia.

**Plan:** si en futuro se añaden popups con `.bindPopup(html)`, forzar `{ html: false }` o sanear con DOMPurify cuando la cadena venga de la API.

### L-23 · vite-plugin-pwa 1.x

**Estado:** diferido.
**Por qué:** salto major. Hay que validar que `generateSW` sigue funcionando con `runtimeCaching` y `navigationFallback`.

**Plan:** rama de prueba `npm i -D vite-plugin-pwa@^1.0`, build, smoke test en preview con devtools mirando Service Worker.

### L-24 · kotlis-plugin PUC v4

**Estado:** diferido.
**Por qué:** kotlis-plugin no es propio. El repo lo trackea como mirror del companion del tema, no se modifica desde aquí.

**Plan:** documentar en CLAUDE.md que kotlis-plugin/* es read-only desde este repo. Si en algún momento se decide forkear, migrar PUC a v5.

### L-29 · BottomNav 8 ítems en 360px

**Estado:** diferido por decisión de diseño.
**Por qué:** requiere rediseño del nav (overflow → "Más", o reducir items). Decisión del usuario.

**Plan:** si se decide reducir: 4-5 ítems prominentes + botón "Más" con menú desplegable o sheet con el resto. Posible refactor a `useNavigationItems` componable que separe principales/secundarios.

---

## Notas globales

- **CI activo en `.github/workflows/ci.yml`** — pwa (build+vitest+npm audit) y plugin (composer + phpunit unit). Los tests crecen por iteración.
- **Suite mínima de tests** en su sitio: 24 vitest tests + 2 PHPUnit unit. Cada PR puede añadir.
- **Tags-feature** y otros refactors grandes deben hacerse después de cubrir el área con tests.
- Para retomar un ítem diferido: copiar el "Plan" al issue/PR description y atacarlo.
