<?php

namespace StatamicCap\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use StatamicCap\Support\AssetVersion;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class AssetController extends Controller
{
    public function js(): Response
    {
        $path = base_path('vendor/oliweb/laravel-cap/resources/js/cap-widget.js');

        $content = File::get($path);
        $etag    = AssetVersion::packageVersion();

        if (request()->header('If-None-Match') === $etag) {
            return response('', 304);
        }

        return response($content, 200, [
            'Content-Type'  => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag'          => $etag,
        ]);
    }

    public function css(): Response
    {
        $path = base_path('vendor/oliweb/laravel-cap/resources/css/cap-widget.css');

        $content = File::get($path);
        $etag    = AssetVersion::packageVersion();

        if (request()->header('If-None-Match') === $etag) {
            return response('', 304);
        }

        return response($content, 200, [
            'Content-Type'  => 'text/css; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag'          => $etag,
        ]);
    }

    public function wasm(): BaseResponse
    {
        return $this->serveWasm(
            local:      storage_path('app/statamic-cap/cap_wasm_bg.wasm'),
            etag:       AssetVersion::wasm(),
            filename:   'cap_wasm_bg.wasm',
            logContext: 'cap_wasm_bg.wasm',
        );
    }

    public function hashwxWasm(): BaseResponse
    {
        return $this->serveWasm(
            local:      storage_path('app/statamic-cap/hashwx.wasm'),
            etag:       AssetVersion::hashwxWasm(),
            filename:   'hashwx.wasm',
            logContext: 'hashwx.wasm',
        );
    }

    private function serveWasm(string $local, ?string $etag, string $filename, string $logContext): BaseResponse
    {
        if (File::exists($local)) {
            $content = File::get($local);
            // $etag is non-null here since File::exists() just passed.
            // The ?? 'local' fallback covers the near-impossible race where the file
            // disappears between the two calls; it degrades gracefully (no 304, no ETag).
            $resolvedEtag = $etag ?? 'local';

            if (request()->header('If-None-Match') === $resolvedEtag) {
                return response('', 304);
            }

            return response($content, 200, [
                'Content-Type'  => 'application/wasm',
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'ETag'          => $resolvedEtag,
            ]);
        }

        $fallbackEnabled = config('statamic-cap.wasm_cdn_fallback', false);

        if (! $fallbackEnabled) {
            Log::warning(
                'statamic-cap: ' . $logContext . ' local absent et wasm_cdn_fallback désactivé. '
                . 'Publiez le WASM localement via `php artisan cap:publish-wasm`, '
                . 'ou activez explicitement wasm_cdn_fallback dans les réglages '
                . 'si vous acceptez la dépendance à cdn.jsdelivr.net.'
            );

            abort(503, 'Cap WASM asset unavailable.');
        }

        $cdnUrl = $this->cdnWasmUrl($filename);

        if ($cdnUrl !== null) {
            return redirect($cdnUrl);
        }

        abort(404);
    }

    /**
     * Constructs the jsDelivr CDN URL for a WASM file by reading the @cap.js/wasm
     * version constant embedded in cap-widget.js (`const e="x.y.z"`).
     *
     * The widget ≥ 0.1.58 uses template literals for WASM URLs, so the old approach
     * of matching a literal quoted URL no longer applies.
     */
    private function cdnWasmUrl(string $filename): ?string
    {
        $js = File::get(base_path('vendor/oliweb/laravel-cap/resources/js/cap-widget.js'));

        if (! preg_match('/const e="([\d.]+)"/', $js, $matches)) {
            return null;
        }

        return 'https://cdn.jsdelivr.net/npm/@cap.js/wasm@' . $matches[1] . '/browser/' . $filename;
    }
}
