<?php
/**
 * Metabox y columnas en wp-admin para gestionar las galerías.
 * (UI más rica está pensada para integrarse en la PWA con los endpoints
 *  /yzmf/v1/cp/admin/* — esto es solo el fallback en wp-admin)
 */

defined( 'ABSPATH' ) || exit;

class YZMF_CP_Admin {

    public static function init() {
        add_action( 'add_meta_boxes_' . YZMF_CP_CPT::POST_TYPE, [ __CLASS__, 'add_metabox' ] );
        add_action( 'save_post_' . YZMF_CP_CPT::POST_TYPE, [ __CLASS__, 'save_metabox' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );

        add_filter( 'manage_' . YZMF_CP_CPT::POST_TYPE . '_posts_columns', [ __CLASS__, 'columns' ] );
        add_action( 'manage_' . YZMF_CP_CPT::POST_TYPE . '_posts_custom_column', [ __CLASS__, 'col_value' ], 10, 2 );

        // Settings page → email destinatario
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
    }

    public static function enqueue_admin_assets( $hook ) {
        // Solo en pantallas de edición del CPT
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== YZMF_CP_CPT::POST_TYPE ) return;
        wp_enqueue_media();
        // JS inline (pequeño, no merece archivo dedicado)
        add_action( 'admin_print_footer_scripts', [ __CLASS__, 'metabox_js' ] );
    }

