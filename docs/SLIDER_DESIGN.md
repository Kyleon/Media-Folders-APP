# Diseño del módulo Slider (yzmf_slider)

> Documento de diseño previo a implementación.
> Última actualización: 2026-05-07.
> Estado: aprobado, decisiones cerradas con Yezrael. Listo para Fase 1.

---

## 1. Resumen ejecutivo

Añadir al plugin `yz-media-folders` un módulo de sliders configurables desde la PWA. Cada slider se compone de varios slides con foto, título, texto, ubicación y botón. El frontend ofrece tres puntos de uso:

1. **Shortcode** `[yzmf_slider id=N]` reutilizable en cualquier página o post.
2. **Widget Elementor** que envuelve el shortcode con UI nativa.
3. **Override del slider de portada del tema YPVA** mediante una meta en la página 37.

Library frontend: **Swiper** (ya cargado por el tema kotlis).

---

## 2. Decisiones de diseño y rationale

### 2.1 CPT único con meta JSON, no CPT por slide

- **Decisión:** un slider = un post del CPT `yzmf_slider`, los slides se almacenan en la meta `_yzmf_slider_data` como JSON.
- **Por qué:** simplifica reordenar (mover items en array), elimina queries N+1, hace el caché REST trivial, evita gestionar el ciclo de vida de N posts hijos. Es el patrón de MetaSlider / Slider Revolution.
- **Trade-off aceptado:** los slides no son indexables por taxonomía. No es necesario para este caso de uso.

### 2.2 Naming `yzmf_*` (no `yzsl_*`)

- **Decisión:** todo el módulo usa el prefijo del plugin (`yzmf_*`).
- **Por qué:** el plugin ya tiene namespace REST `yzmf/v1` y prefijo `yzmf_*` en metas. Fragmentar en `yzsl_*` complica búsquedas y permisos sin beneficio real.

### 2.3 Library: Swiper

- **Decisión:** usar Swiper para todos los puntos de renderizado.
- **Por qué:** ya está enqueado por el tema YPVA (lo usa el slider de portada actual). Reusarlo evita doble carga. Es el estándar en WordPress.

### 2.4 Bridge a portada como override del template-part del tema

- **Decisión:** sobreescribir `template-parts/intro/multi-slideshow.php` en `ypva-child/intro/` (la carpeta `intro/` ya existe en el child con overrides).
- **Por qué:** mantiene el HTML/CSS del tema (transiciones, kenburns, follow-wrap), solo cambia la fuente de datos (de los metas `rnr_bl_multi_slide_*` al JSON del slider yzmf).

### 2.5 Sin redes sociales por slide

- Confirmado por Yezrael. Las redes sociales del tema (`rnr_md_multi_slideshow_social_*` de `multi-slideshow.php`) siguen siendo a nivel de página de portada, no por slide.

---

## 3. Modelo de datos

### 3.1 CPT `yzmf_slider`

```php
register_post_type('yzmf_slider', [
    'labels'             => [...],
    'public'             => false,
    'show_ui'            => true,             // visible en admin (debug)
    'show_in_menu'       => 'yz-media',       // bajo "Mis Medios"
    'show_in_rest'       => true,
    'rest_base'          => 'sliders',
    'rest_namespace'     => 'yzmf/v1',
    'supports'           => ['title', 'custom-fields'],
    'capability_type'    => 'post',
    'map_meta_cap'       => true,
]);
```

### 3.2 Meta `_yzmf_slider_data` (JSON)

