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

> **Config key resolution:** `endpoint`, `token_field`, `timeout`, and `fail_open` are read from `config('statamic-cap.*')`, with `config('cap.*')` as a fallback for values not overridden in the CP. `secret` is sourced exclusively from `config('cap.secret')` and is never read from the `statamic-cap` namespace. If a verification call uses the wrong endpoint after a CP change, check that the YAML file has been written to `storage/statamic/addons/statamic-cap.yaml` and that the config cache has been cleared.

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
| `{{ cap:scripts }}` | Injects `window.CAP_CUSTOM_WASM_URL`, `window.CAP_CUSTOM_HASHWX_URL` + `<script type="module">` for the widget |
| `{{ cap:styles }}` | Widget CSS `<link>` tag |
| `{{ cap:config }}` | `<script>` exposing `window.CAP_API_ENDPOINT` and `window.CAP_TOKEN_FIELD` |
| `{{ cap:frame }}` | Renders the Cap widget in a hidden iframe with a permissive CSP — keeps the parent page strict (no `'unsafe-eval'`) |

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

#### Iframe mode (strict CSP — no `'unsafe-eval'`)

When Cap's instrumentation is enabled, the widget requires `'unsafe-eval'` in `script-src`. If your page enforces a strict CSP, use `{{ cap:frame }}` instead: it renders the widget inside a hidden iframe (`/cap-frame`) with its own permissive CSP, keeping the parent page clean.

```antlers
{{-- No {{ cap:scripts }} or {{ cap:styles }} needed --}}

{{ form:create handle="contact" }}
    {{ cap:frame }}
    <button type="submit">Send</button>
{{ /form:create }}
```

This renders a hidden `<input type="hidden" name="cap-token">`, an invisible `<iframe src="/cap-frame">`, and a `<script>` that bridges the two via `postMessage`.

**Token flow:**

```
Parent page (strict CSP)              iframe /cap-frame (permissive CSP)
      │── postMessage(cap:start) ──►  │  widget.solve()
      │◄── postMessage(cap:token) ──  │  token
      │  fills #cap-frame-token       │
```

**Programmatic trigger** — `{{ cap:frame }}` exposes `window.capSolve()` on the parent page:

```javascript
// Trigger Cap resolution from Alpine, Vue, etc.
window.capSolve();

// Listen for the token (in addition to the hidden input auto-fill)
window.addEventListener('message', (e) => {
    if (e.origin !== window.location.origin) return;
    if (!e.data || e.data.type !== 'cap:token') return;
    myForm.submit(e.data.token);
});
```

**With nonce:**

```antlers
{{ cap:frame nonce="{ $cspNonce }" }}
```

**Multiple instances** — pass a unique `id` when several forms share the same page:

The custom id must satisfy: pattern `^[A-Za-z][A-Za-z0-9_-]*$`, max 64 characters. An invalid value throws an `InvalidArgumentException` at render time.

```antlers
{{-- Login form --}}
{{ cap:frame id="login-cap" }}
{{-- or with nonce: {{ cap:frame nonce="{ $cspNonce }" id="login-cap" }} --}}

{{-- Contact form --}}
{{ cap:frame id="contact-cap" }}
```

Each instance gets its own namespaced trigger function:

| Tag | iframe / input ids | trigger |
|-----|--------------------|---------|
| `{{ cap:frame }}` | `cap-frame` / `cap-frame-token` | `window.capSolve()` |
| `{{ cap:frame id="login-cap" }}` | `login-cap` / `login-cap-token` | `window['capSolve_login-cap']()` |

**`/cap-frame` route** is registered automatically by `oliweb/laravel-cap`. Its `Content-Security-Policy` includes `'unsafe-eval'`, `'wasm-unsafe-eval'`, `blob:`, and `img-src data:` — everything Cap needs — while `frame-ancestors 'self'` prevents embedding from external origins.

> **Instrumentation and strict CSP**
>
> Cap's optional **instrumentation** feature (enabled per site key in the Cap admin dashboard) calls `eval()` and `new Function()`, which are blocked by a `script-src` without `'unsafe-eval'`.
>
> If instrumentation is enabled and `'unsafe-eval'` is absent from your CSP, the widget reports `[instr_timeout]` and Cap returns HTTP 429, making every verification attempt fail.
>
> **Recommended workaround:** use `{{ cap:frame }}` as described above.
>
> **Alternatives:**
> - Disable instrumentation for the site key in the Cap admin dashboard.
> - Add `'unsafe-eval'` to your `script-src` (weakens the parent page CSP).
>
> See also: [tiagozip/cap#268](https://github.com/tiagozip/cap/issues/268)

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

### Disabling Cap on a specific form

By default, every form submission is verified. To exclude a specific form (e.g. an internal admin form that never renders the Cap widget), use the **Cap** tab in Statamic's form editor in the control panel and toggle **Disable Cap** on.

Alternatively, add `cap_disabled: true` directly as a top-level key in the form's YAML file (useful for Git-managed form configurations):

```yaml
# resources/forms/my_internal_form.yaml
title: My Internal Form
cap_disabled: true
fields:
  -
    handle: name
    field:
      type: text
```

When `cap_disabled: true` is present, the listener exits immediately without making any network request to Cap's `/siteverify` endpoint.

> **Non-breaking:** forms that do not have `cap_disabled` in their YAML are protected exactly as before. The absence of the key is treated as `cap_disabled: false`.

---

## Local WASM (strict CSP)

By default, `{{ cap:scripts }}` injects `window.CAP_CUSTOM_WASM_URL` pointing to the `/vendor/statamic-cap/cap_wasm_bg.wasm` route. As of v1.8.0, this route returns a **503** if the local WASM has not been published — CDN fallback is opt-in and disabled by default (see the Breaking change note above).

For fully self-hosted operation with no external requests, download both WASM files locally:

```bash
php artisan cap:publish-wasm
```

The command downloads and saves:

| File | Path | Purpose |
|------|------|---------|
| `cap_wasm_bg.wasm` | `storage/app/statamic-cap/cap_wasm_bg.wasm` | PoW computation (rsw keys) |
| `hashwx.wasm` | `storage/app/statamic-cap/hashwx.wasm` | PoW computation (hashwx keys — new default since Cap 3.1.12) |

`{{ cap:scripts }}` automatically injects both `window.CAP_CUSTOM_WASM_URL` and `window.CAP_CUSTOM_HASHWX_URL` pointing to the corresponding local routes. The CSP can then be limited to `connect-src 'self'` without whitelisting jsDelivr.

### Upgrading

After `composer update oliweb/laravel-cap`, re-run the command to refresh both WASM files to the versions bundled with the updated package:

```bash
php artisan cap:publish-wasm
```

---

## Translations

Translations are available in English, French, and German. Strings cover validation, error messages, widget labels, and the CP settings page.

To customise them, copy and edit the file in your project:

```
lang/vendor/statamic-cap/{locale}/messages.php
```

---

## License

MIT — see [LICENSE](LICENSE)
