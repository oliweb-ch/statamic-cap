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
    private const WASM_CDN_PATTERN = '/"(https:\/\/cdn\.jsdelivr\.net\/[^"]+\.wasm)"/';

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
        $local = storage_path('app/statamic-cap/cap_wasm_bg.wasm');

        if (File::exists($local)) {
            $content = File::get($local);
            // AssetVersion::wasm() is non-null here since File::exists() just passed.
            // The ?? 'local' fallback covers the near-impossible race where the file
            // disappears between the two calls; it degrades gracefully (no 304, no ETag).
            $etag    = AssetVersion::wasm() ?? 'local';

            if (request()->header('If-None-Match') === $etag) {
                return response('', 304);
            }

            return response($content, 200, [
                'Content-Type'  => 'application/wasm',
                'Cache-Control' => 'public, max-age=31536000, immutable',
                'ETag'          => $etag,
            ]);
        }

        $fallbackEnabled = config('statamic-cap.wasm_cdn_fallback', false);

        if (! $fallbackEnabled) {
            Log::warning(
                'statamic-cap: WASM local absent et wasm_cdn_fallback désactivé. '
                . 'Publiez le WASM localement via `php artisan cap:publish-wasm`, '
                . 'ou activez explicitement wasm_cdn_fallback dans les réglages '
                . 'si vous acceptez la dépendance à cdn.jsdelivr.net.'
            );

            abort(503, 'Cap WASM asset unavailable.');
        }

        $js = File::get(base_path('vendor/oliweb/laravel-cap/resources/js/cap-widget.js'));

        if (preg_match(self::WASM_CDN_PATTERN, $js, $matches)) {
            return redirect($matches[1]);
        }

        abort(404);
    }
}