```json
{
  "version": 1,
  "settings": {
    "autoplay": true,
    "speed": 6000,
    "transition": "slide",
    "navigation": true,
    "pagination": "bullets",
    "loop": true,
    "kenburns": true,
    "height": "100vh"
  },
  "slides": [
    {
      "id": "slide_a3f7c2",
      "type": "image",
      "image_id": 12345,
      "video_id": null,
      "video_embed_url": null,
      "title": "Auroras boreales en Islandia",
      "subtitle": "Octubre 2025",
      "text": "Una semana persiguiendo el cielo verde",
      "location": "Vík, Islandia",
      "lat": 63.4198,
      "lng": -19.0067,
      "button_text": "Ver galería",
      "button_url": "/portfolio/auroras-islandia",
      "style": {
        "overlay_color": "#000000",
        "overlay_opacity": 0.3,
        "text_color": "#ffffff",
        "text_alignment": "center",
        "vertical_position": "center",
        "kenburns": true
      }
    }
  ]
}
```

**Campos de tipo:**

- `type: "image"` → usa `image_id` (attachment ID).
- `type: "video_file"` → usa `video_id` (attachment ID de un MP4). Reproduce con `<video autoplay loop muted playsinline>`.
- `type: "video_embed"` → usa `video_embed_url` (YouTube/Vimeo). Renderiza con iframe responsive.

**Estilos por slide** (`style`): cada slide controla su overlay, color del texto, alineación horizontal, posición vertical y kenburns. Si quieres mantener consistencia visual, la PWA puede ofrecer un botón "Aplicar estilos a todos los slides".

**Multi-idioma:** el JSON está versionado (`version: 1`). Una eventual migración a multi-idioma sería `version: 2` con strings como objetos `{es: "...", en: "..."}`. No implementado ahora.

### 3.3 Meta global por slider

- `_yzmf_slider_data` (JSON, único, single=true)
- `_yzmf_slider_thumbnail` (attachment ID, opcional, para preview en listados)

### 3.4 Meta de página para bridge a portada

- Nombre: `_yzmf_slider_for_home`
- Tipo: ID del slider (int)
- Aplica a: cualquier `page` (típicamente la portada ID 37)
- Si está vacío: el tema renderiza el slider tradicional con metas `rnr_bl_multi_slide_*`

---

## 4. API REST

Namespace: `yzmf/v1`. Auth: WordPress Application Passwords (igual que el resto del plugin).

### 4.1 Endpoints

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/sliders` | Lista con paginación y campos resumen |
| POST | `/sliders` | Crear slider vacío |
| GET | `/sliders/{id}` | Detalle completo (incluye `data` JSON) |
| PUT | `/sliders/{id}` | Actualizar título y/o `data` completo |
| DELETE | `/sliders/{id}` | Eliminar (trash, no permanente) |
| POST | `/sliders/{id}/duplicate` | Clonar slider |
| GET | `/sliders/{id}/render` | HTML renderizado (preview) |

### 4.2 Endpoints granulares de slides (opcional, fase 2)

Si la PWA hace muchos cambios pequeños, evitar enviar el JSON entero:

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/sliders/{id}/slides` | Añadir slide |
| PUT | `/sliders/{id}/slides/{slide_id}` | Actualizar slide |
| DELETE | `/sliders/{id}/slides/{slide_id}` | Eliminar slide |
| PUT | `/sliders/{id}/reorder` | Reordenar (body: array de slide_id) |

**Decisión inicial:** empezar con un solo `PUT /sliders/{id}` que recibe el JSON entero. Es más simple y la PWA puede hacer optimistic updates. Si hay problemas de concurrencia en el futuro, se añaden los granulares.

### 4.3 Permission callbacks

Reusar las existentes:
- `YZMF_REST::can_manage()` para POST / PUT / DELETE
- Lectura pública de `GET /sliders/{id}` solo si el slider está marcado como `public` (por defecto sí).

### 4.4 Schema del payload de PUT

```json
{
  "title": "Slider Portada",
  "data": { /* JSON completo del 3.2 */ }
}
```

Validación server-side: validar tipos de cada slide, image_id existe, lat/lng son floats, lt;url> es válida.

---

## 5. Frontend

### 5.1 Shortcode `[yzmf_slider]`

```
[yzmf_slider id="42"]
[yzmf_slider id="42" height="600px"]              ← override altura
[yzmf_slider id="42" autoplay="false"]            ← override settings
```

