# Media Folders APP

Sistema completo de gestión de medios y portfolios para WordPress: plugin propio + PWA móvil/escritorio que se comunican vía REST API.

Desarrollado para **yezraelperez.es** pero pensado para ser reutilizable en cualquier sitio WordPress con tema kotlis (o adaptable a otros temas con un cambio de meta keys).

## Estructura

```
Media-Folders-APP/
├── plugin/           ← Plugin de WordPress (PHP)
│   ├── yz-media-folders.php
│   ├── includes/     ← Clases: taxonomy, ajax, admin, map, rest, portfolio-bridge
│   └── assets/       ← JS/CSS del panel admin
│
└── app/              ← PWA Vue 3 + Vite (cliente móvil/desktop)
    ├── src/
    │   ├── views/        ← 11 pantallas
    │   ├── components/   ← FolderPicker, MediaPicker, GeoTagger, ...
    │   ├── stores/       ← Pinia: auth, ui, folders, media, portfolios, locations
    │   ├── api/          ← Cliente REST con auth Application Password
    │   └── styles/       ← Tokens (dark/light) + base
    ├── public/
    └── vite.config.js
```

## Funcionalidad

### Plugin WordPress (`yz-media-folders`)

- **Carpetas de medios**: taxonomía propia (`yz_media_folder`) sobre attachments con CRUD completo, drag & drop, jerarquía.
- **Panel admin custom**: alternativa a la librería de medios nativa, con filtros, búsqueda, modal de edición, regenerar miniaturas.
- **Generación de alt text + caption con IA**: integración con la API de Claude (Anthropic). Modos URL y base64.
- **Mapa fotográfico**: CPT `yzmf_location` con coordenadas, vinculado a carpetas y fotos individuales. Shortcode `[yz_photo_map]` para frontend.
- **Geolocalización de fotos**: meta keys `_yzmf_geo_lat/lng/place/source`. Auto-detección de GPS desde EXIF al subir.
- **REST API completa** (namespace `yzmf/v1`): folders, media, portfolios, map, geo, stats, geocode (proxy de Nominatim).
- **Bridge con portfolios del tema kotlis**: lee y escribe correctamente los meta keys del tema (`rnr_*`) según el layout activo (st1/st2/st3/st4).
- **CORS configurable** por orígenes para consumir la API desde una app externa.
- **Backfill** de tamaños de archivo y schema GET/PUT para meta avanzada del tema.

### PWA (`app/`)

11 pantallas:

1. **Login** — autenticación con Application Password
2. **Dashboard** — KPIs, sparkline 30d, top carpetas, salud del catálogo
3. **Medios** — galería con filtros, búsqueda, scroll infinito, **acciones masivas** (mover, IA, geotag, copiar URLs, eliminar)
4. **Detalle medio** — editar metadatos, generar IA, **mini-mapa de ubicación**, EXIF
5. **Subir** — captura cámara/galería con **previsualización** y cola con estado por archivo
6. **Carpetas** — gestión completa con árbol expansible y **drag & drop** de carpetas
7. **Portfolios** — listado con filtros, paginación
8. **Detalle portfolio** — galería con **drag & drop reordenable**, imagen destacada (selector global o de la galería), sincronización con carpetas, **configuración avanzada** del tema kotlis (campos dinámicos según layout)
9. **Crear portfolio** — formulario con auto-sync de carpeta opcional
10. **Categorías de portfolio** — CRUD jerárquico
11. **Mapa** — Leaflet con CRUD de ubicaciones, capa de fotos georreferenciadas, búsqueda con Nominatim

Características transversales:
- Tema **dark/light** con selector
- Pull-to-refresh
- **Detector de actualización del Service Worker** (banner "Nueva versión disponible")
- Responsive (móvil + tablet + escritorio con sidebar lateral)
- Instalable como PWA

## Instalación

### Plugin

1. Copia `plugin/` a `wp-content/plugins/yz-media-folders/` en tu WordPress.
2. Actívalo en `Plugins → Plugins instalados`.
3. Configura en `Mis Medios → Ajustes`:
   - API key de Claude (opcional, para generación con IA)
   - Modelo de Claude
   - Modo de envío (`url` vs `base64`)
   - Orígenes CORS permitidos (ej: `https://app.tu-dominio.tld`)

### PWA

```bash
cd app/
npm install
npm run dev    # desarrollo en http://localhost:5173
npm run build  # build de producción → dist/
```

Para deploy: subir el contenido de `dist/` al subdominio (ver `app/README.md` para más detalle).

#### Configurar SFTP de despliegue

Crea `app/.vscode/sftp.json` con tus credenciales (este archivo está en `.gitignore` para no exponer secretos):

```json
{
  "name": "Mi sitio",
  "host": "tu-host.com",
  "username": "usuario",
  "password": "password",
  "port": 21,
  "protocol": "ftp",
  "remotePath": "/ruta/al/subdominio/",
  "context": "dist",
  "uploadOnSave": false
}
```

Y luego `.\deploy.ps1` automatiza build + upload.

## Stack técnico

- **Backend**: PHP 7.4+, WordPress 6.x, MySQL
- **PWA**: Vue 3 (Composition API + script setup), Vite 6, Pinia, Vue Router 4, Leaflet 1.9, vuedraggable, vite-plugin-pwa
- **APIs externas**: Anthropic Claude (alt+caption), Nominatim/OpenStreetMap (geocoding), CARTO (tiles)
- **Autenticación**: WordPress Application Passwords (Basic Auth)

## REST API — endpoints principales

Namespace: `/wp-json/yzmf/v1/`

| Método | Endpoint | Descripción |
|---|---|---|
| GET/POST | `/folders` | Listar / crear carpetas |
| PUT/DELETE | `/folders/{id}` | Renombrar / mover (parent) / eliminar |
| GET | `/folders/{id}/images` | Imágenes de una carpeta |
| GET/POST | `/media` | Listar / subir |
| GET/PUT/DELETE | `/media/{id}` | Obtener / actualizar / eliminar |
| PUT | `/media/{id}/folder` | Mover a carpeta |
| POST | `/media/{id}/ai` | Generar alt+caption con IA |
| PUT | `/media/{id}/geo` | Asignar ubicación |
| POST | `/media/geo/bulk` | Geotag masivo |
| GET | `/media/geo/all` | Todas las fotos georreferenciadas |
| GET | `/portfolios` | Listar portfolios |
| GET/PUT/DELETE | `/portfolios/{id}` | CRUD portfolio |
| GET/PUT | `/portfolios/{id}/gallery` | Galería del portfolio |
| POST | `/portfolios/{id}/sync-folder` | Vincular carpeta como galería |
| GET/PUT | `/portfolios/{id}/meta` | Schema dinámico + valores de meta del tema |
| GET/POST | `/portfolio-categories` | Categorías |
| GET | `/map/data` | Datos públicos del mapa (cacheable) |
| GET/POST/PUT/DELETE | `/map/locations` | CRUD ubicaciones |
| GET | `/geocode/search?q=` | Búsqueda Nominatim (proxy) |
| GET | `/geocode/reverse?lat=&lng=` | Reverse geocoding |
| GET | `/stats` | Estadísticas globales |

Detalle completo en `plugin/README.md`.

## Licencia

GPL-2.0+ (consistente con WordPress).

## Autor

Yezrael Pérez · [yezraelperez.es](https://yezraelperez.es)
