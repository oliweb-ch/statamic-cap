<?php

namespace StatamicCap\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use StatamicCap\Http\Controllers\SettingsController;
use StatamicCap\Tests\TestCase;

class SettingsControllerEndpointValidationTest extends TestCase
{
    private string $yamlPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->yamlPath = storage_path('statamic/addons/statamic-cap.yaml');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->yamlPath)) {
            @unlink($this->yamlPath);
        }
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Endpoint HTTP — doit être rejeté
    // ---------------------------------------------------------------

    public function test_http_endpoint_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new SettingsController())->update($this->makeRequest('http://cap.example.com/key/'));
    }

    public function test_http_endpoint_rejection_carries_https_message(): void
    {
        try {
            (new SettingsController())->update($this->makeRequest('http://cap.example.com/key/'));
            $this->fail('Une ValidationException était attendue pour un endpoint HTTP.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('endpoint', $e->errors(),
                'La ValidationException ne contient pas d\'erreur pour le champ endpoint.');

            $this->assertStringContainsString('HTTPS', $e->errors()['endpoint'][0],
                'Le message d\'erreur ne mentionne pas HTTPS — il ne sera pas clair pour l\'utilisateur.');
        }
    }

    // ---------------------------------------------------------------
    // Endpoint sans scheme — doit être rejeté
    // ---------------------------------------------------------------

    public function test_endpoint_without_scheme_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new SettingsController())->update($this->makeRequest('cap.example.com/key/'));
    }

    // ---------------------------------------------------------------
    // Endpoint HTTPS — doit passer
    // ---------------------------------------------------------------

    public function test_https_endpoint_passes_validation(): void
    {
        $response = (new SettingsController())->update($this->makeRequest('https://cap.example.com/key/'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    private function makeRequest(string $endpoint): Request
    {
        $request = Request::create('/cp/statamic-cap/settings', 'POST', [
            'endpoint'    => $endpoint,
            'token_field' => 'cap-token',
            'timeout'     => 5,
            'fail_open'   => false,
        ]);
        $request->setLaravelSession(app('session.store'));

        return $request;
    }
}
