# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **WASM CDN fallback is now opt-in (potentially breaking).** Previously, if the local WASM file had not been published via `php artisan cap:publish-wasm`, the addon silently redirected to `cdn.jsdelivr.net`. This behaviour has changed: the default is now a **503 response** with an explicit log warning, rather than an invisible dependency on an external CDN. Existing installations that relied on the silent CDN fallback without having published the WASM locally will receive 503 errors on the WASM route until they either run `php artisan cap:publish-wasm` or explicitly enable `wasm_cdn_fallback` (via `CAP_WASM_CDN_FALLBACK=true` or the CP settings panel).

### Added

- New `wasm_cdn_fallback` setting (config key, env variable `CAP_WASM_CDN_FALLBACK`, CP checkbox). When enabled, the addon falls back to `cdn.jsdelivr.net` for the WASM asset if the local file is absent. Disabled by default.
- Unit tests for `AssetController::wasm()` covering: local WASM served regardless of fallback setting, 503 on missing local WASM with fallback disabled, CDN redirect with fallback enabled, 404 when fallback is enabled but no CDN URL found in the widget JS.

### Security

- **Removed Cap secret from the CP settings panel.** The secret field (`<input type="password">`) has been removed from the settings view. The secret must now be configured exclusively via `CAP_SECRET` in `.env` or through the published `oliweb/laravel-cap` configuration — never from the Statamic control panel.
- **`ValidateCapToken` now builds its own `Cap` instance** with an explicit config instead of relying on the `LaravelCap\Facades\Cap` singleton. The `secret` key is sourced exclusively from `config('cap.secret')` (the `laravel-cap` namespace), ensuring `statamic-cap` never stores or reads the secret.

### Fixed

- `SettingsController::update()` no longer validates or persists a `secret` field. Even if a `secret` key is present in the submitted request (e.g. from a browser with a cached form), it is explicitly removed from `$data` before writing the YAML file.
- `ServiceProvider::loadSettingsFromYaml()` automatically purges a residual `secret` key found in `storage/statamic/addons/statamic-cap.yaml` (left over from installations prior to this fix). The file is rewritten without the key immediately after detection. If the file cannot be rewritten (read-only storage, permissions, etc.), a `Log::warning()` is emitted and the application continues booting normally — the secret is never merged into `config('statamic-cap', ...)` in either case.

### Added

- First test suite for the package (`tests/`). Base `TestCase` extending `Statamic\Testing\AddonTestCase`. Unit tests covering: CP settings controller (no secret in YAML output, no secret in view data), YAML secret auto-purge on boot, and `ValidateCapToken` listener (valid/invalid token, correct endpoint and secret sourcing, `fail_open` behaviour).
