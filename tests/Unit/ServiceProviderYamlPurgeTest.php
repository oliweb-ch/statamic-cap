<?php

namespace StatamicCap\Tests\Unit;

use ReflectionMethod;
use Statamic\Facades\YAML;
use StatamicCap\Tests\TestCase;

/**
 * Vérifie la purge automatique du secret résiduel dans le YAML au boot du ServiceProvider.
 *
 * getEnvironmentSetUp() crée un YAML *avec* secret avant le boot, de sorte que
 * les deux premiers tests valident le cas "résidu d'une ancienne installation".
 *
 * Le troisième test réécrit le YAML sans secret puis réinvoque loadSettingsFromYaml
 * via reflection pour confirmer qu'aucune réécriture inutile n'a lieu.
 */
class ServiceProviderYamlPurgeTest extends TestCase
{
    private string $yamlPath;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Créer le YAML AVEC secret AVANT que les service providers ne bootent.
        $this->yamlPath = $app->storagePath('statamic/addons/statamic-cap.yaml');
        @mkdir(dirname($this->yamlPath), 0755, true);
        file_put_contents($this->yamlPath, YAML::dump([
            'endpoint' => 'https://cap.example.com/key/',
            'secret'   => 'residual-secret-from-old-install',
        ]));
    }

    protected function tearDown(): void
    {
        if (isset($this->yamlPath) && file_exists($this->yamlPath)) {
            @unlink($this->yamlPath);
        }
        parent::tearDown();
    }

    public function test_secret_is_absent_from_config_after_boot(): void
    {
        // Le ServiceProvider a booté dans setUp(), le secret ne doit pas être dans la config.
        $this->assertNull(config('statamic-cap.secret'),
            'config("statamic-cap.secret") ne doit pas exister après boot.');

        $this->assertArrayNotHasKey('secret', config('statamic-cap'),
            'Le tableau config statamic-cap ne doit pas contenir de clé "secret".');
    }

    public function test_secret_is_purged_from_yaml_file_after_boot(): void
    {
        $this->assertFileExists($this->yamlPath);

        $parsed = YAML::file($this->yamlPath)->parse();
        $this->assertArrayNotHasKey('secret', $parsed,
            'Le fichier YAML doit avoir été réécrit sans la clé "secret".');

        // Les autres données doivent être préservées.
        $this->assertSame('https://cap.example.com/key/', $parsed['endpoint']);
    }

    public function test_yaml_without_secret_is_not_rewritten(): void
    {
        // Écrire un YAML propre (sans secret) dans le fichier et noter le contenu exact.
        $cleanContent = YAML::dump(['endpoint' => 'https://cap.example.com/key/']);
        file_put_contents($this->yamlPath, $cleanContent);

        // Réinvoquer loadSettingsFromYaml via reflection — comme si le ServiceProvider
        // se chargeait avec un YAML sans secret.
        $provider = new \StatamicCap\ServiceProvider($this->app);
        $method = new ReflectionMethod($provider, 'loadSettingsFromYaml');
        $method->setAccessible(true);
        $method->invoke($provider);

        // Le contenu du fichier doit être identique (pas de File::put inutile).
        $this->assertSame(
            $cleanContent,
            file_get_contents($this->yamlPath),
            'Le fichier YAML a été réécrit alors qu\'il ne contenait pas de clé "secret".'
        );
    }
}
