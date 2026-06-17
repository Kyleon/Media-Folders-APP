<?php
/**
 * Tests para YZMF_Basic_Auth (clase crítica de seguridad).
 *
 * Mocks WordPress con Brain Monkey: no requiere instalación de WP-tests.
 */

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\Polyfills\AssertIsType;

require_once dirname( __DIR__, 2 ) . '/includes/class-basic-auth.php';

class BasicAuthTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_is_enabled_devuelve_false_por_defecto() {
        // Tras el fix de auditoría el default es '0'.
        Functions\when( 'get_option' )->justReturn( '0' );
        $this->assertFalse( YZMF_Basic_Auth::is_enabled() );
    }

    public function test_is_enabled_devuelve_true_cuando_opcion_es_1_string() {
        Functions\when( 'get_option' )->justReturn( '1' );
        $this->assertTrue( YZMF_Basic_Auth::is_enabled() );
    }

    public function test_is_enabled_devuelve_true_cuando_opcion_es_1_int() {
        Functions\when( 'get_option' )->justReturn( 1 );
        $this->assertTrue( YZMF_Basic_Auth::is_enabled() );
    }

    public function test_is_enabled_devuelve_true_cuando_opcion_es_true_bool() {
        Functions\when( 'get_option' )->justReturn( true );
        $this->assertTrue( YZMF_Basic_Auth::is_enabled() );
    }

    public function test_is_enabled_devuelve_false_para_valores_truthy_no_canonicos() {
        // Evita falsos positivos: 'yes', 'on', 2, 'true' deben devolver false.
        foreach ( [ 'yes', 'on', 2, 'true', 'enabled' ] as $val ) {
            Functions\when( 'get_option' )->justReturn( $val );
            $this->assertFalse(
                YZMF_Basic_Auth::is_enabled(),
                "is_enabled() debería devolver false para el valor: " . var_export( $val, true )
            );
        }
    }
}
