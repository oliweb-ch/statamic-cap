<?php

namespace StatamicCap\Tags;

use Statamic\Tags\Tags;
use StatamicCap\Support\AssetVersion;

class Cap extends Tags
{
    protected static $handle = 'cap';

    /**
     * {{ cap }}
     * Renders the Cap widget HTML element.
     *
     * Note: the nonce for inline styles is set via window.CAP_CSS_NONCE
     * in {{ cap:scripts }}, not via a data attribute.
     */
    public function index(): string
    {
        $endpoint = config('statamic-cap.endpoint', config('cap.endpoint'));

        $i18n = [
            'initial-state'        => 'widget_initial_state',
            'required-label'       => 'widget_required_label',
            'verifying-label'      => 'widget_verifying_label',
            'verifying-aria-label' => 'widget_verifying_aria_label',
            'verified-aria-label'  => 'widget_verified_aria_label',
            'error-label'          => 'widget_error_label',
            'error-aria-label'     => 'widget_error_aria_label',
            'wasm-disabled'        => 'widget_wasm_disabled',
            'verify-aria-label'    => 'widget_verify_aria_label',
            'troubleshooting-label' => 'widget_troubleshooting_label',
            'solved-label'         => 'widget_solved_label',
        ];

        $attrs = 'data-cap-api-endpoint="' . e($endpoint) . '"';

        foreach ($i18n as $widgetKey => $msgKey) {
            $attrs .= ' data-cap-i18n-' . $widgetKey . '="' . e(__('statamic-cap::messages.' . $msgKey)) . '"';
        }

        return '<cap-widget ' . $attrs . '></cap-widget>';
    }

    /**
     * {{ cap:scripts }}
     * {{ cap:scripts nonce="x" }}
     *
     * Injects:
     *   - window.CAP_CSP_NONCE / window.CAP_CSS_NONCE  (when nonce provided)
     *   - window.CAP_CUSTOM_WASM_URL                    (always — route vers /vendor/statamic-cap/cap_wasm_bg.wasm)
     *   - <script type="module"> for cap-widget.js
     */
    public function scripts(): string
    {
        $nonce     = $this->params->get('nonce');
        $src       = e(route('statamic-cap.assets.js', ['v' => AssetVersion::packageVersion()]));
        $nonceAttr = $nonce ? ' nonce="' . e($nonce) . '"' : '';

        $globals = $this->buildGlobals($nonce);

        $output = '<script' . $nonceAttr . '>' . $globals . '</script>' . "\n";
        $output .= '<script type="module"' . $nonceAttr . ' src="' . $src . '"></script>';

        return $output;
    }

    /**
     * {{ cap:styles }}
     * Renders the Cap CSS link tag.
     */
    public function styles(): string
    {
        $css = '<link rel="stylesheet" href="' . e(route('statamic-cap.assets.css', ['v' => AssetVersion::packageVersion()])) . '">';

        if (config('statamic-cap.hide_attribution', false)) {
            $css .= "\n" . '<style>cap-widget::part(attribution){display:none}</style>';
        }

        return $css;
    }

