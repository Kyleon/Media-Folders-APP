# YZ Media Folders

Gestor de medios con carpetas, drag & drop, mapa fotográfico, generación automática de alt+caption con IA (Claude), **sliders configurables** desde la PWA (con shortcode + widget Elementor) y **REST API completa** para consumirlo desde aplicaciones externas (móvil, panel propio, etc.).

- **Versión**: 2.5.0
- **Slugs**: panel `Mis Medios` (`yz-media`), submenús `Mapa` (`yz-media-map`) y `Ajustes` (`yz-media-settings`).
- **Taxonomía interna**: `yz_media_folder` (carpetas).
- **CPTs**: `yzmf_location` (mapa), `yzmf_slider` (sliders, privado).

---

## REST API — namespace `yzmf/v1`

Base: `https://tu-dominio.tld/wp-json/yzmf/v1/`

### Autenticación

Para clientes externos (PWA móvil, scripts), usa **Application Passwords** nativos de WordPress:

1. Ve a `Usuarios → Tu perfil → Application Passwords`.
2. Crea una contraseña nueva (ej: "PWA móvil").
3. En el cliente, autentica vía Basic Auth:
   ```
   Authorization: Basic base64(usuario:application_password)
   ```

Capabilities mínimas:
- `upload_files` para CRUD de carpetas/media/portfolios.
- `delete_posts` para eliminar attachments y portfolios.
- `manage_options` para tareas administrativas (regen thumbs, backfill).

`map/data` es **público** (sin autenticación) para uso desde frontend.

---

### Folders

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET    | `/folders` | Árbol completo de carpetas. |
| POST   | `/folders` | Crear carpeta. Body: `{ name, parent? }`. |
| PUT    | `/folders/{id}` | Renombrar. Body: `{ name }`. |
| DELETE | `/folders/{id}` | Eliminar carpeta (no borra imágenes). |
| GET    | `/folders/{id}/images` | Imágenes de una carpeta (alias de `/media?folder={id}`). |

### Media

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET    | `/media` | Listar con filtros. Query: `folder` (-1=todas, 0=sin carpeta, >0=id), `page`, `per_page` (max 100), `search`, `orderby` (`date`\|`title`\|`size`), `order` (`ASC`\|`DESC`), `mime` (`image`\|`video`\|`pdf`\|`audio`). |
| POST   | `/media` | Subir archivo. Multipart: campo `file`, opcional `folder_id`. |
| GET    | `/media/{id}` | Detalle (incluye EXIF, dimensiones, alt, caption, etc.). |
| PUT    | `/media/{id}` | Editar metadatos. Body: `{ title?, alt?, caption?, description?, seo_title? }`. |
| DELETE | `/media/{id}` | Eliminar attachment. Requiere `delete_posts`. |
| PUT    | `/media/{id}/folder` | Mover a carpeta. Body: `{ folder_id }` (0 = sin carpeta). |
| POST   | `/media/{id}/ai` | Generar alt+caption con Claude. Devuelve `{ alt, caption }` y los guarda. |

### Map

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET    | `/map/data` | **Público**. Datos para el mapa frontend (con thumbs y count). Cacheado 15 min. |
| GET    | `/map/locations` | Lista admin de ubicaciones. |
| POST   | `/map/locations` | Crear ubicación. Body: `{ name, lat, lng, tag?, description?, gallery_url?, hero_id?, folder_ids?, photo_ids? }`. |
| PUT    | `/map/locations/{id}` | Actualizar. Mismos campos. |
| DELETE | `/map/locations/{id}` | Eliminar. |

### Geocoding (proxy de Nominatim)

Cumple los TOS de Nominatim (User-Agent identificado, cache de 1 día por consulta).

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET    | `/geocode/search?q=…` | Búsqueda libre de lugar. |
| GET    | `/geocode/reverse?lat=…&lng=…` | Coords → dirección. |

