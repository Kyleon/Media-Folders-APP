# Roadmap — ideas para iterar

Listado vivo de funcionalidades que se podrían añadir al sistema. Agrupadas
por área del flujo del fotógrafo. Esfuerzo aproximado: **S** (pequeño,
1–3 días), **M** (medio, 1–2 semanas), **L** (grande, 1+ mes).

---

## 🌐 Sitio público

| Idea | Qué resuelve | Esfuerzo |
|---|---|---|
| **Blog/diario** como CPT propio (`yzmf_post`) | Hoy si quieres escribir un post tienes que ir a wp-admin. Lo metes en la PWA con preview, drag & drop de imágenes desde tu media, schedule de publicación. | M |
| **SEO automático** para portfolios | Schema.org `CreativeWork`/`ImageObject` por cada portfolio, sitemap.xml propio, meta description generada por IA desde el caption, Open Graph image dinámica. | M |
| **Generador de Open Graph image** | Cuando compartes un portfolio en Threads/WhatsApp, sale una imagen 1200×630 con título + hero + tu marca, generada al vuelo. | S |
| **Páginas legales editables** (about, contacto, FAQs, prensa) | Editas desde la PWA, no tocas Elementor ni el theme. | S |

## 💼 Negocio / clientes

| Idea | Qué resuelve | Esfuerzo |
|---|---|---|
| **Calendario de bookings** | Cliente entra a tu web, ve disponibilidad, reserva sesión (con Stripe checkout o sin pago para confirmar). | L |
| **Gestor de leads/contacto** | Sustituye Contact Form 7. Pipeline simple: nuevo → contactado → reservado → entregado. Con notas. | M |
| **Generador de presupuestos PDF** | Eliges paquete + cliente, genera PDF con tu marca, link de aceptación. Integra con Stripe para depósito. | M |
| **CRM mínimo de clientes** | Lista de clientes con sus galerías, comentarios, favoritas, sesiones pasadas, presupuestos firmados, valor total. | M |

## 🛒 Monetización

| Idea | Qué resuelve | Esfuerzo |
|---|---|---|
| **Venta de prints** | Cada foto puede activarse para venta. Tamaños y materiales (papel/lienzo/aluminio) con precios. Checkout Stripe. Tirada limitada con contador. | L |
| **Venta de licencias digitales** | Editorial, comercial, redes — distintos precios y términos por tipo de uso. Watermark se quita al pagar. | M |
| **Tienda integrada con el client portal** | El cliente ve su galería, marca favoritas Y ya tiene un carrito para imprimir las que quiera. Pago directo. | L |
| **Cupones / códigos promocionales** | Para reseñas, repetidores, referidos. | S |

## 📈 Insights / analítica

| Idea | Qué resuelve | Esfuerzo |
|---|---|---|
| **Analytics propio en Dashboard** | Sin Google Analytics. Mide vistas por portfolio, click-through, tiempo en página. Self-hosted, sin cookies. | M |
| **Heatmap de "qué fotos eligen tus clientes"** | Cruza datos del client-portal: las favoritas más marcadas en todas las galerías, agrupadas por tipo de sesión. Te dice qué estilo vende. | S |
| **Search Console integration** | Tu Dashboard te dice por qué te encuentran en Google (queries reales), no solo las analíticas internas. | M |
| **Performance monitor** | Lighthouse score automático tras cada deploy, alarmas si baja. | S |

## 📦 Workflow / productividad

| Idea | Qué resuelve | Esfuerzo |
|---|---|---|
| **Backups automáticos a B2 / S3 / Wasabi** | Cron diario que sube `/wp-content/uploads` + DB dump cifrado a almacenamiento barato. Restauración con un click. | M |
| **Sync con Dropbox/iCloud** | Una carpeta de Dropbox automáticamente importa nuevas fotos a una carpeta YZMF concreta (útil cuando exportas de Lightroom Classic). | M |
| **Comando rápido móvil** | Bottom bar en la PWA con shortcuts: "Subir foto de hoy", "Marcar last shoot como entregado", "Reenviar último presupuesto"… | S |
| **PWA web share target** | Compartes una foto desde el carrete del iPhone → tu PWA se ofrece como destino → subida a la carpeta actual con un tap. | S |

## 🤖 IA (extender lo que ya hay)

