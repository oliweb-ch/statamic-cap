<?php

namespace StatamicCap\Tests\Unit;

use Illuminate\Support\Facades\File;
use StatamicCap\Tests\TestCase;

class AssetControllerWasmTest extends TestCase
{
    private string $wasmPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wasmPath = storage_path('app/statamic-cap/cap_wasm_bg.wasm');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->wasmPath)) {
            @unlink($this->wasmPath);
        }
        parent::tearDown();
    }

    private function createLocalWasm(): void
    {
        @mkdir(dirname($this->wasmPath), 0755, true);
        // En-tête WASM minimale valide
        file_put_contents($this->wasmPath, "\x00asm\x01\x00\x00\x00");
    }

    // ---------------------------------------------------------------
    // WASM local présent : servi dans les deux cas (fallback on/off)
    // ---------------------------------------------------------------

    public function test_local_wasm_served_with_fallback_disabled(): void
    {
        $this->createLocalWasm();
        config(['statamic-cap.wasm_cdn_fallback' => false]);

        $response = $this->get('/vendor/statamic-cap/cap_wasm_bg.wasm');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/wasm');
    }

    public function test_local_wasm_served_with_fallback_enabled(): void
    {
        $this->createLocalWasm();
        config(['statamic-cap.wasm_cdn_fallback' => true]);

        $response = $this->get('/vendor/statamic-cap/cap_wasm_bg.wasm');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/wasm');
    }

    // ---------------------------------------------------------------
    // WASM local absent, fallback désactivé → 503
    // ---------------------------------------------------------------

    public function test_503_when_local_absent_and_fallback_disabled(): void
    {
        $this->assertFileDoesNotExist($this->wasmPath);
        config(['statamic-cap.wasm_cdn_fallback' => false]);

        $response = $this->get('/vendor/statamic-cap/cap_wasm_bg.wasm');

        $response->assertStatus(503);
    }

    // ---------------------------------------------------------------
    // WASM local absent, fallback activé → redirection CDN jsDelivr
    // ---------------------------------------------------------------

    public function test_redirect_to_cdn_when_local_absent_and_fallback_enabled(): void
    {
        $this->assertFileDoesNotExist($this->wasmPath);
        config(['statamic-cap.wasm_cdn_fallback' => true]);

        // base_path() en contexte testbench pointe vers le skeleton d'Orchestra,
        // pas vers la racine du projet → File::get() sur le widget JS lèverait une exception.
        // On mocke pour retourner un contenu JS contenant l'URL CDN attendue.
        File::partialMock()
            ->shouldReceive('get')
            ->with(base_path('vendor/oliweb/laravel-cap/resources/js/cap-widget.js'))
            ->andReturn('"https://cdn.jsdelivr.net/npm/@cap.js/wasm@0.0.7/browser/cap_wasm_bg.wasm"');

        $response = $this->get('/vendor/statamic-cap/cap_wasm_bg.wasm');

        $this->assertContains(
            $response->getStatusCode(),
            [301, 302, 303, 307, 308],
            'La réponse doit être une redirection 3xx vers le CDN.'
        );
        $this->assertStringContainsString(
            'cdn.jsdelivr.net',
            $response->headers->get('Location'),
            'La redirection doit pointer vers cdn.jsdelivr.net.'
        );
        $this->assertStringContainsString(
            '.wasm',
            $response->headers->get('Location'),
            'La redirection doit pointer vers un fichier .wasm.'
        );
    }

    // ---------------------------------------------------------------
    // WASM local absent, fallback activé, mais JS sans pattern CDN → 404
    // ---------------------------------------------------------------

    public function test_404_when_fallback_enabled_but_no_cdn_pattern_in_js(): void
    {
        $this->assertFileDoesNotExist($this->wasmPath);
        config(['statamic-cap.wasm_cdn_fallback' => true]);

        // Simuler un cap-widget.js qui ne contient pas d'URL WASM CDN
        File::partialMock()
            ->shouldReceive('get')
            ->with(base_path('vendor/oliweb/laravel-cap/resources/js/cap-widget.js'))
            ->andReturn('// cap widget js sans URL WASM CDN');

        $response = $this->get('/vendor/statamic-cap/cap_wasm_bg.wasm');

        $response->assertStatus(404);
    }
}
