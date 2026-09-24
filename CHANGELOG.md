# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.14.0] - 2026-09-24

### Added
- **`hashwx` protocol support** (Cap standalone ≥ 3.1.12). New keys created on a
  3.1.12+ instance default to `hashwx` (GPU-resistant PoW). Existing `rsw` keys
  continue to work unchanged.
- **Route `GET /vendor/statamic-cap/hashwx.wasm`** (`statamic-cap.assets.hashwx_wasm`)
  serving the new WASM file via `AssetController::hashwxWasm()`. Same logic as
  `wasm()`: local file takes priority, jsDelivr CDN as an opt-in fallback.
- **`window.CAP_CUSTOM_HASHWX_URL`** injected by `{{ cap:scripts }}` (with and
  without nonce), pointing to `/vendor/statamic-cap/hashwx.wasm`.
- **`AssetVersion::hashwxWasm()`** returns the mtime of
  `storage/app/statamic-cap/hashwx.wasm` as a cache-busting token (same convention
  as `wasm()`).

### Changed
- **`cap:publish-wasm`** now downloads both `cap_wasm_bg.wasm` and `hashwx.wasm`
  from jsDelivr. CDN URL resolution now reads the `@cap.js/wasm` version constant
  embedded in the widget (`const e="x.y.z"`) instead of matching a literal quoted
  URL — widget ≥ 0.1.58 uses template literals for WASM URLs.
- **`AssetController::wasm()`** refactored: shared logic extracted into `serveWasm()`
  (used by both `wasm()` and `hashwxWasm()`). CDN fallback URL resolution moved to
  `cdnWasmUrl(string $filename)`, which constructs the URL from the `@cap.js/wasm`
  version read from the widget.

## [1.13.1] - 2026-08-10

### Fixed

- **Corrected `CHANGELOG.md`.** Every entry from v1.7.0 through v1.13.0 had been collapsed into a single dated `[1.13.0]` block instead of six separate versioned entries. This happened because `[Unreleased]` was never converted to a dated version header at any of the intermediate tags — each one only appended new bullets under `[Unreleased]`. Each tag's actual content is now documented separately below, with its real tag date. Per this project's policy of never amending a published tag, v1.13.1 exists specifically to ship this correction. No file under `src/`, `config/`, `resources/`, or `tests/` was modified in this release.

## [1.13.0] - 2026-08-10

### Fixed

- **README documentation corrections:** the "Local WASM (strict CSP)" section incorrectly described CDN fallback as the default behaviour (changed to opt-in 503 in v1.8.0); the "Translations" section omitted German, which has been available since its addition.

## [1.12.0] - 2026-08-09

### Added

- **CP toggle for `cap_disabled` in Statamic's form editor.** A dedicated **Cap** tab is now injected into the form editing screen via `Form::appendConfigFields()`, exposing a **Disable Cap** toggle. Persisted automatically to the form's YAML file by Statamic's native form persistence — no custom route or controller required. This is a UI addition for a mechanism already introduced in v1.11.0: forms that were already using `cap_disabled: true` in their YAML are unaffected. Manual YAML editing remains supported (useful for Git-managed form configurations).

## [1.11.0] - 2026-08-09

### Added

- **Per-form opt-out via `cap_disabled: true` in the form YAML.** Adding `cap_disabled: true` as a top-level key in `resources/forms/{handle}.yaml` instructs `ValidateCapToken` to exit immediately without making any network request to Cap's `/siteverify` endpoint. Useful for internal or admin forms that never render the Cap widget. **Not a breaking change:** forms that do not declare this key continue to be verified exactly as before — the absence of `cap_disabled` is treated as `false` (protected). There is currently no corresponding checkbox in Statamic's CP form editor; the key must be set manually in YAML.

## [1.10.0] - 2026-08-09

### Changed