    public static function metabox_js() {
        ?>
        <script>
        (function ($) {
            $(document).on('click', '.cp-pick-images', function (e) {
                e.preventDefault();
                var ta = $('textarea[name="cp_images"]');
                var preview = $('.cp-images-preview');
                var existing = (ta.val() || '').split(',').map(function (x) { return parseInt(x, 10); }).filter(Boolean);

                var frame = wp.media({
                    title: 'Selecciona imágenes para la galería',
                    button: { text: 'Añadir a la galería' },
                    multiple: true,
                    library: { type: 'image' },
                });
                frame.on('select', function () {
                    var sel = frame.state().get('selection').toJSON();
                    var newIds = sel.map(function (a) { return a.id; });
                    var combined = existing.concat(newIds.filter(function (id) { return existing.indexOf(id) === -1; }));
                    ta.val(combined.join(','));
                    renderPreview(combined, sel);
                });
                frame.open();
            });

            $(document).on('click', '.cp-clear-images', function (e) {
                e.preventDefault();
                if (!confirm('¿Quitar todas las imágenes? (no se borran del catálogo)')) return;
                $('textarea[name="cp_images"]').val('');
                $('.cp-images-preview').empty();
            });

            $(document).on('click', '.cp-rm-img', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                var ta = $('textarea[name="cp_images"]');
                var ids = (ta.val() || '').split(',').map(function (x) { return parseInt(x, 10); }).filter(function (x) { return x && x !== id; });
                ta.val(ids.join(','));
                $(this).closest('.cp-prev-card').remove();
            });

            // Combina previos del backend con nuevos del frame
            function renderPreview(allIds, freshSelection) {
                var container = $('.cp-images-preview');
                // Map de ID -> URL para los recién seleccionados
                var map = {};
                (freshSelection || []).forEach(function (a) {
                    map[a.id] = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
                });
                // Reusa los que ya estaban en el preview
                container.find('.cp-prev-card').each(function () {
                    var id = parseInt($(this).data('id'), 10);
                    if (id && allIds.indexOf(id) >= 0 && !map[id]) {
                        map[id] = $(this).find('img').attr('src');
                    }
                });
                container.empty();
                allIds.forEach(function (id) {
                    var src = map[id] || '';
                    container.append(
                        '<div class="cp-prev-card" data-id="' + id + '">' +
                        '  <img src="' + src + '" />' +
                        '  <button type="button" class="cp-rm-img" data-id="' + id + '" title="Quitar">×</button>' +
                        '</div>'
                    );
                });
            }
        })(jQuery);
        </script>
        <style>
        .cp-images-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 6px; margin: 12px 0; }
        .cp-prev-card { position: relative; aspect-ratio: 1; background: #eee; border-radius: 4px; overflow: hidden; }
        .cp-prev-card img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .cp-rm-img { position: absolute; top: 2px; right: 2px; width: 22px; height: 22px; border-radius: 50%; background: rgba(0,0,0,.65); color: white; border: 0; font-size: 14px; cursor: pointer; padding: 0; line-height: 1; }
        .cp-rm-img:hover { background: #c0392b; }
        .cp-pwa-link { background: #d6e9f7; border-left: 4px solid #2271b1; padding: 12px; margin: 0 0 12px; border-radius: 0 4px 4px 0; }
        .cp-pwa-link a { font-weight: 600; }
        </style>
        <?php
    }

    public static function menu() {
        add_submenu_page(
            'edit.php?post_type=' . YZMF_CP_CPT::POST_TYPE,
            'Ajustes Portal',
            'Ajustes',
            'manage_options',
            'yzmf-cp-settings',
            [ __CLASS__, 'render_settings' ]
        );
    }

    public static function register_settings() {
        register_setting( 'yzmf_cp', 'yzmf_cp_owner_email', [ 'sanitize_callback' => 'sanitize_email' ] );
    }

    public static function render_settings() {
        ?>
        <div class="wrap">
            <h1>Ajustes Portal de Cliente</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'yzmf_cp' ); ?>
                <table class="form-table">
                    <tr>
                        <th>Email para notificaciones</th>
                        <td>
                            <input type="email" name="yzmf_cp_owner_email" class="regular-text"
                                value="<?php echo esc_attr( get_option( 'yzmf_cp_owner_email', get_option( 'admin_email' ) ) ); ?>" />
                            <p class="description">Recibirás un email cuando el cliente marque favoritas o deje comentarios.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /* ─────────── Metabox ─────────── */

    public static function add_metabox() {
        add_meta_box( 'yzmf_cp_meta', 'Configuración de la galería',
            [ __CLASS__, 'render_metabox' ], YZMF_CP_CPT::POST_TYPE, 'normal', 'high' );
        add_meta_box( 'yzmf_cp_share', 'Compartir',
            [ __CLASS__, 'render_share' ], YZMF_CP_CPT::POST_TYPE, 'side', 'high' );
        add_meta_box( 'yzmf_cp_actions', 'Actividad del cliente',
            [ __CLASS__, 'render_actions' ], YZMF_CP_CPT::POST_TYPE, 'side', 'default' );
    }

    public static function render_share( $post ) {
        $token = get_post_meta( $post->ID, '_yzmf_cp_token', true );
        if ( ! $token ) {
            echo '<p>Guarda primero la galería para generar el enlace.</p>';
            return;
        }
        $url = home_url( '/g/' . $token );
        $views = (int) get_post_meta( $post->ID, '_yzmf_cp_views', true );
        ?>
        <p><strong>Enlace público:</strong></p>
        <input type="text" readonly value="<?php echo esc_attr( $url ); ?>"
            style="width:100%; font-size:11px; padding:6px;"
            onclick="this.select()" />
        <p class="howto">Copia y envía al cliente.</p>
        <p><strong>Visitas:</strong> <?php echo $views; ?></p>
        <?php
    }

    public static function render_actions( $post ) {
        $favs    = YZMF_CP_CPT::get_actions( $post->ID, 'favorite' );
        $comments = YZMF_CP_CPT::get_actions( $post->ID, 'comment' );
        echo '<p><strong>' . count( $favs ) . '</strong> favoritas · <strong>' . count( $comments ) . '</strong> comentarios</p>';

        if ( count( $favs ) ) {
            echo '<h4>Favoritas</h4><ul>';
            foreach ( array_slice( $favs, 0, 10 ) as $a ) {
                $aid = (int) get_post_meta( $a->ID, '_att_id', true );
                $thumb = wp_get_attachment_image_url( $aid, 'thumbnail' );
                echo '<li style="display:inline-block;margin:2px"><a href="' . esc_url( get_edit_post_link( $aid ) ) . '"><img src="' . esc_url( $thumb ) . '" width="60" height="60" /></a></li>';
            }
            echo '</ul>';
        }

        if ( count( $comments ) ) {
            echo '<h4>Comentarios recientes</h4>';
            foreach ( array_slice( $comments, 0, 5 ) as $a ) {
                $payload = json_decode( $a->post_content, true );
                $text = is_array( $payload ) ? $payload['text'] : $a->post_content;
                $aid = (int) get_post_meta( $a->ID, '_att_id', true );
                echo '<p style="font-size:11px;border-left:2px solid #ccc;padding-left:8px;margin:6px 0"><strong>#' . $aid . '</strong>: ' . esc_html( $text ) . '<br /><em>' . esc_html( get_the_date( '', $a ) ) . '</em></p>';
            }
        }
    }

    public static function render_metabox( $post ) {
        wp_nonce_field( 'yzmf_cp_save', 'yzmf_cp_nonce' );
        $images = YZMF_CP_CPT::get_images( $post );
        $expires = (int) get_post_meta( $post->ID, '_yzmf_cp_expires', true );

        // Aviso de la PWA al inicio del metabox (UX recomendada)
        $pwa_url = 'https://app.yezraelperez.es/client-galleries/' . $post->ID;
        ?>
        <div class="cp-pwa-link">
            ✨ <strong>Para una gestión más cómoda</strong>, edita esta galería desde la PWA:
            <a href="<?php echo esc_url( $pwa_url ); ?>" target="_blank">
                Abrir en app.yezraelperez.es ↗
            </a>
        </div>
        <?php
        $allow_dl = (bool) get_post_meta( $post->ID, '_yzmf_cp_allow_download', true );
        $allow_co = (bool) get_post_meta( $post->ID, '_yzmf_cp_allow_comments', true );
        $client_name = (string) get_post_meta( $post->ID, '_yzmf_cp_client_name', true );
        $client_email = (string) get_post_meta( $post->ID, '_yzmf_cp_client_email', true );
        $msg = (string) get_post_meta( $post->ID, '_yzmf_cp_message', true );
        $has_pwd = (bool) get_post_meta( $post->ID, '_yzmf_cp_password', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label>Nombre del cliente</label></th>
                <td><input type="text" name="cp_client_name" class="regular-text" value="<?php echo esc_attr( $client_name ); ?>" /></td>
            </tr>
            <tr>
                <th><label>Email del cliente</label></th>
                <td><input type="email" name="cp_client_email" class="regular-text" value="<?php echo esc_attr( $client_email ); ?>" /></td>
            </tr>
            <tr>
                <th><label>Mensaje (visible al cliente)</label></th>
                <td><textarea name="cp_message" rows="3" class="large-text"><?php echo esc_textarea( $msg ); ?></textarea></td>
            </tr>
            <tr>
                <th><label>Contraseña</label></th>
                <td>
                    <input type="password" name="cp_password" placeholder="<?php echo $has_pwd ? '(definida — escribe nueva para cambiar)' : 'Dejar vacío = sin contraseña'; ?>" />
                    <?php if ( $has_pwd ): ?>
                        <label style="margin-left:12px"><input type="checkbox" name="cp_clear_password" /> Quitar contraseña</label>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label>Caducidad</label></th>
                <td>
                    <input type="date" name="cp_expires" value="<?php echo $expires ? esc_attr( date( 'Y-m-d', $expires ) ) : ''; ?>" />
                    <span class="description">Vacío = nunca caduca</span>
                </td>
            </tr>
            <tr>
                <th>Permisos</th>
                <td>
                    <label><input type="checkbox" name="cp_allow_download" value="1" <?php checked( $allow_dl ); ?> /> Permitir descarga</label><br />
                    <label><input type="checkbox" name="cp_allow_comments" value="1" <?php checked( $allow_co ); ?> /> Permitir comentarios</label>
                </td>
            </tr>
            <tr>
                <th>Imágenes</th>
                <td>
                    <p>
                        <button type="button" class="button button-primary cp-pick-images">+ Añadir imágenes</button>
                        <?php if ( $images ): ?>
                            <button type="button" class="button cp-clear-images">Vaciar</button>
                        <?php endif; ?>
                    </p>
                    <div class="cp-images-preview">
                        <?php foreach ( $images as $img_id ):
                            $thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                            if ( ! $thumb ) continue;
                            ?>
                            <div class="cp-prev-card" data-id="<?php echo (int) $img_id; ?>">
                                <img src="<?php echo esc_url( $thumb ); ?>" />
                                <button type="button" class="cp-rm-img" data-id="<?php echo (int) $img_id; ?>" title="Quitar">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <textarea name="cp_images" class="large-text" style="font-family:monospace;font-size:11px"><?php echo esc_textarea( implode( ',', $images ) ); ?></textarea>
                    <p class="description">Los IDs se actualizan automáticamente al añadir/quitar imágenes con el botón de arriba.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function save_metabox( $post_id, $post ) {
        if ( ! isset( $_POST['yzmf_cp_nonce'] ) || ! wp_verify_nonce( $_POST['yzmf_cp_nonce'], 'yzmf_cp_save' ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( wp_is_post_autosave( $post_id ) ) return;

        update_post_meta( $post_id, '_yzmf_cp_client_name',  sanitize_text_field( $_POST['cp_client_name'] ?? '' ) );
        update_post_meta( $post_id, '_yzmf_cp_client_email', sanitize_email( $_POST['cp_client_email'] ?? '' ) );
        update_post_meta( $post_id, '_yzmf_cp_message',      wp_kses_post( $_POST['cp_message'] ?? '' ) );
        update_post_meta( $post_id, '_yzmf_cp_allow_download', ! empty( $_POST['cp_allow_download'] ) );
        update_post_meta( $post_id, '_yzmf_cp_allow_comments', ! empty( $_POST['cp_allow_comments'] ) );

        if ( ! empty( $_POST['cp_clear_password'] ) ) {
            delete_post_meta( $post_id, '_yzmf_cp_password' );
        } elseif ( ! empty( $_POST['cp_password'] ) ) {
            update_post_meta( $post_id, '_yzmf_cp_password', wp_hash_password( $_POST['cp_password'] ) );
        }

        $exp_str = sanitize_text_field( $_POST['cp_expires'] ?? '' );
        if ( $exp_str ) {
            update_post_meta( $post_id, '_yzmf_cp_expires', strtotime( $exp_str . ' 23:59:59' ) );
        } else {
            delete_post_meta( $post_id, '_yzmf_cp_expires' );
        }

        $ids_raw = sanitize_text_field( $_POST['cp_images'] ?? '' );
        $ids = array_filter( array_map( 'intval', preg_split( '/[\s,]+/', $ids_raw ) ) );
        YZMF_CP_CPT::set_images( $post_id, $ids );
    }

    /* ─────────── Columnas en lista ─────────── */

    public static function columns( $cols ) {
        $new = [];
        foreach ( $cols as $k => $v ) {
            $new[ $k ] = $v;
            if ( $k === 'title' ) {
                $new['cp_client'] = 'Cliente';
                $new['cp_images'] = 'Imágenes';
                $new['cp_views']  = 'Visitas';
                $new['cp_favs']   = 'Favoritas';
                $new['cp_link']   = 'Enlace';
            }
        }
        return $new;
    }

    public static function col_value( $col, $post_id ) {
        switch ( $col ) {
            case 'cp_client':
                echo esc_html( get_post_meta( $post_id, '_yzmf_cp_client_name', true ) );
                break;
            case 'cp_images':
                echo count( YZMF_CP_CPT::get_images( get_post( $post_id ) ) );
                break;
            case 'cp_views':
                echo (int) get_post_meta( $post_id, '_yzmf_cp_views', true );
                break;
            case 'cp_favs':
                echo count( YZMF_CP_CPT::get_actions( $post_id, 'favorite' ) );
                break;
            case 'cp_link':
                $token = get_post_meta( $post_id, '_yzmf_cp_token', true );
                if ( $token ) {
                    $url = home_url( '/g/' . $token );
                    echo '<a href="' . esc_url( $url ) . '" target="_blank">Abrir ↗</a>';
                }
                break;
        }
    }
}
