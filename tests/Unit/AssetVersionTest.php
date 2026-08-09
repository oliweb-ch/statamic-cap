<?php

namespace StatamicCap\Tests\Unit;

use Illuminate\Support\Facades\File;
use StatamicCap\Support\AssetVersion;
use StatamicCap\Tags\Cap;
use StatamicCap\Tests\TestCase;

class AssetVersionTest extends TestCase
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

    // ---------------------------------------------------------------
    // AssetVersion::packageVersion()
    // ---------------------------------------------------------------

    public function test_package_version_returns_non_empty_string(): void
    {
        // On n'assume pas la valeur exacte (forme normalisée Composer : e.g. "1.10.0.0")
        // car elle dépend de ce qui est installé dans l'environnement CI.
        $version = AssetVersion::packageVersion();

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }

    // ---------------------------------------------------------------
    // AssetVersion::wasm()
    // ---------------------------------------------------------------

    public function test_wasm_version_is_null_when_file_absent(): void
    {
        $this->assertFileDoesNotExist($this->wasmPath);

        $this->assertNull(AssetVersion::wasm());
    }

    public function test_wasm_version_is_filemtime_when_file_exists(): void
    {
        @mkdir(dirname($this->wasmPath), 0755, true);
        file_put_contents($this->wasmPath, "\x00asm\x01\x00\x00\x00");
        $expectedMtime = (string) filemtime($this->wasmPath);

        $this->assertSame($expectedMtime, AssetVersion::wasm());
    }

    // ---------------------------------------------------------------
    // {{ cap:scripts }} et {{ cap:styles }} — paramètre v dans les URLs
    // ---------------------------------------------------------------

    public function test_scripts_tag_url_contains_version_parameter(): void
    {
        $output  = $this->makeTag()->scripts();
        $version = AssetVersion::packageVersion();

        $this->assertStringContainsString('v=' . $version, $output,
            '{{ cap:scripts }} ne contient pas le paramètre v= dans l\'URL du script.');
    }

    public function test_styles_tag_url_contains_version_parameter(): void
    {
        $output  = $this->makeTag()->styles();
        $version = AssetVersion::packageVersion();

        $this->assertStringContainsString('v=' . $version, $output,
            '{{ cap:styles }} ne contient pas le paramètre v= dans l\'URL du CSS.');
    }

    // ---------------------------------------------------------------
    // AssetController::js() et css() — ETag = packageVersion()
    // ---------------------------------------------------------------

    public function test_js_etag_equals_package_version(): void
    {
        // base_path() en testbench pointe vers le skeleton d'Orchestra.
        // On mocke File::get() pour éviter une exception sur le chemin réel.
        File::partialMock()
            ->shouldReceive('get')
            ->with(base_path('vendor/oliweb/laravel-cap/resources/js/cap-widget.js'))
            ->andReturn('/* fake cap-widget.js */');

        $response = $this->get('/vendor/statamic-cap/cap-widget.js');

        $response->assertStatus(200);
        $response->assertHeader('ETag', AssetVersion::packageVersion());
    }

    public function test_css_etag_equals_package_version(): void
    {
        File::partialMock()
            ->shouldReceive('get')
            ->with(base_path('vendor/oliweb/laravel-cap/resources/css/cap-widget.css'))
            ->andReturn('/* fake cap-widget.css */');

        $response = $this->get('/vendor/statamic-cap/cap-widget.css');

        $response->assertStatus(200);
        $response->assertHeader('ETag', AssetVersion::packageVersion());
    }

    // ---------------------------------------------------------------
    // AssetController::wasm() — ETag = filemtime du fichier local
    // ---------------------------------------------------------------

    public function test_wasm_etag_equals_filemtime_of_local_file(): void
    {
        @mkdir(dirname($this->wasmPath), 0755, true);
        file_put_contents($this->wasmPath, "\x00asm\x01\x00\x00\x00");
        $expectedEtag = (string) filemtime($this->wasmPath);

        $response = $this->get('/vendor/statamic-cap/cap_wasm_bg.wasm');

        $response->assertStatus(200);
        $response->assertHeader('ETag', $expectedEtag);
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    private function makeTag(?string $nonce = null): Cap
    {
        $tag = new Cap();
        $tag->params = new class ($nonce) {
            public function __construct(private ?string $nonce) {}
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'nonce' ? $this->nonce : $default;
            }
        };
        return $tag;
    }
}
