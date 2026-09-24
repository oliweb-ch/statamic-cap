<?php

namespace StatamicCap\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\File;

class PublishWasm extends Command
{
    protected $signature = 'cap:publish-wasm';

    protected $description = 'Télécharge les fichiers WASM Cap en local pour un chargement auto-hébergé (CSP strict)';

    public function handle(Http $http): int
    {
        $jsPath = base_path('vendor/oliweb/laravel-cap/resources/js/cap-widget.js');

        if (! File::exists($jsPath)) {
            $this->error('cap-widget.js introuvable dans vendor/oliweb/laravel-cap. Vérifiez votre installation Composer.');

            return Command::FAILURE;
        }

        $js = File::get($jsPath);

        // Le widget ≥ 0.1.58 embarque la version @cap.js/wasm sous la forme `const e="x.y.z"`.
        // Les URLs WASM sont des template literals et ne peuvent donc pas être extraites
        // par un simple preg_match sur une URL littérale.
        if (! preg_match('/const e="([\d.]+)"/', $js, $matches)) {
            $this->error('Version @cap.js/wasm introuvable dans cap-widget.js.');

            return Command::FAILURE;
        }

        $wasmVersion = $matches[1];
        $wasmDir     = storage_path('app/statamic-cap');
        $files       = ['cap_wasm_bg.wasm', 'hashwx.wasm'];

        File::ensureDirectoryExists($wasmDir);

        foreach ($files as $filename) {
            $cdnUrl    = "https://cdn.jsdelivr.net/npm/@cap.js/wasm@{$wasmVersion}/browser/{$filename}";
            $localPath = $wasmDir . '/' . $filename;

            $this->info("Téléchargement de {$filename} depuis {$cdnUrl}…");

            $response = $http->get($cdnUrl);

            if ($response->failed()) {
                $this->error("Échec du téléchargement de {$filename} ({$response->status()}).");

                return Command::FAILURE;
            }

            File::put($localPath, $response->body());
            $this->info("{$filename} enregistré dans {$localPath}");
        }

        $this->newLine();
        $this->line('Le tag <b>{{ cap:scripts }}</b> injecte automatiquement <b>window.CAP_CUSTOM_WASM_URL</b>');
        $this->line('et <b>window.CAP_CUSTOM_HASHWX_URL</b> pointant vers les routes locales.');
        $this->line('Ajoutez <b>connect-src \'self\'</b> à votre CSP — plus besoin de whitelister jsDelivr.');

        return Command::SUCCESS;
    }
}