Atributos del shortcode pueden sobreescribir cualquier setting del JSON.

### 5.2 HTML renderizado (Swiper)

```html
<div class="yzmf-slider"
     data-slider-id="42"
     data-settings='{...}'>
  <div class="swiper">
    <div class="swiper-wrapper">
      <div class="swiper-slide" data-slide-id="...">
        <div class="yzmf-slide-bg" style="background-image: url(...)"></div>
        <div class="yzmf-slide-overlay"></div>
        <div class="yzmf-slide-content">
          <span class="yzmf-slide-subtitle">Octubre 2025</span>
          <h2 class="yzmf-slide-title">Auroras boreales</h2>
          <p class="yzmf-slide-text">Una semana persiguiendo...</p>
          <span class="yzmf-slide-location">📍 Vík, Islandia</span>
          <a class="yzmf-slide-button" href="...">Ver galería</a>
        </div>
      </div>
    </div>
    <div class="swiper-pagination"></div>
    <div class="swiper-button-prev"></div>     ← flechas de navegación
    <div class="swiper-button-next"></div>
  </div>
</div>
```

### 5.3 Assets

- `plugin/assets/css/yzmf-slider.css` (estilos del shortcode/widget)
- `plugin/assets/js/yzmf-slider.js` (init de Swiper, lee `data-settings`)
- Enqueue condicional: solo si la página contiene el shortcode o el widget Elementor

### 5.4 Widget Elementor (fase 3)

Widget propio en `plugin/includes/elementor/widget-slider.php`:
- Categoría: "Yezrael"
- Control principal: select dropdown poblado con sliders existentes
- Controles secundarios: override de altura, overlay, etc.
- Render: invoca el mismo helper `YZMF_Slider::render($id)` que el shortcode.

---

## 6. Integración portada del tema

### 6.1 Override en el child

Crear `wp/themes/ypva-child/intro/multi-slideshow.php` (override del padre).

Pseudocódigo:

```php
$slider_id = get_post_meta($post->ID, '_yzmf_slider_for_home', true);

if ($slider_id) {
    // Renderizar con datos del slider yzmf
    $data = YZMF_Slider::get_data($slider_id);
    foreach ($data['slides'] as $slide) {
        echo '<div class="swiper-slide">';
        echo '  <div class="ms_item fl-wrap kenburns">';
        echo '    <div class="bg" data-bg="' . wp_get_attachment_url($slide['image_id']) . '"></div>';
        echo '    <div class="yzmf-slide-content-overlay">';
        echo '      <h2>' . esc_html($slide['title']) . '</h2>';
        echo '      <p>' . esc_html($slide['text']) . '</p>';
        echo '      <span class="loc">' . esc_html($slide['location']) . '</span>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    return;
}

// Fallback: mantener exactamente el comportamiento original del tema
include get_template_directory() . '/template-parts/intro/multi-slideshow.php';
```

### 6.2 Edición del slider de portada desde la PWA

- En la pantalla del slider, un botón "Asignar a portada" actualiza la meta `_yzmf_slider_for_home` de la página 37.
- Solo un slider puede estar activo en portada al mismo tiempo (server-side: limpia los demás al asignar).

---

## 7. PWA (Vue 3)

### 7.1 Vistas nuevas

- `app/src/views/Sliders.vue` (listado)
- `app/src/views/SliderDetail.vue` (editor)

### 7.2 Componentes nuevos

- `SliderEditor.vue` (lista de slides con drag & drop)
- `SlideCard.vue` (item individual con thumbnail, edita inline)
- `SlideForm.vue` (formulario expandible: título, texto, ubicación, botón)
- `SliderSettings.vue` (panel lateral con autoplay, speed, etc.)
- `SliderPreview.vue` (iframe que carga `/sliders/{id}/render`)

### 7.3 Componentes reutilizados (ya existen)

