<?php
/**
 * KOTLIS Child Theme - Optimización y mejoras
 * Autor: Yez
 * Descripción: Archivo functions.php optimizado y ordenado.
 */

/* ============================================================
 *  1. CARGA DE ESTILOS Y SCRIPTS
 * ============================================================ */

/* Cargar hoja de estilos del Child Theme */
function kotlis_add_stylesheet() {
    wp_enqueue_style('kotlis-child-style', get_stylesheet_directory_uri() . '/style.css', false, '1.0', 'all');
}
add_action('wp_enqueue_scripts', 'kotlis_add_stylesheet');

/* Cargar Flickity solo en las páginas de portfolio */
function enqueue_flickity() {
    if (is_singular('portfolio')) {
        wp_enqueue_script('flickity', 'https://cdnjs.cloudflare.com/ajax/libs/flickity/2.3.0/flickity.pkgd.min.js', [], null, true);
        wp_enqueue_style('flickity-css', 'https://cdnjs.cloudflare.com/ajax/libs/flickity/2.3.0/flickity.min.css');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_flickity');

/* Cargar Lightbox solo en las páginas de portfolio */
function add_lightbox_to_portfolio() {
    if (is_singular('portfolio')) {
        wp_enqueue_script('lightbox2', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js', [], null, true);
        wp_enqueue_style('lightbox2-css', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css');
    }
}
add_action('wp_enqueue_scripts', 'add_lightbox_to_portfolio');

/* Cargar JavaScript para modo pantalla completa */
function pantallaCompleta() {
    wp_enqueue_script('js-file', get_stylesheet_directory_uri() . '/js/goToFullscreen.js');
}
add_action('wp_enqueue_scripts', 'pantallaCompleta');


/* ============================================================
 *  6. BANNER COOKIES
 * ============================================================ */

function banner_cookies() {
    echo '<link rel="stylesheet" href="'.get_stylesheet_directory_uri().'/cookies/pdcc.min.css">
<script charset="utf-8" src="'.get_stylesheet_directory_uri().'/cookies/pdcc.min.js"></script>
<script type="text/javascript">
    PDCookieConsent.config({
      "cookiePolicyLink": "",
      "hideModalIn": [""],
      "styles": {
        "primaryButton": {
          "bgColor" : "#A1FFA1",
          "txtColor": "#036900"
        },
        "secondaryButton": {
          "bgColor" : "#EEEEEE",
          "txtColor": "#333333"
        }
      }
    });
	PDCookieConsent.blockList([
		 
]);

  </script>
';
}

// Add hook for front-end <head></head>
//add_action( 'wp_head', 'banner_cookies',1 );


/* ============================================================
 *  2. OPTIMIZACIÓN DE RENDIMIENTO
 * ============================================================ */

/* Deshabilitar funciones innecesarias de WordPress para mejorar velocidad */
function disable_unused_wp_features() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
}
add_action('init', 'disable_unused_wp_features');

/* Evitar que jQuery cargue en modo bloqueante */
function optimize_kotlis_scripts() {
    if (!is_admin()) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'optimize_kotlis_scripts');

function defer_js_parsing() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            let scripts = document.querySelectorAll("script[data-defer]");
            scripts.forEach(script => {
                let newScript = document.createElement("script");
                newScript.src = script.src;
                document.body.appendChild(newScript);
                script.remove();
            });
        });
    </script>';
}
add_action('wp_footer', 'defer_js_parsing');

/* Forzar carga diferida de imágenes (Lazy Load avanzado) */
function custom_lazy_load() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            let images = document.querySelectorAll("img.lazyload");
            let observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        let img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove("lazyload");
                        observer.unobserve(img);
                    }
                });
            });
            images.forEach(img => observer.observe(img));
        });
    </script>';
}
add_action('wp_footer', 'custom_lazy_load');

/* ============================================================
 *  3. MEJORAS VISUALES (UI/UX)
 * ============================================================ */

/* Añadir efecto de carga a imágenes */
function add_image_loader() {
    echo '<style>
        .lazyload { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .lazyload.loaded { opacity: 1; }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let images = document.querySelectorAll("img.lazyload");
            images.forEach(img => {
                img.onload = () => img.classList.add("loaded");
            });
        });
    </script>';
}
add_action('wp_footer', 'add_image_loader');