### Portfolios (CPT del tema kotlis)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET    | `/portfolios` | Lista paginada. Query: `page`, `per_page`, `search`, `status` (`any`\|`publish`\|`draft`…), `category` (term id). |
| POST   | `/portfolios` | Crear. Body: `{ title, content?, excerpt?, status?, layout?, hero_id?, categories?, gallery?, linked_folder? }`. |
| GET    | `/portfolios/{id}` | Detalle (incluye galería). |
| PUT    | `/portfolios/{id}` | Actualizar (todos los campos opcionales). |
| DELETE | `/portfolios/{id}` | Eliminar (papelera). Query `force=1` para borrar definitivamente. |
| GET    | `/portfolios/{id}/gallery` | Galería actual del portfolio (según layout). |
| PUT    | `/portfolios/{id}/gallery` | Reemplazar galería. Body: `{ gallery: [id1, id2, …] }`. |
| POST   | `/portfolios/{id}/sync-folder` | Vincular una carpeta YZMF como galería. Body: `{ folder_id, orderby?, order? }`. Sustituye la galería actual por las imágenes de la carpeta. |

**Layouts soportados**: `st1`, `st2`, `st3`, `st4`. El plugin escribe automáticamente en el meta key correcto:
- `st1` → `rnr_portfolio_column_grid_gallery_images`
- `st2` → `rnr_th_gallery_imge_st2`
- `st3` → `rnr_portfolio_column_fullwidth_gallery_images`
- `st4` → `rnr_th_gallery_imge_st4`

### Portfolio categories

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET    | `/portfolio-categories` | Listar. |
| POST   | `/portfolio-categories` | Crear. Body: `{ name, parent? }`. |
| PUT    | `/portfolio-categories/{id}` | Actualizar. |
| DELETE | `/portfolio-categories/{id}` | Eliminar. |

### Sliders

CPT propio `yzmf_slider`. Cada slider se almacena como un post con un meta JSON `_yzmf_slider_data` que contiene los settings y el array de slides. La gestión la hace la PWA; el frontend lo renderiza el shortcode `[yzmf_slider id="N"]` o el widget Elementor incluido.

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET    | `/sliders` | Lista (resumen). Query: `page`, `per_page`, `search`. |
| POST   | `/sliders` | Crear. Body: `{ title? }`. Devuelve el slider con `data` por defecto. |
| GET    | `/sliders/{id}` | Detalle con `data` completo (settings + slides). |
| PUT    | `/sliders/{id}` | Actualizar. Body: `{ title?, data? }`. El `data` se valida y se rellena con defaults. |
| DELETE | `/sliders/{id}` | Mover a papelera. Query `force=1` para borrar definitivamente. |
| POST   | `/sliders/{id}/duplicate` | Clonar. Genera nuevos slide IDs para evitar colisiones. |

**Modelo de slide** (entrada del array `data.slides`):

```json
{
  "id": "slide_a3f7c2",
  "type": "image",                    // "image" | "video_file" | "video_embed"
  "image_id": 12345,                  // attachment ID si type=image
  "video_id": 0,                      // attachment ID si type=video_file (MP4)
  "video_embed_url": "",              // URL /embed/ si type=video_embed
  "title": "Auroras boreales",
  "subtitle": "Octubre 2025",
  "text": "Una semana persiguiendo el cielo verde",
  "location": "Vík, Islandia",
  "lat": 63.4198, "lng": -19.0067,
  "button_text": "Ver galería",
  "button_url": "/portfolio/auroras-islandia/",
  "style": {
    "overlay_color": "#000000",
    "overlay_opacity": 0.3,
    "text_color": "#ffffff",
    "text_alignment": "center",       // "start" | "center" | "end"
    "vertical_position": "center",    // "top" | "center" | "bottom"
    "kenburns": true
  }
}
```

**Settings** (`data.settings`): `autoplay`, `speed` (ms), `transition` (`slide`|`fade`), `navigation`, `pagination` (`bullets`|`progress`|`none`), `loop`, `kenburns`, `height` (cualquier valor CSS).

**Shortcode**: `[yzmf_slider id="N"]`. Atributos opcionales que sobreescriben settings: `height`, `autoplay`, `speed`, `loop`, `navigation`, `pagination`, `transition`.

**Widget Elementor**: aparece bajo la categoría "Yezrael". Select del slider + mismos overrides que el shortcode. Internamente delega en `do_shortcode()` para mantener una sola fuente de render.

**Frontend**: usa Swiper 11 cargado como módulo ES desde jsDelivr para no chocar con el Swiper que cargue el tema. Assets propios: `assets/css/yzmf-slider.css` + `assets/js/yzmf-slider.js`.

---

## Ejemplos curl

