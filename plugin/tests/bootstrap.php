<?php
/**
 * Bootstrap PHPUnit del plugin.
 *
 * Carga Brain Monkey para mockear WordPress en tests UNIT (sin DB).
 * Para los tests INTEGRATION (suite "integration") se debe instalar la suite
 * de WordPress con:
 *   composer test:install
 * y luego correr ./vendor/bin/phpunit --testsuite=integration
 */

$pluginDir = dirname( __DIR__ );

// Autoload de Composer
require_once $pluginDir . '/vendor/autoload.php';

// Detectar si la suite es UNIT (sin WP) o INTEGRATION (con wp-tests)
$is_integration = false;
foreach ( $_SERVER['argv'] ?? [] as $arg ) {
    if ( strpos( $arg, '--testsuite' ) !== false && strpos( $arg, 'integration' ) !== false ) {
        $is_integration = true;
        break;
    }
}

if ( $is_integration ) {
    // Cargar la suite oficial de WordPress (instalada por bin/install-wp-tests.sh)
    $_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';
    if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
        echo "WordPress test suite no encontrada en $_tests_dir." . PHP_EOL;
        echo "Ejecuta: composer test:install" . PHP_EOL;
        exit( 1 );
    }
    require_once $_tests_dir . '/includes/functions.php';

    tests_add_filter( 'muplugins_loaded', function() use ( $pluginDir ) {
        require_once $pluginDir . '/yz-media-folders.php';
    } );

    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // Modo UNIT con Brain Monkey: stubs de constantes WP que el plugin asume.
    if ( ! defined( 'ABSPATH' )       ) define( 'ABSPATH', __DIR__ );
    if ( ! defined( 'YZMF_VERSION' )  ) define( 'YZMF_VERSION', 'test' );
    if ( ! defined( 'YZMF_DIR' )      ) define( 'YZMF_DIR', $pluginDir . '/' );
    if ( ! defined( 'YZMF_URL' )      ) define( 'YZMF_URL', 'http://example.com/wp-content/plugins/yz-media-folders/' );
    if ( ! defined( 'YZMF_TAXONOMY' ) ) define( 'YZMF_TAXONOMY', 'yz_media_folder' );
    if ( ! defined( 'MINUTE_IN_SECONDS' ) ) define( 'MINUTE_IN_SECONDS', 60 );
    if ( ! defined( 'HOUR_IN_SECONDS' )   ) define( 'HOUR_IN_SECONDS', 3600 );
    if ( ! defined( 'DAY_IN_SECONDS' )    ) define( 'DAY_IN_SECONDS', 86400 );
}