- `MediaPicker.vue` para elegir imagen del slide
- `GeoTagger.vue` para ubicación + lat/lng (ya tiene Nominatim)
- `vuedraggable` para reordenar

### 7.4 Store nuevo

`app/src/stores/sliders.js`:

```js
state:    { sliders: [], current: null, isDirty: false }
actions:  { fetchAll, fetchOne, create, save, duplicate, remove, addSlide, updateSlide, removeSlide, reorderSlides }
getters:  { byId, count, dirty }
```

### 7.5 Router

```js
{ path: '/sliders', component: Sliders }
{ path: '/sliders/:id', component: SliderDetail, props: true }
{ path: '/sliders/new', component: SliderDetail, props: { id: 'new' } }
```

---

## 8. Plan de implementación

### Fase 1: backend mínimo (1 sesión)

- [ ] Crear `plugin/includes/class-slider.php` con CPT y helpers
- [ ] Registrar el CPT desde `yz-media-folders.php`
- [ ] Añadir endpoints REST en `class-rest.php` o en clase aparte `class-slider-rest.php`
- [ ] Validar con curl/Postman: crear, listar, actualizar, borrar

### Fase 2: shortcode y assets (1 sesión)

- [ ] Implementar `YZMF_Slider::render($id)` que devuelve HTML
- [ ] Crear assets: `yzmf-slider.css` + `yzmf-slider.js` (Swiper init)
- [ ] Registrar shortcode `[yzmf_slider]`
- [ ] Enqueue condicional (solo si hay shortcode o widget en la página)
- [ ] Probar inserción manual del shortcode en una página

### Fase 3: PWA editor (2 sesiones)

- [ ] Store Pinia `sliders.js`
- [ ] Vista `Sliders.vue` (listado)
- [ ] Vista `SliderDetail.vue` con `SliderEditor` + `SliderSettings`
- [ ] Integración con MediaPicker y GeoTagger existentes
- [ ] Drag & drop de slides
- [ ] Probar flujo completo desde la PWA

### Fase 4: integración portada (1 sesión)

- [ ] Override `wp/themes/ypva-child/intro/multi-slideshow.php`
- [ ] Endpoint y botón "Asignar a portada" en la PWA
- [ ] Verificar que el fallback al slider del tema sigue funcionando

### Fase 5: widget Elementor (1 sesión)

- [ ] Widget en `plugin/includes/elementor/widget-slider.php`
- [ ] Registro condicional (solo si Elementor está activo)
- [ ] Probar drag & drop del widget en una página Elementor

### Fase 6: pulido y docs (1 sesión)

- [ ] Documentar en `plugin/README.md` los nuevos endpoints
- [ ] Capturas en `app/README.md`
- [ ] Sincronizar con `scripts/sync-wp.ps1` y subir a Hostinger

**Total estimado:** 6 sesiones de trabajo (~12 a 18 horas).

---

## 9. Decisiones cerradas (2026-05-07)

| # | Cuestión | Decisión |
|---|---|---|
| 1 | Visibilidad CPT | **Privado** (`public: false`). Solo embed por shortcode/widget. Sin permalink propio. |
| 2 | Vídeo en slides | **Sí, los tres tipos**: imagen, MP4 nativo (attachment), embed YouTube/Vimeo. Campo `type` en cada slide. |
| 3 | Estilos por slide | **Por slide individualmente**. Cada slide tiene su objeto `style`. La PWA puede ofrecer "Aplicar a todos" como atajo. |
| 4 | Importar slider portada actual | **No**. Empezar de cero al configurar el slider de portada desde la PWA. |
| 5 | Versionado / revisiones | **Sin revisiones**. El JSON se sobrescribe en cada save. WordPress Trash recupera el slider entero si se borra. |
| 6 | Multi-idioma | **Hueco para futuro**. JSON con `version: 1`. Migración eventual a `version: 2` con strings como objetos por idioma. No implementar ahora. |

---

*Fin del documento. Listo para Fase 1.*