/* Habilitar modo oscuro en la barra de direcciones móvil */
function address_mobile_address_bar() {
    $color = "#161616";
    echo '<meta name="theme-color" content="' . $color . '">';
    echo '<meta name="msapplication-navbutton-color" content="' . $color . '">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
}
add_action('wp_head', 'address_mobile_address_bar');

/* ============================================================
 *  4. FUNCIONALIDADES EXTRA
 * ============================================================ */

/* Agregar botones de compartir en redes sociales dentro de las páginas de portfolio */
function add_social_sharing_buttons($content) {
    if (is_singular('portfolio')) {
        $image_url = get_the_post_thumbnail_url();
        if ($image_url) {
            $content .= '<p>
                <a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($image_url) . '" target="_blank">📘 Compartir en Facebook</a> | 
                <a href="https://twitter.com/intent/tweet?url=' . urlencode($image_url) . '" target="_blank">🐦 Compartir en Twitter</a>
            </p>';
        }
    }
    return $content;
}
add_filter('the_content', 'add_social_sharing_buttons');

/* Precargar la galería con Flickity */
function fix_kotlis_gallery() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof galleryInit === "function") {
                galleryInit();
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            let gallery = document.querySelector(".gallery");
            if (gallery) {
                new Flickity(gallery, {
                    cellAlign: "center",
                    wrapAround: true,
                    freeScroll: true,
                    autoPlay: 4000,
                    lazyLoad: 2
                });
            }
        });
    </script>';
}
add_action('wp_footer', 'fix_kotlis_gallery');

/* ============================================================
 *  5. OPTIMIZACIÓN DE IMÁGENES
 * ============================================================ */

/*
 * NOTA: la función serve_webp_images() se eliminó el 2026-05-08.
 * El filtro reemplazaba ciegamente .jpg/.png por .webp en todo
 * the_content sin verificar que el archivo .webp existiera, lo que
 * rompía cualquier imagen sin versión WebP generada (incluido el
 * shortcode [yzmf_slider]). LiteSpeed Cache ya hace conversión a
 * WebP en frontend con verificación correcta, así que esta función
 * era redundante y peligrosa.
 */

/* Agregar Datos Estructurados para SEO (Schema Markup)*/
function add_image_schema() {
    if (is_singular('portfolio')) {
        $image_url = get_the_post_thumbnail_url();
        if ($image_url) {
            echo '<script type="application/ld+json">
            {
                "@context": "https://schema.org/",
                "@type": "ImageObject",
                "contentUrl": "' . esc_url($image_url) . '",
                "author": {
                    "@type": "Person",
                    "name": "' . get_the_author() . '"
                },
                "datePublished": "' . get_the_date('c') . '"
            }
            </script>';
        }
    }
}
add_action('wp_head', 'add_image_schema');


/* ============================================================
 *  7. FUNCIONES DESACTIVADAS
 * ============================================================ */

function disable_kotlis_preloader() {
    wp_dequeue_script('kotlis-preloader'); // Sustituye 'kotlis-preloader' por el nombre real
}
add_action('wp_enqueue_scripts', 'disable_kotlis_preloader', 100);


add_filter('wp_generate_attachment_metadata', 'generar_meta_imagen_con_IA', 10, 2);

function generar_meta_imagen_con_IA($metadata, $attachment_id) {
    $image_url = wp_get_attachment_url($attachment_id);
    if (!$image_url) return $metadata;

    // La clave se carga desde una opción WP (Settings o wp_options en BD).
    // NUNCA pongas la API key en código fuente — quedaría expuesta en el repo.
    $api_key = trim( (string) get_option( 'ypva_openai_api_key', '' ) );
    if ( ! $api_key && defined( 'YPVA_OPENAI_API_KEY' ) ) $api_key = YPVA_OPENAI_API_KEY;
    if ( ! $api_key ) return $metadata;  // sin clave, no se genera meta IA

    $prompt = "Describe esta imagen en español para SEO. Genera ALT, título, caption y descripción:\n".$image_url;

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            "model" => "gpt-4o-mini",
            "messages" => [
                ["role"=>"system", "content"=>"Eres un generador de metadatos SEO para imágenes."],
                ["role"=>"user", "content"=>$prompt]
            ],
            "max_tokens" => 300
        ])
    ]);

    if (is_wp_error($response)) return $metadata;

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $text = trim($body['choices'][0]['message']['content'] ?? '');

    // Analiza salida (puedes ajustar el formato requerido)
    $parts = explode("\n", $text);
    $alt = sanitize_text_field($parts[0] ?? '');
    $title = sanitize_text_field($parts[1] ?? '');
    $caption = sanitize_text_field($parts[2] ?? '');
    $desc = sanitize_textarea_field($parts[3] ?? '');

    // Guarda en WP
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
    wp_update_post([
        'ID'         => $attachment_id,
        'post_title' => $title,
        'post_excerpt' => $caption,   // caption
        'post_content' => $desc       // description
    ]);

    return $metadata;
}