```bash
# Auth header reutilizable
AUTH="Authorization: Basic $(echo -n 'usuario:app_pass_aqui' | base64)"
BASE="https://yezraelperez.es/wp-json/yzmf/v1"

# Listar carpetas
curl -H "$AUTH" "$BASE/folders"

# Crear carpeta
curl -X POST -H "$AUTH" -d "name=Sesión Galicia" "$BASE/folders"

# Subir archivo a una carpeta
curl -X POST -H "$AUTH" -F "file=@foto.jpg" -F "folder_id=12" "$BASE/media"

# Generar alt+caption con IA
curl -X POST -H "$AUTH" "$BASE/media/345/ai"

# Mover imagen a otra carpeta
curl -X PUT -H "$AUTH" -d "folder_id=15" "$BASE/media/345/folder"

# Crear portfolio y vincular carpeta como galería
curl -X POST -H "$AUTH" \
  -d "title=Sesión Galicia&status=publish&layout=st1" \
  "$BASE/portfolios"
# → respuesta { id: 678, … }

curl -X POST -H "$AUTH" \
  -d "folder_id=12&orderby=date&order=ASC" \
  "$BASE/portfolios/678/sync-folder"

# Crear slider y añadirle un slide
curl -X POST -H "$AUTH" -H "Content-Type: application/json" \
  -d '{"title":"Slider portada"}' "$BASE/sliders"
# → respuesta { id: 999, data: { settings: {...}, slides: [] } }

curl -X PUT -H "$AUTH" -H "Content-Type: application/json" \
  -d '{
    "data": {
      "settings": { "autoplay": true, "speed": 5000 },
      "slides": [
        { "type": "image", "image_id": 12345, "title": "Hola", "location": "León" }
      ]
    }
  }' "$BASE/sliders/999"
```

---

## Settings

`Mis Medios → Ajustes`:

- **API Key de Claude** — para generación automática de alt + caption.
- **Modelo Claude** — por defecto `claude-haiku-4-5-20251001` (rápido y barato). Para descripciones de mayor calidad: `claude-sonnet-4-6`.
- **Modo de envío de imagen** — `url` (Claude descarga la URL pública) o `base64` (lectura local). En `url` hay fallback automático a `base64` si la API rechaza la URL (sitios privados/local).
- **Mantenimiento → Recalcular tamaños** — backfill del meta `_yzmf_filesize`. Necesario una vez tras actualizar a v2.4 para que el orden por "Tamaño" funcione con archivos antiguos.

## Estructura

```
yz-media-folders/
├── yz-media-folders.php              ← Bootstrap (v2.5.0)
├── README.md                          ← Este archivo
├── includes/
│   ├── class-taxonomy.php             ← Taxonomía yz_media_folder
│   ├── class-ajax.php                 ← Handlers AJAX (panel admin)
│   ├── class-admin.php                ← Páginas de menú + ajustes
│   ├── class-map.php                  ← CPT yzmf_location + mapa
│   ├── class-rest.php                 ← REST API (folders, media, map, geocode)
│   ├── class-portfolio-bridge.php     ← Sync con CPT portfolio del tema
│   ├── class-slider.php               ← CPT yzmf_slider + helpers de datos
│   ├── class-slider-rest.php          ← Endpoints /sliders/*
│   ├── class-slider-shortcode.php     ← Shortcode + render con Swiper
│   ├── class-elementor-integration.php ← Categoría "Yezrael" + registro de widgets
│   └── elementor/
│       └── class-widget-slider.php    ← Widget YZMF Slider para Elementor
└── assets/
    ├── css/
    │   ├── main.css
    │   ├── map-admin.css
    │   ├── map-front.css
    │   └── yzmf-slider.css            ← Estilos del shortcode/widget
    └── js/
        ├── main.js
        ├── map-admin.js
        ├── map-front.js
        └── yzmf-slider.js             ← Init de Swiper para los sliders
```

## Hooks útiles para extender

- `wp_generate_attachment_metadata` — el plugin guarda automáticamente `_yzmf_filesize` en cada subida.
- Meta key `_yzmf_seo_title` — SEO title custom por imagen.
- Meta key `_yzmf_linked_folder` (en CPT `portfolio`) — carpeta YZMF vinculada al portfolio.
- Transient `yzmf_map_public_data` — cache de 15 min del endpoint público del mapa; se invalida al guardar/borrar ubicación.
