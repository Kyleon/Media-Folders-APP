# YPVA Admin · PWA

Panel móvil PWA para gestionar medios y portfolios de **yezraelperez.es** vía REST API (`yzmf/v1`).

- **Stack**: Vue 3 + Vite + Pinia + Vue Router + Leaflet
- **Auth**: WordPress Application Passwords (Basic Auth)
- **PWA**: instalable, dark/light theme

---

## Pantallas

- **Login** — URL del sitio + usuario + Application Password.
- **Inicio** — KPIs (medios, portfolios, ubicaciones, carpetas) y atajos.
- **Medios** — galería con filtro por carpeta, búsqueda, orden, scroll infinito.
- **Detalle medio** — editar título/alt/SEO/caption/descripción/carpeta. Generar alt+caption con IA. Ver EXIF. Copiar URL. Eliminar.
- **Subir** — captura de cámara o picker múltiple del móvil, cola de subidas con estado por archivo.
- **Carpetas** — gestión completa con árbol expansible y drag & drop.
- **Portfolios** — lista paginada, búsqueda, filtro por categoría.
- **Detalle portfolio** — editar campos, ver galería, vincular y sincronizar carpeta YZMF.
- **Nuevo portfolio** — formulario con auto-sync de carpeta opcional.
- **Sliders** — listado con miniatura, contador de slides y acciones (duplicar, eliminar).
- **Detalle slider** — editor con título editable, panel de settings globales y lista de slides con drag & drop. Cada slide soporta imagen / vídeo MP4 / embed YouTube-Vimeo, textos, ubicación con GeoTagger, botón cuyo enlace puede ser URL libre o un portfolio elegido por dropdown, y bloque de estilo individual (overlay, color de texto, alineación, posición vertical, kenburns). Atajo `Ctrl/Cmd+S` para guardar y aviso al salir si hay cambios sin guardar.
- **Mapa** — Leaflet con CRUD de ubicaciones, búsqueda de lugares (Nominatim vía proxy), reverse geocoding.
- **Ajustes** — sesión, theme switch, limpiar cachés.

---

## Instalación local

Requisitos: Node 18+.

```bash
cd f:/dev/proyectos/yezraelperez-app
npm install
npm run dev
```

Por defecto sirve en `http://localhost:5173`. Puedes acceder desde el móvil en la misma red usando la IP que muestra Vite.

### Variable opcional

Crea un archivo `.env.local` para autocompletar la URL en el login:
```
VITE_DEFAULT_BASE_URL=https://yezraelperez.es
```

---

## Build + despliegue al subdominio

```bash
npm run build
```

Genera `dist/` con todos los assets estáticos.

### Pasos para `app.yezraelperez.es`

1. **DNS**: crea un registro `A` (o `CNAME`) `app.yezraelperez.es` apuntando al hosting.
2. **Hostinger / cPanel**: crea el subdominio. Apunta su `document root` a una carpeta nueva (p. ej. `domains/yezraelperez.es/public_html/app/` o `domains/app.yezraelperez.es/public_html/`).
3. **SSL**: instala certificado Let's Encrypt para el subdominio (en Hostinger: SSL → instalar gratis).
4. **Subir `dist/`** completo a esa carpeta vía SFTP.
5. **Rewrites**: como es una SPA con HTML5 history, todas las rutas deben servir `index.html`. Crea un `.htaccess` en la raíz del subdominio:

   ```apache
   <IfModule mod_rewrite.c>
     RewriteEngine On
     RewriteBase /
     RewriteRule ^index\.html$ - [L]
     RewriteCond %{REQUEST_FILENAME} !-f
     RewriteCond %{REQUEST_FILENAME} !-d
     RewriteRule . /index.html [L]
   </IfModule>

   # Cache largo para assets versionados
   <FilesMatch "\.(js|css|woff2|svg|png|webp)$">
     Header set Cache-Control "public, max-age=31536000, immutable"
   </FilesMatch>
   <FilesMatch "\.(html|webmanifest)$">
     Header set Cache-Control "no-cache"
   </FilesMatch>
   ```

   Si tu hosting es LiteSpeed, este `.htaccess` también funciona.

6. **CORS en el plugin** — en el WP, ve a `Mis Medios → Ajustes → Orígenes CORS permitidos` y asegúrate de que esté `https://app.yezraelperez.es`.

7. **Application Password** — en `Usuarios → Tu perfil → Application Passwords`, genera una nueva ("PWA móvil") y úsala en el login de la app.

---

## Iconos PWA

El proyecto referencia `public/icons/icon-192.png` y `public/icons/icon-512.png`. Crea esas imágenes (192×192 y 512×512 px) y colócalas en `public/icons/` antes del build.

Para generar rápido a partir de un logo SVG/PNG, una opción gratuita: <https://realfavicongenerator.net/>.

Si no las añades, el manifest seguirá funcionando pero sin icono al instalar.

---

## Estructura

```
yezraelperez-app/
├── package.json
├── vite.config.js          ← Vite + plugin PWA
├── index.html
├── public/
│   └── favicon.svg
├── src/
│   ├── main.js
│   ├── App.vue
│   ├── api/
│   │   ├── client.js       ← fetch con Basic Auth
│   │   └── endpoints.js    ← Folders, Media, Map, Geo, Portfolios, Sliders…
│   ├── stores/             ← Pinia: auth, ui (theme/toast), folders, media, portfolios, locations, sliders
│   ├── router/index.js
│   ├── components/         ← AppShell, BottomNav, Toast, Spinner, ThemeSwitch, MediaPicker, GeoTagger, SliderSettings, SlideForm
│   ├── views/              ← Sliders.vue, SliderDetail.vue + el resto de pantallas
│   └── styles/             ← tokens.css (dark/light), base.css
└── README.md
```

---

## Troubleshooting

- **CORS error en el navegador**: verifica que `app.yezraelperez.es` está exactamente en `Orígenes CORS permitidos` del plugin (con `https://`, sin slash final).
- **401 al login**: la Application Password se invalidó (al cambiar la contraseña principal del usuario, por ejemplo). Genera una nueva.
- **Imágenes no cargan en la PWA pero sí en el sitio**: probablemente bloquea LiteSpeed. Comprueba que tu sitio sirve los archivos `wp-content/uploads/*` con `Access-Control-Allow-Origin: *` o que están servidos desde el mismo dominio raíz (que sí lo están).
- **El mapa no muestra tiles en móvil offline**: las tiles de CARTO requieren internet; el cache del SW no las cachea. Comportamiento esperado.
- **PWA no se actualiza**: desde la v0.22 está en `registerType: 'autoUpdate'`, así que el nuevo SW activa solo al cerrar y reabrir la app. Si sigues atascado en una build muy antigua (anterior al cambio), limpia datos del sitio una sola vez en el navegador (Configuración → Sitios → `app.yezraelperez.es` → Borrar).