/* ============================================================
 *  8. PLANTILLA "ELEMENTOR PANTALLA COMPLETA"
 * ============================================================ */

/**
 * Carga el CSS de la plantilla Elementor Wide solo cuando se usa.
 * Prioridad 100 para asegurar que se aplica después del CSS del padre.
 */
function ypva_child_elementor_wide_styles() {
    if ( is_page_template( 'page-elementor-wide.php' ) ) {
        wp_enqueue_style(
            'ypva-elementor-wide',
            get_stylesheet_directory_uri() . '/assets/css/elementor-wide.css',
            array(),
            wp_get_theme()->get( 'Version' )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'ypva_child_elementor_wide_styles', 100 );


/* ============================================================
 *  9. OCULTAR METABOXES DEL TEMA EN PÁGINAS "ELEMENTOR WIDE"
 * ============================================================ */

/**
 * El plugin Kotlis registra metaboxes condicionados por el valor de
 * meta keys como rnr_wr_intro_sc_opt o rnr_wr_pagetype, no por la
 * plantilla activa. Resultado: en la portada (que usa la plantilla
 * Elementor Pantalla Completa) siguen apareciendo los paneles del
 * slider de portada y de los layouts del intro section, aunque ya
 * no se renderizan en frontend.
 *
 * Cuando la plantilla activa es 'page-elementor-wide.php', filtramos
 * los metaboxes del tema que dependen de esos modos para que no
 * estorben en el editor.
 */
add_filter( 'rwmb_meta_boxes', 'ypva_child_hide_theme_metaboxes_in_elementor_wide', 999 );
function ypva_child_hide_theme_metaboxes_in_elementor_wide( $metaboxes ) {
    if ( ! is_admin() ) return $metaboxes;

    // Detectar la página actual del editor (post.php o post-new.php)
    $post_id = 0;
    if ( ! empty( $_GET['post'] ) ) {
        $post_id = (int) $_GET['post'];
    } elseif ( ! empty( $_POST['post_ID'] ) ) {
        $post_id = (int) $_POST['post_ID'];
    }
    if ( ! $post_id ) return $metaboxes;

    $template = get_post_meta( $post_id, '_wp_page_template', true );
    if ( $template !== 'page-elementor-wide.php' ) return $metaboxes;

    $hide_keys = array(
        '#rnr_wr_intro_sc_opt',
        '#rnr_wr_pagetype',
    );

    return array_values( array_filter( $metaboxes, function ( $mb ) use ( $hide_keys ) {
        $show = isset( $mb['show']['input_value'] ) && is_array( $mb['show']['input_value'] )
            ? $mb['show']['input_value']
            : array();
        foreach ( $show as $key => $value ) {
            if ( in_array( $key, $hide_keys, true ) ) {
                return false;
            }
        }
        return true;
    } ) );
}


/* ============================================================
 *  10. EXPONER METAS DEL TEMA EN REST API
 * ============================================================ */

/**
 * Registra metas del tema YPVA con prefijo rnr_ como REST-accessible.
 * Permite editarlos vía /wp/v2/pages/{id} pasando el campo "meta".
 *
 * - rnr_page_layout: st1 sidebar derecha, st2 sidebar izquierda, st3 full, vacío default
 * - rnr_page_header_block: 'no' oculta la cabecera con título e imagen destacada
 * - rnr_wr_pagetype: st1 default, st2 sideblock (imagen lateral fija)
 */
function ypva_child_register_theme_meta_rest() {
    $meta_keys = array(
        'rnr_page_layout',
        'rnr_page_header_block',
        'rnr_wr_pagetype',
    );

    foreach ($meta_keys as $key) {
        register_post_meta('page', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));
    }
}
add_action('init', 'ypva_child_register_theme_meta_rest');


/* ============================================================
 *  ✅ FIN DEL ARCHIVO
 * ============================================================ */
?>
