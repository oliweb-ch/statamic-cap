<?php

namespace StatamicCap;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\YAML;
use Statamic\Providers\AddonServiceProvider;
use StatamicCap\Console\Commands\PublishWasm;
use StatamicCap\Http\Controllers\AssetController;
use StatamicCap\Http\Controllers\SettingsController;
use StatamicCap\Listeners\ValidateCapToken;
use StatamicCap\Tags\Cap;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Cap::class,
    ];

    protected $commands = [
        PublishWasm::class,
    ];

    protected $listen = [
        \Statamic\Events\FormSubmitted::class => [
            ValidateCapToken::class,
        ],
    ];

    public function bootAddon(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/statamic-cap.php', 'statamic-cap');
        $this->loadSettingsFromYaml();
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'statamic-cap');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'statamic-cap');

        $this->bootWebRoutes();
        $this->bootCpRoutes();
        $this->bootCpNav();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/statamic-cap.php' => config_path('statamic-cap.php'),
            ], 'statamic-cap-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => lang_path('vendor/statamic-cap'),
            ], 'statamic-cap-lang');
        }
    }

    private function loadSettingsFromYaml(): void
    {
        $path = storage_path('statamic/addons/statamic-cap.yaml');

        if (! file_exists($path)) {
            return;
        }

        $settings = YAML::file($path)->parse();

        if (isset($settings['secret'])) {
            unset($settings['secret']);
            try {
                File::put($path, YAML::dump($settings));
            } catch (\Throwable $e) {
                Log::warning(
                    'statamic-cap: impossible de purger le secret résiduel du fichier YAML. ' .
                    'Supprimez manuellement la clé "secret" dans : ' . $path,
                    ['exception' => $e->getMessage()]
                );
            }
        }

        config(['statamic-cap' => array_merge(config('statamic-cap', []), $settings)]);
    }

    private function bootWebRoutes(): void
    {
        $this->registerWebRoutes(function () {
            Route::get('vendor/statamic-cap/cap-widget.js', [AssetController::class, 'js'])
                ->name('statamic-cap.assets.js');
            Route::get('vendor/statamic-cap/cap-widget.css', [AssetController::class, 'css'])
                ->name('statamic-cap.assets.css');
            Route::get('vendor/statamic-cap/cap_wasm_bg.wasm', [AssetController::class, 'wasm'])
                ->name('statamic-cap.assets.wasm');
        });
    }

    private function bootCpRoutes(): void
    {
        $this->registerCpRoutes(function () {
            Route::get('statamic-cap/settings', [SettingsController::class, 'edit'])
                ->name('statamic-cap.settings');
            Route::post('statamic-cap/settings', [SettingsController::class, 'update'])
                ->name('statamic-cap.settings.update');
        });
    }

    private function bootCpNav(): void
    {
        Nav::extend(function ($nav) {
            $nav->create('Cap CAPTCHA')
                ->section('Tools')
                ->url(cp_route('statamic-cap.settings'))
                ->icon('settings-sliders');
        });
    }
}