| Idea | Qué resuelve | Esfuerzo |
|---|---|---|
| **Auto-tagging visual** | Claude ya hace alt+caption. Añade: estilo (retrato/paisaje/boda), mood (alegre/melancólico/épico), elementos (mar, montaña, ciudad). Genera tags filtrables. | S |
| **Sugerencias de portfolio automáticas** | "Tienes 47 fotos sin portfolio asignado. Por su EXIF/contenido las agruparía en estos 3 posibles portfolios: ..." | M |
| **Email assistant para clientes** | "Redacta un email para mandar la galería 'Boda Anna & David' con un tono cálido y profesional, recordándole que tiene 2 semanas para favoritas." | S |
| **Detector de duplicados perceptual** | Detecta variantes casi idénticas del mismo disparo (ráfaga) y sugiere quedarse con la mejor por nitidez/composición. | M |

## 🌍 Distribución / marketing

| Idea | Qué resuelve | Esfuerzo |
|---|---|---|
| **Newsletter integrado** | Subs de visitantes (sin Mailchimp). Cuando publicas un portfolio nuevo, opt-in para notificar. Plantillas con tu marca. | M |
| **Auto-post a Instagram/Threads** | Marcas una foto como "promocional" → se programa publicación en Insta/Threads con caption generado por IA, hashtags y referencia. | M |
| **RSS feed por portfolio** | Para que otros sitios y agregadores puedan suscribirse. Gratis y casi sin código. | S |
| **Embed builder** | Generas un snippet para incrustar un portfolio en otra web (Medium, Substack, perfil de Behance). | S |

## 🔐 Privacidad / control

| Idea | Qué resuelve | Esfuerzo |
|---|---|---|
| **Modo "show & don't tell"** | Si una foto es de un cliente que no quiere ser identificado, marca como privada → aparece en tu portfolio sin nombre y con metadata redactada. | S |
| **Caducidad de galerías por defecto** | Toda galería de cliente caduca a los 90 días salvo que extiendas. Aviso por email al cliente con 7 días de antelación. | S |
| **Audit log visual** | Quién subió, modificó, descargó, compartió qué y cuándo. Útil para GDPR y para revisar accesos del client-portal. | M |

---

## Top 5 por valor / esfuerzo

Si tuviera que priorizar ahora mismo:

1. **Tagging IA extendido** (estilo / mood / elementos) — extiende `generate_ai_for_image` que ya existe. Una tarde de trabajo + automáticamente filtros mucho más ricos. **S**

2. **SEO automático para portfolios** (schema.org + sitemap + OG image dinámica) — multiplica tráfico orgánico. Un par de endpoints + un filter en `wp_head` y empiezas a aparecer mejor en Google. **M**

3. **Backups automáticos a Backblaze B2** — peace of mind. ~6€/año de almacenamiento. Una vez configurado te olvidas. Cuando un cliente te pida una foto borrada hace 2 años, la tienes. **M**

4. **Web share target en la PWA** — desde el carrete del iPhone, "compartir → YPVA" sube al instante a la carpeta activa. ~30 líneas en `manifest.json` + un endpoint. **S**

5. **Generador de presupuestos PDF + aceptación con Stripe** — único de la lista que es trabajo más sustancial, pero **te convierte la app en herramienta de negocio**, no solo de gestión visual. Diferencia entre "una herramienta interna" y "tu CRM completo". **M-L**

---

## Cómo se integra todo esto con la arquitectura actual

La buena noticia: nada de esto requiere reescribir lo que ya hay. La base
modular permite añadir cada idea como una iteración, no como un rewrite:

- **Cada nueva feature** suele ser:
  - 1 CPT nuevo (o reusar uno existente)
  - N endpoints REST en `yzmf/v1/` siguiendo el patrón ya establecido
  - Una vista nueva en `app/src/views/`
  - Una entrada en `BottomNav` o en el menú lateral

- **Lo común ya está resuelto**: auth, paginación, búsqueda, filtros, focus
  trap en sheets, ConfirmDialog accesible, manejo de errores, deploy script
  con health-check.

- **Las decisiones arquitectónicas grandes** (PWA vs nativo, plugin propio
  vs SaaS, REST vs GraphQL, BD propia vs WP postmeta) ya están tomadas y
  funcionan a escala razonable.

Cada idea de este roadmap encaja en ese molde. Por eso son iteraciones y
no proyectos nuevos.
