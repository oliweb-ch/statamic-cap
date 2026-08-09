# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Security

- **Removed Cap secret from the CP settings panel.** The secret field (`<input type="password">`) has been removed from the settings view. The secret must now be configured exclusively via `CAP_SECRET` in `.env` or through the published `oliweb/laravel-cap` configuration — never from the Statamic control panel.
- **`ValidateCapToken` now builds its own `Cap` instance** with an explicit config instead of relying on the `LaravelCap\Facades\Cap` singleton. The `secret` key is sourced exclusively from `config('cap.secret')` (the `laravel-cap` namespace), ensuring `statamic-cap` never stores or reads the secret.

### Fixed

- `SettingsController::update()` no longer validates or persists a `secret` field. Even if a `secret` key is present in the submitted request (e.g. from a browser with a cached form), it is explicitly removed from `$data` before writing the YAML file.
- `ServiceProvider::loadSettingsFromYaml()` automatically purges a residual `secret` key found in `storage/statamic/addons/statamic-cap.yaml` (left over from installations prior to this fix). The file is rewritten without the key immediately after detection. If the file cannot be rewritten (read-only storage, permissions, etc.), a `Log::warning()` is emitted and the application continues booting normally — the secret is never merged into `config('statamic-cap', ...)` in either case.

### Added

- First test suite for the package (`tests/`). Base `TestCase` extending `Statamic\Testing\AddonTestCase`. Unit tests covering: CP settings controller (no secret in YAML output, no secret in view data), YAML secret auto-purge on boot, and `ValidateCapToken` listener (valid/invalid token, correct endpoint and secret sourcing, `fail_open` behaviour).
