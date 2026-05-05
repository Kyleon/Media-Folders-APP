<?php
/**
 * Pantalla de ajustes del plugin Hotlink Watermark.
 * Settings → YZMF Hotlink
 */

defined( 'ABSPATH' ) || exit;

class YZMF_HW_Admin {

    public static function init() {
        add_action( 'admin_menu',  [ __CLASS__, 'menu' ] );
        add_action( 'admin_init',  [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_post_yzmf_hw_clear_cache', [ __CLASS__, 'clear_cache_action' ] );
    }

    public static function menu() {
        add_options_page(
            'YZMF Hotlink Watermark',
            'YZMF Hotlink',
            'manage_options',
            'yzmf-hotlink',
            [ __CLASS__, 'render' ]
        );
    }

    public static function register_settings() {
        $opts = [
            'yzmf_hw_text'                  => 'sanitize_text_field',
            'yzmf_hw_image'                 => 'sanitize_text_field',
            'yzmf_hw_opacity'               => 'absint',
            'yzmf_hw_position'              => 'sanitize_text_field',
            'yzmf_hw_whitelist'             => 'sanitize_textarea_field',
            'yzmf_hw_block_empty_referer'   => 'rest_sanitize_boolean',
            'yzmf_hw_allow_search_engines'  => 'rest_sanitize_boolean',
        ];
        foreach ( $opts as $name => $cb ) {
            register_setting( 'yzmf_hw', $name, [ 'sanitize_callback' => $cb ] );
        }
    }

    public static function clear_cache_action() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );
        check_admin_referer( 'yzmf_hw_clear_cache' );
        $count = YZMF_HW_Handler::clear_cache();
        wp_safe_redirect( add_query_arg( 'cleared', $count,
            admin_url( 'options-general.php?page=yzmf-hotlink' ) ) );
        exit;
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $opacity = (int) get_option( 'yzmf_hw_opacity', 60 );
        $position = (string) get_option( 'yzmf_hw_position', 'br' );
        ?>
        <div class="wrap">
            <h1>YZMF Hotlink Watermark</h1>

            <?php if ( isset( $_GET['cleared'] ) ): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Caché vaciada (<?php echo (int) $_GET['cleared']; ?> archivos).</p>
                </div>
            <?php endif; ?>

            <p>Cuando alguien enlaza directamente una imagen tuya desde un sitio externo
               (no incluido en la whitelist), se sirve una versión con marca de agua
               en lugar del original.</p>

            <form method="post" action="options.php">
                <?php settings_fields( 'yzmf_hw' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Texto de marca de agua</th>
                        <td>
                            <input type="text" name="yzmf_hw_text" class="regular-text"
                                value="<?php echo esc_attr( get_option( 'yzmf_hw_text', '© ' . get_bloginfo( 'name' ) ) ); ?>" />
                            <p class="description">Si dejas vacío y configuras una imagen, se usa la imagen.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Imagen de marca de agua (PNG con transparencia)</th>
                        <td>
                            <input type="text" name="yzmf_hw_image" class="regular-text"
                                value="<?php echo esc_attr( get_option( 'yzmf_hw_image' ) ); ?>"
                                placeholder="<?php echo esc_attr( WP_CONTENT_DIR . '/uploads/watermark.png' ); ?>" />
                            <p class="description">Path absoluto en el servidor. Requiere extensión Imagick.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Opacidad (%)</th>
                        <td><input type="number" name="yzmf_hw_opacity" min="10" max="100" value="<?php echo $opacity; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Posición</th>
                        <td>
                            <select name="yzmf_hw_position">
                                <?php foreach ( [
                                    'br' => 'Inferior derecha',
                                    'bl' => 'Inferior izquierda',
                                    'tr' => 'Superior derecha',
                                    'tl' => 'Superior izquierda',
                                    'center' => 'Centro',
                                    'tile' => 'Mosaico (toda la imagen)',
                                ] as $code => $label ): ?>
                                    <option value="<?php echo $code; ?>" <?php selected( $position, $code ); ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Dominios permitidos (whitelist)</th>
                        <td>
                            <textarea name="yzmf_hw_whitelist" rows="4" class="large-text"
                                placeholder="ejemplo.com&#10;app.yezraelperez.es"><?php echo esc_textarea( get_option( 'yzmf_hw_whitelist', '' ) ); ?></textarea>
                            <p class="description">Uno por línea. El propio dominio (<?php echo esc_html( parse_url( home_url(), PHP_URL_HOST ) ); ?>) ya está incluido.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Permitir Referer vacío</th>
                        <td>
                            <label><input type="checkbox" name="yzmf_hw_block_empty_referer" value="1" <?php checked( ! get_option( 'yzmf_hw_block_empty_referer', false ) ); ?> />
                                Servir originales cuando la petición no tiene Referer (escribir URL directa, RSS, etc.)</label>
                            <p class="description">Marcado = permitir. Desmarcar es más estricto pero puede romper apps legítimas.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Permitir motores y previsualizadores</th>
                        <td>
                            <label><input type="checkbox" name="yzmf_hw_allow_search_engines" value="1" <?php checked( get_option( 'yzmf_hw_allow_search_engines', true ) ); ?> />
                                Google Images, Facebook, Twitter, etc. ven el original (mejor para SEO)</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr />
            <h2>Caché</h2>
            <p>Las imágenes con marca de agua se generan al vuelo y se cachean en
               <code><?php echo esc_html( YZMF_HW_CACHE_DIR ); ?></code>.</p>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <?php wp_nonce_field( 'yzmf_hw_clear_cache' ); ?>
                <input type="hidden" name="action" value="yzmf_hw_clear_cache" />
                <?php submit_button( 'Vaciar caché', 'secondary', '', false ); ?>
            </form>

            <hr />
            <h2>Test rápido</h2>
            <p>Desde un dominio externo, intenta cargar:
                <code><?php echo esc_url( WP_CONTENT_URL . '/uploads/.../tuimagen.jpg' ); ?></code>.
                Debería verse con marca de agua. Desde tu propio dominio, sin marca.</p>

            <p><strong>Cómo verificar manualmente</strong>:</p>
            <pre>curl -H "Referer: https://otro-dominio.com/" -o test.jpg \
  "<?php echo esc_url( WP_CONTENT_URL ); ?>/uploads/2024/01/foto.jpg"</pre>
        </div>
        <?php
    }
}
