# statamic-cap

Statamic addon to integrate [Cap](https://github.com/tiagozip/cap) — a self-hosted proof-of-work CAPTCHA — into Statamic forms.

Built on top of [oliweb/laravel-cap](https://github.com/oliweb-ch/laravel-cap).

---

## Requirements

- PHP 8.2+
- Statamic 5.x or 6.x
- A self-hosted Cap instance

---

## Installation

```bash
composer require oliweb/statamic-cap
```

Assets (JS + CSS) are served automatically by the addon via dedicated routes. No `vendor:publish` required.

---

## Configuration

### Via the Statamic CP

Go to **Tools > Cap CAPTCHA** in the Statamic control panel.

| Field | Description |
|-------|-------------|
| Cap Endpoint URL | Full URL of your Cap instance, including the site key (e.g. `https://cap.example.com/your-site-key/`) |
| Token Field Name | Name of the hidden field injected by the widget (default: `cap-token`) |
| Timeout (seconds) | Request timeout for `/siteverify` calls (default: `5`) |
| Fail Open | If enabled, allows requests through on Cap communication errors |
| Hide Attribution Link | If enabled, hides the "Cap" attribution link at the bottom right of the widget |
| Allow CDN fallback for WASM | If enabled, falls back to `cdn.jsdelivr.net` when the local WASM has not been published. **Disabled by default** — see note below. |

> **Breaking change (v1.8+) — WASM CDN fallback:** prior to this version, a missing local WASM file silently redirected to `cdn.jsdelivr.net`. This fallback is now **opt-in**: with `Allow CDN fallback for WASM` disabled (the new default), a missing local WASM returns a **503** instead. The recommended solution is to publish the WASM locally once via `php artisan cap:publish-wasm`. Enable the CDN fallback only if you explicitly accept the external dependency.

> **Important — Cap secret:** the secret is **not** configurable from this panel. It must be set exclusively via the `CAP_SECRET` environment variable in `.env` (or via the published `oliweb/laravel-cap` configuration). Exposing the secret in the CP panel is a security risk; it is read directly from `config('cap.secret')` at verification time.

Settings are saved to `storage/statamic/addons/statamic-cap.yaml`.

### Via environment variables

Environment variables serve as default values and are overridden by CP settings for editable fields.

```env
# Configurable from the CP panel as well
CAP_ENDPOINT=https://cap.example.com/your-site-key/
CAP_TOKEN_FIELD=cap-token
CAP_TIMEOUT=5
CAP_FAIL_OPEN=false
CAP_HIDE_ATTRIBUTION=false
CAP_WASM_CDN_FALLBACK=false

# Secret — via .env or config/cap.php only (never from the CP panel)
CAP_SECRET=your-secret-key
```

---

## Usage

### Antlers tags

| Tag | Description |
|-----|-------------|
| `{{ cap }}` | Renders the `<cap-widget>` with the configured endpoint |
| `{{ cap:scripts }}` | Injects `window.CAP_CUSTOM_WASM_URL` + `<script type="module">` for the widget |
| `{{ cap:styles }}` | Widget CSS `<link>` tag |
| `{{ cap:config }}` | `<script>` exposing `window.CAP_API_ENDPOINT` and `window.CAP_TOKEN_FIELD` |

#### Standard widget mode

Load assets in the layout and add the widget to a Statamic form:

```antlers
<head>
    {{ cap:styles }}
</head>
<body>

    {{ form:create handle="contact" }}
        {{ cap }}
        <button type="submit">Send</button>
    {{ /form:create }}

    {{ cap:scripts }}
</body>
```

The widget automatically injects a hidden `cap-token` field into the parent form upon verification.

`{{ cap:scripts }}` always injects `window.CAP_CUSTOM_WASM_URL` pointing to the local WASM route — no external request at runtime.

#### Programmatic mode

Use `{{ cap:config }}` to expose the endpoint in JavaScript, then instantiate `Cap` directly without rendering a visible widget:

```antlers
<head>
    {{ cap:styles }}
</head>
<body>

    {{ cap:config }}
    {{ cap:scripts }}

    <form method="POST" action="/contact">
        <input type="hidden" name="cap-token" id="cap-token">
        <button type="submit" id="submit-btn">Send</button>
    </form>

    <script type="module">
    document.getElementById('submit-btn').addEventListener('click', async (e) => {
        e.preventDefault();

        const cap = new Cap({ apiEndpoint: window.CAP_API_ENDPOINT });
        const { token } = await cap.solve();

        document.getElementById('cap-token').value = token;
        e.target.closest('form').submit();
    });
    </script>

</body>
```

`Cap` automatically creates a hidden `cap-widget` element in the background. No visible widget is rendered.

`window.CAP_API_ENDPOINT` and `window.CAP_TOKEN_FIELD` are set by `{{ cap:config }}` from the PHP configuration — no JavaScript hard-coding required.

#### With CSP nonce

```antlers
{{ cap:config nonce="{ $cspNonce }" }}
{{ cap:scripts nonce="{ $cspNonce }" }}
{{ cap nonce="{ $cspNonce }" }}
```

#### CSP headers

The widget relies on Web Workers and WebAssembly. A strict CSP must include:

```
Content-Security-Policy:
  script-src 'nonce-{nonce}' 'strict-dynamic';
  worker-src blob:;
  wasm-unsafe-eval;
  connect-src 'self';
```

`worker-src blob:` — required because the widget spawns workers via Blob URLs.  
`wasm-unsafe-eval` — required for WebAssembly hash computation.  
`connect-src 'self'` — sufficient when WASM is served locally (see below).

### Automatic validation

Token verification is automatic: the addon listens to Statamic's `FormSubmitted` event and rejects the submission if the token is invalid or missing. No additional configuration required.

On failure, a validation error is returned with the message `statamic-cap::messages.validation_failed`.

---

## Local WASM (strict CSP)

By default, `{{ cap:scripts }}` injects `window.CAP_CUSTOM_WASM_URL` pointing to the `/vendor/statamic-cap/cap_wasm_bg.wasm` route. This route serves the local file if present, otherwise falls back to the jsDelivr CDN.

For fully self-hosted operation with no external requests, download the WASM file locally:

```bash
php artisan cap:publish-wasm
```

The file is saved to `storage/app/statamic-cap/cap_wasm_bg.wasm` and served automatically. The CSP can then be limited to `connect-src 'self'` without whitelisting jsDelivr.

---

## Translations

Translations are available in English and French. Strings cover validation, error messages, widget labels, and the CP settings page.

To customise them, copy and edit the file in your project:

```
lang/vendor/statamic-cap/{locale}/messages.php
```

---

## License

MIT — see [LICENSE](LICENSE)
