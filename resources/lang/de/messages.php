<?php

return [
    'validation_failed'         => 'Die Cap-Verifizierung ist fehlgeschlagen. Bitte lösen Sie die Aufgabe und versuchen Sie es erneut.',
    'validation_endpoint_https' => 'Die Cap-Endpunkt-URL muss HTTPS verwenden.',
    'form_failed'            => 'Die Cap-Verifizierung ist fehlgeschlagen. Bitte versuchen Sie es erneut.',
    'settings_saved'         => 'Cap-Einstellungen gespeichert.',

    'field_endpoint'         => 'Cap-Endpunkt-URL',
    'field_endpoint_hint'    => 'Vollständige URL Ihrer selbst gehosteten Cap-Instanz, einschließlich des Site-Keys. Beispiel: https://cap.example.com/ihr-site-key/',
    'field_secret'           => 'Geheimer Schlüssel',
    'field_secret_hint'      => 'Der geheime Schlüssel aus Ihrem Cap-Dashboard. Niemals öffentlich zugänglich machen.',
    'field_token_field'      => 'Name des Token-Felds',
    'field_token_field_hint' => 'Name des versteckten Felds, das das Cap-Widget in das übergeordnete Formular einfügt.',
    'field_timeout'          => 'Zeitlimit (Sekunden)',
    'field_timeout_hint'     => 'Zeit in Sekunden, bevor die Anfrage an /siteverify abgebrochen wird.',
    'field_fail_open'        => 'Fail Open',
    'field_fail_open_hint'   => 'Wenn aktiviert, lässt jeder Kommunikationsfehler mit Cap die Anfrage durch. Ein explizit ungültiges Token wird immer abgelehnt.',
    'save'                   => 'Speichern',

    'field_hide_attribution'      => 'Attributionslink ausblenden',
    'field_hide_attribution_hint' => 'Blendet den "Cap"-Link unten rechts im Widget aus.',

    'field_wasm_cdn_fallback'      => 'CDN-Fallback für WASM erlauben',
    'field_wasm_cdn_fallback_hint' => 'Wenn aktiviert, fällt das Addon auf cdn.jsdelivr.net zurück, falls die lokale WASM-Datei nicht über `php artisan cap:publish-wasm` veröffentlicht wurde. Standardmäßig deaktiviert: Eine fehlende lokale WASM-Datei gibt einen 503 zurück, anstatt stillschweigend von einem externen CDN abhängig zu sein.',

    'widget_initial_state'        => 'Beweise, dass du ein Mensch bist',
    'widget_required_label'       => 'Bitte beweise, dass du ein Mensch bist',
    'widget_verifying_label'      => 'Wird überprüft…',
    'widget_verifying_aria_label' => 'Überprüfung läuft, bitte warten',
    'widget_verified_aria_label'  => 'Sie wurden verifiziert, Sie können fortfahren',
    'widget_error_label'          => 'Fehler',
    'widget_error_aria_label'     => 'Ein Fehler ist aufgetreten, bitte versuchen Sie es erneut',
    'widget_wasm_disabled'        => 'WASM aktivieren für deutlich schnellere Lösung',
    'widget_verify_aria_label'    => 'Klicken Sie, um zu beweisen, dass Sie ein Mensch sind',
    'widget_troubleshooting_label' => 'Fehlerbehebung',
    'widget_solved_label'         => 'Sie sind ein Mensch',
];
