<?php

namespace StatamicCap\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;

class SettingsController
{
    public function edit(): \Illuminate\View\View
    {
        return view('statamic-cap::settings', [
            'settings' => config('statamic-cap', []),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'endpoint'          => ['required', 'url:https'],
            'token_field'       => ['required', 'string', 'alpha_dash'],
            'timeout'           => ['required', 'integer', 'min:1', 'max:60'],
            'fail_open'         => ['nullable', 'boolean'],
            'hide_attribution'  => ['nullable', 'boolean'],
            'wasm_cdn_fallback' => ['nullable', 'boolean'],
        ], [
            'endpoint.url' => __('statamic-cap::messages.validation_endpoint_https'),
        ]);

        unset($data['secret']);

        $data['fail_open']         = $request->boolean('fail_open');
        $data['hide_attribution']  = $request->boolean('hide_attribution');
        $data['wasm_cdn_fallback'] = $request->boolean('wasm_cdn_fallback');

        $path = storage_path('statamic/addons/statamic-cap.yaml');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, YAML::dump($data));

        config(['statamic-cap' => array_merge(config('statamic-cap', []), $data)]);

        return back()->with('success', __('statamic-cap::messages.settings_saved'));
    }
}