    /**
     * {{ cap:config }}
     * {{ cap:config nonce="x" }}
     *
     * Expose l'endpoint et le nom du champ token en JavaScript pour le mode programmatic.
     * À placer avant {{ cap:scripts }}.
     *
     * Usage :
     *   const cap = new Cap({ apiEndpoint: window.CAP_API_ENDPOINT });
     *   const { token } = await cap.solve();
     */
    public function config(): string
    {
        $endpoint   = config('statamic-cap.endpoint', config('cap.endpoint'));
        $tokenField = config('statamic-cap.token_field', config('cap.token_field', 'cap-token'));
        $nonce      = $this->params->get('nonce');
        $nonceAttr  = $nonce ? ' nonce="' . e($nonce) . '"' : '';

        return '<script' . $nonceAttr . '>'
            . 'window.CAP_API_ENDPOINT=' . json_encode($endpoint, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';'
            . 'window.CAP_TOKEN_FIELD=' . json_encode($tokenField, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';'
            . '</script>';
    }

    /**
     * {{ cap:frame }}
     * {{ cap:frame nonce="x" }}
     * {{ cap:frame id="login-cap" }}
     * {{ cap:frame nonce="x" id="login-cap" }}
     *
     * Renders the Cap widget inside a hidden iframe with its own permissive CSP,
     * keeping the parent page CSP strict (no 'unsafe-eval' required).
     *
     * The iframe posts `cap:token` to the parent via postMessage; the parent
     * fills a hidden input and exposes `window.capSolve()` (or
     * `window['capSolve_<id>']()` when a custom id is used).
     *
     * @throws \InvalidArgumentException if $id does not satisfy ^[A-Za-z][A-Za-z0-9_-]*$ (max 64 chars).
     */
    public function frame(): string
    {
        $nonce = $this->params->get('nonce');
        $id    = $this->params->get('id', 'cap-frame');

        if (! preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id) || strlen($id) > 64) {
            throw new \InvalidArgumentException(
                '{{ cap:frame }}: invalid id "' . $id . '" — must match ^[A-Za-z][A-Za-z0-9_-]*$ (max 64 characters).'
            );
        }

        $tokenField = e(config('statamic-cap.token_field', config('cap.token_field', 'cap-token')));
        $frameSrc   = e(route('cap.frame'));
        $idE        = e($id);
        $idJson     = json_encode($id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $isDefault  = ($id === 'cap-frame');

        $input    = '<input type="hidden" name="' . $tokenField . '" id="' . $idE . '-token">';
        $iframeBase = '<iframe src="' . $frameSrc . '" id="' . $idE . '"'
            . ' title="Cap CAPTCHA" aria-hidden="true"></iframe>';

        $listener  = 'window.addEventListener(\'message\',function(e){'
            . 'if(e.origin!==window.location.origin)return;'
            . 'var _f=document.getElementById(' . $idJson . ');'
            . 'if(!_f||e.source!==_f.contentWindow)return;'
            . 'if(!e.data||e.data.type!==\'cap:token\')return;'
            . 'document.getElementById(' . $idJson . '+\'-token\').value=e.data.token;'
            . '});';

        $solver = $isDefault
            ? 'window.capSolve=function(){'
                . 'document.getElementById(' . $idJson . ').contentWindow'
                . '.postMessage({type:\'cap:start\'},window.location.origin);'
                . '};'
            : 'window[\'capSolve_\'+' . $idJson . ']=function(){'
                . 'document.getElementById(' . $idJson . ').contentWindow'
                . '.postMessage({type:\'cap:start\'},window.location.origin);'
                . '};';

        if ($nonce === null) {
            $iframe = str_replace('></iframe>', ' style="width:0;height:0;border:0;overflow:hidden;"></iframe>', $iframeBase);
            $script = '<script>(function(){' . $listener . $solver . '})();</script>';
        } else {
            $nonceE = e($nonce);
            $iframe = $iframeBase;
            $script = '<style nonce="' . $nonceE . '">#' . $idE . '{width:0;height:0;border:0;overflow:hidden;}</style>'
                . '<script nonce="' . $nonceE . '">(function(){' . $listener . $solver . '})();</script>';
        }

        return $input . $iframe . $script;
    }

    private function buildGlobals(?string $nonce): string
    {
        $assignments = [];

        if ($nonce) {
            $encoded = json_encode($nonce, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            $assignments[] = 'window.CAP_CSP_NONCE=' . $encoded;
            $assignments[] = 'window.CAP_CSS_NONCE=' . $encoded;
        }

        // Le widget lit nativement window.CAP_CUSTOM_WASM_URL et window.CAP_CUSTOM_HASHWX_URL.
        // On pointe toujours vers nos routes — elles servent le WASM local si présent,
        // sinon redirigent vers le CDN jsdelivr (si wasm_cdn_fallback activé).
        $wasmVersion = AssetVersion::wasm();
        $wasmUrl     = $wasmVersion
            ? route('statamic-cap.assets.wasm', ['v' => $wasmVersion])
            : route('statamic-cap.assets.wasm');

        $assignments[] = 'window.CAP_CUSTOM_WASM_URL=' . json_encode($wasmUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $hashwxVersion = AssetVersion::hashwxWasm();
        $hashwxUrl     = $hashwxVersion
            ? route('statamic-cap.assets.hashwx_wasm', ['v' => $hashwxVersion])
            : route('statamic-cap.assets.hashwx_wasm');

        $assignments[] = 'window.CAP_CUSTOM_HASHWX_URL=' . json_encode($hashwxUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return implode(';', $assignments);
    }
}
