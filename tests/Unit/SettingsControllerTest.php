<?php

namespace StatamicCap\Tests\Unit;

use Illuminate\Http\Request;
use Statamic\Facades\YAML;
use StatamicCap\Http\Controllers\SettingsController;
use StatamicCap\Tests\TestCase;

class SettingsControllerTest extends TestCase
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

    public function test_update_succeeds_without_secret_field(): void
    {
        $request = Request::create('/statamic-cap/settings', 'POST', [
            'endpoint'         => 'https://cap.example.com/key/',
            'token_field'      => 'cap-token',
            'timeout'          => 5,
            'fail_open'        => false,
            'hide_attribution' => false,
        ]);
        $request->setLaravelSession(app('session.store'));

        $controller = new SettingsController();
        $response = $controller->update($request);

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_yaml_written_by_update_never_contains_secret(): void
    {
        // Envoyer un champ 'secret' dans la requête (manipulation ou ancien formulaire)
        $request = Request::create('/statamic-cap/settings', 'POST', [
            'endpoint'         => 'https://cap.example.com/key/',
            'secret'           => 'should-not-be-persisted',
            'token_field'      => 'cap-token',
            'timeout'          => 5,
            'fail_open'        => false,
            'hide_attribution' => false,
        ]);
        $request->setLaravelSession(app('session.store'));

        $controller = new SettingsController();
        $controller->update($request);

        $this->assertFileExists($this->yamlPath);

        $written = YAML::file($this->yamlPath)->parse();
        $this->assertArrayNotHasKey('secret', $written);
    }

    public function test_settings_page_does_not_expose_secret_in_view_data(): void
    {
        // Simuler un secret correctement configuré dans cap.secret (via .env)
        // — cas qui aurait échoué avant le correctif si ce secret se retrouvait
        // dans statamic-cap.secret puis rendu dans le HTML.
        config(['cap.secret' => 'cap-secret-must-not-appear-in-html']);

        $controller = new SettingsController();
        $view = $controller->edit();

        $settings = $view->getData()['settings'];

        // Après le correctif : config('statamic-cap', []) ne contient plus 'secret'
        $this->assertArrayNotHasKey('secret', $settings,
            'La vue reçoit une clé "secret" dans $settings — le secret pourrait apparaître dans le HTML.');

        // Vérification du template : le champ password a été supprimé
        $template = file_get_contents(__DIR__ . '/../../resources/views/settings.blade.php');
        $this->assertStringNotContainsString('type="password"', $template,
            'Le template contient encore un champ password qui pourrait exposer le secret.');
        $this->assertStringNotContainsString('name="secret"', $template,
            'Le template contient encore un champ "secret".');
    }
}
