<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Endpoint de l'instance Cap
    |--------------------------------------------------------------------------
    |
    | L'URL complète de votre instance Cap auto-hébergée, incluant le site-key.
    | Exemple : https://cap.example.com/votre-site-key/
    |
    | Cette valeur peut être configurée depuis le panneau Statamic CP
    | via Réglages > Cap.
    |
    */
    'endpoint' => env('CAP_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | Nom du champ token
    |--------------------------------------------------------------------------
    |
    | Le nom du champ hidden injecté automatiquement par le widget Cap
    | dans le formulaire parent.
    |
    */
    'token_field' => env('CAP_TOKEN_FIELD', 'cap-token'),

    /*
    |--------------------------------------------------------------------------
    | Timeout de vérification
    |--------------------------------------------------------------------------
    |
    | Délai (en secondes) avant abandon de la requête vers /siteverify.
    |
    */
    'timeout' => (int) env('CAP_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Mode fail-open
    |--------------------------------------------------------------------------
    |
    | Quand true, toute erreur de communication laisse passer la requête.
    | Un token explicitement invalide est toujours refusé.
    |
    */
    'fail_open' => (bool) env('CAP_FAIL_OPEN', false),

    /*
    |--------------------------------------------------------------------------
    | Masquer le lien d'attribution
    |--------------------------------------------------------------------------
    |
    | Quand true, le lien "Cap" affiché en bas à droite du widget est masqué
    | via cap-widget::part(attribution) { display: none }.
    |
    */
    'hide_attribution' => (bool) env('CAP_HIDE_ATTRIBUTION', false),

    /*
    |--------------------------------------------------------------------------
    | Fallback CDN WASM
    |--------------------------------------------------------------------------
    |
    | Quand true, si le WASM local n'a pas été publié via
    | `php artisan cap:publish-wasm`, l'addon se rabat sur
    | cdn.jsdelivr.net pour servir le fichier WASM.
    |
    | Par défaut false : l'absence de WASM local produit un 503 explicite
    | plutôt qu'une dépendance silencieuse à un CDN externe.
    |
    */
    'wasm_cdn_fallback' => (bool) env('CAP_WASM_CDN_FALLBACK', false),
];
