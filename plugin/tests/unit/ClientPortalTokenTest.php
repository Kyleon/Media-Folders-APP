<?php
/**
 * Tests para YZMF_CP_CPT::generate_token (entropía del token de galería).
 *
 * El token tras el hardening de auditoría sube de 9 a 16 bytes de entropía
 * (~71 -> ~128 bits). Validamos que el resultado siempre cumple el contrato:
 *  - 22 chars URL-safe base64 sin padding
 *  - 1000 generaciones consecutivas son únicas (no colisiones)
 *  - Sólo contiene [A-Za-z0-9_-]
 */

use PHPUnit\Framework\TestCase;

// El client portal vive fuera de plugin/. Carga directa del fichero.
require_once dirname( __DIR__, 3 ) . '/wp/plugins/yzmf-client-portal/includes/class-cpt.php';

class ClientPortalTokenTest extends TestCase {

    public function test_token_tiene_22_chars() {
        $tok = YZMF_CP_CPT::generate_token();
        $this->assertSame( 22, strlen( $tok ),
            'El token debe ser de 22 chars URL-safe (16 bytes base64 sin padding).'
        );
    }

    public function test_token_es_url_safe() {
        for ( $i = 0; $i < 50; $i++ ) {
            $tok = YZMF_CP_CPT::generate_token();
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9_-]{22}$/',
                $tok,
                "Token no URL-safe: $tok"
            );
        }
    }

    public function test_token_unico_en_1000_generaciones() {
        $seen = [];
        for ( $i = 0; $i < 1000; $i++ ) {
            $tok = YZMF_CP_CPT::generate_token();
            $this->assertArrayNotHasKey( $tok, $seen,
                "Colisión de token tras $i iteraciones: $tok"
            );
            $seen[ $tok ] = true;
        }
    }
}