- **Asset URLs now include a cache-busting `v` parameter.** `{{ cap:scripts }}` and `{{ cap:styles }}` append `?v=<version>` derived from the installed version of `oliweb/laravel-cap` (via `Composer\InstalledVersions`). The WASM URL additionally carries `?v=<filemtime>` when the local file has been published, or no parameter when it is absent. ETag values for JS and CSS responses are now the package version string instead of a per-request `md5()` of the full file content, eliminating a redundant hash computation on every uncached request. The WASM ETag is now the file's `filemtime`. Together these changes ensure that a `composer update oliweb/laravel-cap` automatically invalidates browser caches without any action required from the user. **No breaking change** — existing installations that have not published the WASM locally are unaffected; those that have will benefit from correct mtime-based cache invalidation.

## [1.9.0] - 2026-08-09

### Security

- **`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` applied to all `json_encode()` calls in `Cap.php`.** Values interpolated into inline `<script>` blocks (endpoint, token field name, nonce, WASM URL) are now unicode-escaped, preventing XSS injection if any of those values were to contain a raw `<` or `&` sequence.
- **Cap endpoint restricted to HTTPS in `SettingsController`.** The `endpoint` validation rule is now `url:https` (native Laravel parameter syntax) instead of the permissive `url`. **Potentially breaking:** installations currently configured with an HTTP endpoint — including local development environments — will receive a validation error when saving settings until the endpoint is updated to HTTPS. This restriction is intentional: the CP settings panel is a production surface and, unlike `laravel-cap` which tolerates HTTP for development, `statamic-cap` enforces HTTPS unconditionally.

## [1.8.0] - 2026-08-09

### Changed

- **WASM CDN fallback is now opt-in (potentially breaking).** Previously, if the local WASM file had not been published via `php artisan cap:publish-wasm`, the addon silently redirected to `cdn.jsdelivr.net`. This behaviour has changed: the default is now a **503 response** with an explicit log warning, rather than an invisible dependency on an external CDN. Existing installations that relied on the silent CDN fallback without having published the WASM locally will receive 503 errors on the WASM route until they either run `php artisan cap:publish-wasm` or explicitly enable `wasm_cdn_fallback` (via `CAP_WASM_CDN_FALLBACK=true` or the CP settings panel).

### Added

- New `wasm_cdn_fallback` setting (config key, env variable `CAP_WASM_CDN_FALLBACK`, CP checkbox). When enabled, the addon falls back to `cdn.jsdelivr.net` for the WASM asset if the local file is absent. Disabled by default.
- Unit tests for `AssetController::wasm()` covering: local WASM served regardless of fallback setting, 503 on missing local WASM with fallback disabled, CDN redirect with fallback enabled, 404 when fallback is enabled but no CDN URL found in the widget JS.

## [1.7.0] - 2026-08-09

### Security

- **Removed Cap secret from the CP settings panel.** The secret field (`<input type="password">`) has been removed from the settings view. The secret must now be configured exclusively via `CAP_SECRET` in `.env` or through the published `oliweb/laravel-cap` configuration — never from the Statamic control panel.
- **`ValidateCapToken` now builds its own `Cap` instance** with an explicit config instead of relying on the `LaravelCap\Facades\Cap` singleton. The `secret` key is sourced exclusively from `config('cap.secret')` (the `laravel-cap` namespace), ensuring `statamic-cap` never stores or reads the secret.

### Added

- First test suite for the package (`tests/`). Base `TestCase` extending `Statamic\Testing\AddonTestCase`. Unit tests covering: CP settings controller (no secret in YAML output, no secret in view data), YAML secret auto-purge on boot, and `ValidateCapToken` listener (valid/invalid token, correct endpoint and secret sourcing, `fail_open` behaviour).

### Fixed

- `SettingsController::update()` no longer validates or persists a `secret` field. Even if a `secret` key is present in the submitted request (e.g. from a browser with a cached form), it is explicitly removed from `$data` before writing the YAML file.
- `ServiceProvider::loadSettingsFromYaml()` automatically purges a residual `secret` key found in `storage/statamic/addons/statamic-cap.yaml` (left over from installations prior to this fix). The file is rewritten without the key immediately after detection. If the file cannot be rewritten (read-only storage, permissions, etc.), a `Log::warning()` is emitted and the application continues booting normally — the secret is never merged into `config('statamic-cap', ...)` in either case.
