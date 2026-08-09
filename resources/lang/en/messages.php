<?php

return [
    'validation_failed'          => 'The Cap verification failed. Please complete the challenge and try again.',
    'validation_endpoint_https'  => 'The Cap endpoint URL must use HTTPS.',
    'form_failed'          => 'Cap verification failed. Please try again.',
    'settings_saved'       => 'Cap settings saved.',

    'field_endpoint'       => 'Cap Endpoint URL',
    'field_endpoint_hint'  => 'Full URL of your self-hosted Cap instance, including the site key. Example: https://cap.example.com/your-site-key/',
    'field_secret'         => 'Secret Key',
    'field_secret_hint'    => 'The secret key from your Cap dashboard. Never expose this publicly.',
    'field_token_field'    => 'Token Field Name',
    'field_token_field_hint' => 'Name of the hidden field injected by the Cap widget into the parent form.',
    'field_timeout'        => 'Timeout (seconds)',
    'field_timeout_hint'   => 'Time in seconds before abandoning the request to /siteverify.',
    'field_fail_open'      => 'Fail Open',
    'field_fail_open_hint' => 'When enabled, any communication error with Cap lets the request through. An explicitly invalid token is always rejected.',
    'save'                 => 'Save',

    'field_hide_attribution'      => 'Hide attribution link',
    'field_hide_attribution_hint' => 'Hide the "Cap" link displayed in the bottom-right corner of the widget.',

    'field_cap_disabled'           => 'Disable Cap',
    'field_cap_disabled_hint'      => 'When enabled, this form is excluded from Cap verification entirely — no network request is made to /siteverify. Intended for internal or admin forms that never display the Cap widget.',

    'field_wasm_cdn_fallback'      => 'Allow CDN fallback for WASM',
    'field_wasm_cdn_fallback_hint' => 'When enabled, the addon falls back to cdn.jsdelivr.net if the local WASM file has not been published via `php artisan cap:publish-wasm`. Disabled by default: a missing local WASM returns a 503 rather than silently depending on an external CDN.',

    'widget_initial_state'        => "Verify you're human",
    'widget_required_label'       => "Please verify you're human",
    'widget_verifying_label'      => 'Verifying...',
    'widget_verifying_aria_label' => "Verifying you're a human, please wait",
    'widget_verified_aria_label'  => "We have verified you're a human, you may now continue",
    'widget_error_label'          => 'Error',
    'widget_error_aria_label'     => 'An error occurred, please try again',
    'widget_wasm_disabled'        => 'Enable WASM for significantly faster solving',
    'widget_verify_aria_label'    => "Click to verify you're a human",
    'widget_troubleshooting_label' => 'Troubleshoot',
    'widget_solved_label'         => "You're a human",
];
