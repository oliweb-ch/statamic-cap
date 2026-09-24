<?php

namespace StatamicCap\Support;

use Illuminate\Support\Facades\File;

class AssetVersion
{
    /**
     * Returns the installed version of oliweb/laravel-cap as a cache-busting token.
     *
     * InstalledVersions::getVersion() throws OutOfBoundsException (not null) when
     * the package is unknown — confirmed on Composer 2.x. The broad Throwable catch
     * ensures a missing runtime autoload (e.g. COMPOSER_VENDOR_DIR override) never
     * breaks tag rendering.
     */
    public static function packageVersion(): string
    {
        try {
            return \Composer\InstalledVersions::getVersion('oliweb/laravel-cap') ?? 'dev';
        } catch (\Throwable) {
            return 'dev';
        }
    }

    /**
     * Returns the mtime of the local rsw WASM file as a cache-busting token,
     * or null when the file has not been published.
     */
    public static function wasm(): ?string
    {
        $path = storage_path('app/statamic-cap/cap_wasm_bg.wasm');

        return File::exists($path) ? (string) filemtime($path) : null;
    }

    /**
     * Returns the mtime of the local hashwx WASM file as a cache-busting token,
     * or null when the file has not been published.
     */
    public static function hashwxWasm(): ?string
    {
        $path = storage_path('app/statamic-cap/hashwx.wasm');

        return File::exists($path) ? (string) filemtime($path) : null;
    }
}
