<?php
defined( 'ABSPATH' ) || exit;

class YZMF_Admin {

    public static function init() {
        add_action( 'admin_menu',            [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_action( 'admin_init',            [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_notices',         [ __CLASS__, 'maybe_basic_auth_notice' ] );
    }

    /**
     * Aviso visible cuando Basic Auth con contraseña regular está activo.
     * Es un modo de mayor riesgo: la contraseña principal del usuario viaja
     * en cada request REST. Recomendamos Application Passwords.
     */
    public static function maybe_basic_auth_notice() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( ! class_exists( 'YZMF_Basic_Auth' ) || ! YZMF_Basic_Auth::is_enabled() ) return;
        $url = esc_url( admin_url( 'admin.php?page=yz-media-settings' ) );
        echo '<div class="notice notice-warning"><p><strong>YZ Media Folders:</strong> '
           . esc_html__( 'Basic Auth con contraseña regular está ACTIVADO. La contraseña principal viaja en cada request a /wp-json/. Recomendamos Application Passwords y desactivar esta opción.', 'yz-media-folders' )
           . ' <a href="' . $url . '">' . esc_html__( 'Ajustes', 'yz-media-folders' ) . '</a></p></div>';
    }

    public static function register_menu() {
        add_menu_page(
            __( 'Mis Medios', 'yz-media-folders' ),
            __( 'Mis Medios', 'yz-media-folders' ),
            'upload_files',
            'yz-media',
            [ __CLASS__, 'render_page' ],
            'dashicons-images-alt2',
            25
        );
        add_submenu_page(
            'yz-media',
            __( 'Ajustes', 'yz-media-folders' ),
            __( 'Ajustes', 'yz-media-folders' ),
            'manage_options',
            'yz-media-settings',
            [ __CLASS__, 'render_settings' ]
        );
    }

    public static function enqueue( $hook ) {
        if ( $hook !== 'toplevel_page_yz-media' ) return;

        wp_enqueue_media(); // para subida nativa de WP

        wp_enqueue_style(
            'yzmf-main',
            YZMF_URL . 'assets/css/main.css',
            [],
            YZMF_VERSION
        );

        wp_enqueue_script(
            'yzmf-main',
            YZMF_URL . 'assets/js/main.js',
            [ 'jquery' ],
            YZMF_VERSION,
            true
        );

        $flat    = YZMF_Taxonomy::get_flat();
        $folders = [];
        foreach ( $flat as $f ) {
            $folders[] = [
                'id'     => $f->term_id,
                'name'   => $f->name,
                'parent' => $f->parent,
                'count'  => (int) $f->count,
            ];
        }

        wp_localize_script( 'yzmf-main', 'YZMF', [
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'yzmf_nonce' ),
            'upload_url'   => admin_url( 'async-upload.php' ),
            'upload_nonce' => wp_create_nonce( 'media-form' ),
            'tree'         => YZMF_Taxonomy::get_tree(),
            'has_ai'       => ! empty( get_option( 'yzmf_claude_api_key', '' ) ),
            'flat_folders' => $folders,
            'i18n'         => [
                'all_media'      => __( 'Todos los medios', 'yz-media-folders' ),
                'unassigned'     => __( 'Sin carpeta',       'yz-media-folders' ),
                'new_folder'     => __( 'Nueva carpeta',     'yz-media-folders' ),
                'del_folder'     => __( '¿Eliminar esta carpeta? Las imágenes no se borrarán.', 'yz-media-folders' ),
                'del_images'     => __( '¿Eliminar las imágenes seleccionadas? Esta acción no se puede deshacer.', 'yz-media-folders' ),
                'no_folder'      => __( 'Sin carpeta',       'yz-media-folders' ),
                'drop_upload'    => __( 'Suelta para subir a esta carpeta', 'yz-media-folders' ),
                'drop_move'      => __( 'Suelta para mover aquí', 'yz-media-folders' ),
            ],
        ] );
    }

    public static function render_page() {
        // El .wrap de WP se oculta via CSS — solo necesitamos el div del app
        echo '<div id="yzmf-app"></div>';
    }

    public static function render_settings() {
        $api_key   = get_option( 'yzmf_claude_api_key', '' );
        $send_mode = get_option( 'yzmf_ai_send_mode', 'url' );
        $model     = get_option( 'yzmf_ai_model', 'claude-haiku-4-5-20251001' );
        $cors      = get_option( 'yzmf_cors_origins', 'https://app.yezraelperez.es' );
        ?>
        <div class="wrap">
          <h1>⚙️ YZ Media Folders — Ajustes</h1>
          <form method="post" action="options.php">
            <?php settings_fields( 'yzmf_settings_group' ); ?>
            <table class="form-table">
              <tr>
                <th scope="row"><label for="yzmf_claude_api_key">API Key de Claude</label></th>
                <td>
                  <input type="password" id="yzmf_claude_api_key" name="yzmf_claude_api_key"
                    value="<?php echo esc_attr( $api_key ); ?>"
                    class="regular-text" autocomplete="off">
                  <p class="description">
                    Obtén tu API key en
                    <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a>.
                    Se usa para generar alt text y captions automáticamente con IA.
                  </p>
                  <?php if ( $api_key ) : ?>
                    <p><span style="color:#46b450">✅ API key configurada</span></p>
                  <?php else : ?>
                    <p><span style="color:#dc3232">⚠️ Sin API key — la generación de IA no estará disponible</span></p>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <th scope="row"><label for="yzmf_ai_model">Modelo Claude</label></th>
                <td>
                  <input type="text" id="yzmf_ai_model" name="yzmf_ai_model"
                    value="<?php echo esc_attr( $model ); ?>" class="regular-text">
                  <p class="description">
                    ID del modelo de Claude. Recomendado: <code>claude-haiku-4-5-20251001</code> (rápido y económico).
                    Para mejores descripciones puedes usar Sonnet: <code>claude-sonnet-4-6</code>.
                  </p>
                </td>
              </tr>
              <tr>
                <th scope="row"><label for="yzmf_cors_origins">Orígenes CORS permitidos</label></th>
                <td>
                  <input type="text" id="yzmf_cors_origins" name="yzmf_cors_origins"
                    value="<?php echo esc_attr( $cors ); ?>" class="large-text" placeholder="https://app.yezraelperez.es">
                  <p class="description">
                    Orígenes (separados por coma) autorizados a llamar al REST <code>yzmf/v1</code> con Authorization.
                    Necesario para la PWA móvil. Ejemplo: <code>https://app.yezraelperez.es</code>.
                  </p>
                </td>
              </tr>
              <tr>
                <th scope="row"><label for="yzmf_ai_send_mode">Modo de envío de imagen</label></th>
                <td>
                  <select id="yzmf_ai_send_mode" name="yzmf_ai_send_mode">
                    <option value="url"    <?php selected( $send_mode, 'url' );    ?>>URL pública (recomendado)</option>
                    <option value="base64" <?php selected( $send_mode, 'base64' ); ?>>base64 (forzar lectura local)</option>
                  </select>
                  <p class="description">
                    Si tu sitio está en local, staging o detrás de basic-auth, Claude no puede descargar
                    las imágenes desde URL. Cambia a <strong>base64</strong>. En modo <strong>url</strong>
                    el plugin hace fallback automático a base64 si la API rechaza la URL.
                  </p>
                </td>
              </tr>
            </table>
            <?php submit_button( 'Guardar ajustes' ); ?>
          </form>

          <hr style="margin:30px 0">

          <h2>🛠 Mantenimiento</h2>
          <table class="form-table">
            <tr>
              <th scope="row">Recalcular tamaños de archivo</th>
              <td>
                <button type="button" class="button" id="yzmf-backfill-btn">▶ Iniciar backfill</button>
                <span id="yzmf-backfill-status" style="margin-left:12px;color:#5a5a5a"></span>
                <p class="description">
                  Rellena el meta <code>_yzmf_filesize</code> en attachments antiguos.
                  Necesario para que el orden por "Tamaño" funcione con archivos subidos antes de v2.4.
                  Procesa en lotes de 50.
                </p>
              </td>
            </tr>
          </table>
        </div>

        <script>
        (function () {
          var btn    = document.getElementById('yzmf-backfill-btn');
          var status = document.getElementById('yzmf-backfill-status');
          if (!btn) return;
          var nonce  = '<?php echo esc_js( wp_create_nonce( 'yzmf_nonce' ) ); ?>';
          var ajax   = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
          var totalProcessed = 0, totalErrors = 0;

          function tick(offset) {
            var fd = new FormData();
            fd.append('action', 'yzmf_backfill_filesizes');
            fd.append('nonce', nonce);
            fd.append('offset', offset);
            fetch(ajax, { method: 'POST', credentials: 'same-origin', body: fd })
              .then(function (r) { return r.json(); })
              .then(function (res) {
                if (!res.success) {
                  status.style.color = '#dc3232';
                  status.textContent = '⚠ ' + (res.data && res.data.message ? res.data.message : 'Error');
                  btn.disabled = false;
                  return;
                }
                totalProcessed += res.data.processed;
                totalErrors    += res.data.errors;
                status.textContent = '⏳ ' + totalProcessed + ' procesados · ' + res.data.remaining + ' restantes' + (totalErrors ? ' · ' + totalErrors + ' con error' : '');
                if (res.data.done) {
                  status.style.color = '#46b450';
                  status.textContent = '✓ Completado · ' + totalProcessed + ' procesados' + (totalErrors ? ' · ' + totalErrors + ' archivos no encontrados' : '');
                  btn.disabled = false;
                  btn.textContent = '▶ Reiniciar backfill';
                  return;
                }
                tick(offset + res.data.processed + res.data.errors);
              })
              .catch(function () {
                status.style.color = '#dc3232';
                status.textContent = '⚠ Error de red';
                btn.disabled = false;
              });
          }

          btn.addEventListener('click', function () {
            btn.disabled = true;
            status.style.color = '#5a5a5a';
            status.textContent = '⏳ Iniciando…';
            totalProcessed = 0; totalErrors = 0;
            tick(0);
          });
        })();
        </script>
        <?php
    }

    public static function register_settings() {
        register_setting( 'yzmf_settings_group', 'yzmf_claude_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        register_setting( 'yzmf_settings_group', 'yzmf_ai_model', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );
        register_setting( 'yzmf_settings_group', 'yzmf_ai_send_mode', [
            'sanitize_callback' => function( $v ) {
                return in_array( $v, [ 'url', 'base64' ], true ) ? $v : 'url';
            },
        ] );
        register_setting( 'yzmf_settings_group', 'yzmf_cors_origins', [
            'sanitize_callback' => function( $v ) {
                $list = array_filter( array_map( 'trim', explode( ',', (string) $v ) ) );
                // Solo orígenes HTTPS o http://localhost para evitar abrir el grifo
                // a orígenes inseguros si el admin se equivoca.
                $list = array_filter( $list, function( $u ) {
                    if ( ! wp_http_validate_url( $u ) ) return false;
                    $p = wp_parse_url( $u );
                    if ( ! empty( $p['scheme'] ) && $p['scheme'] === 'https' ) return true;
                    if ( ! empty( $p['scheme'] ) && $p['scheme'] === 'http'
                        && ! empty( $p['host'] ) && in_array( $p['host'], [ 'localhost', '127.0.0.1' ], true ) ) return true;
                    return false;
                } );
                return implode( ', ', $list );
            },
        ] );
        register_setting( 'yzmf_settings_group', 'yzmf_enable_basic_auth', [
            'sanitize_callback' => function( $v ) {
                return ( $v === '1' || $v === 1 || $v === true ) ? '1' : '0';
            },
        ] );
    }
}
