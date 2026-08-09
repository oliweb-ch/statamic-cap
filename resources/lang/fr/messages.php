<?php

return [
    'validation_failed'         => 'La vérification Cap a échoué. Veuillez compléter le défi et réessayer.',
    'validation_endpoint_https' => "L'URL de l'instance Cap doit utiliser HTTPS.",
    'form_failed'          => 'La vérification Cap a échoué. Veuillez réessayer.',
    'settings_saved'       => 'Réglages Cap enregistrés.',

    'field_endpoint'       => 'URL de l\'instance Cap',
    'field_endpoint_hint'  => 'URL complète de votre instance Cap auto-hébergée, incluant le site-key. Exemple : https://cap.example.com/votre-site-key/',
    'field_secret'         => 'Clé secrète',
    'field_secret_hint'    => 'La clé secrète de votre tableau de bord Cap. Ne jamais l\'exposer publiquement.',
    'field_token_field'    => 'Nom du champ token',
    'field_token_field_hint' => 'Nom du champ hidden injecté par le widget Cap dans le formulaire parent.',
    'field_timeout'        => 'Timeout (secondes)',
    'field_timeout_hint'   => 'Délai en secondes avant abandon de la requête vers /siteverify.',
    'field_fail_open'      => 'Fail Open',
    'field_fail_open_hint' => 'Activé : toute erreur de communication avec Cap laisse passer la requête. Un token explicitement invalide est toujours refusé.',
    'save'                 => 'Enregistrer',

    'field_hide_attribution'      => "Masquer le lien d'attribution",
    'field_hide_attribution_hint' => 'Masque le lien "Cap" affiché en bas à droite du widget.',

    'field_wasm_cdn_fallback'      => 'Autoriser le repli CDN pour le WASM',
    'field_wasm_cdn_fallback_hint' => 'Activé : si le WASM local n\'a pas été publié via `php artisan cap:publish-wasm`, l\'addon se rabat sur cdn.jsdelivr.net. Désactivé par défaut : l\'absence du WASM local retourne un 503 plutôt que de dépendre silencieusement d\'un CDN externe.',

    'widget_initial_state'        => 'Prouvez que vous êtes humain',
    'widget_required_label'       => 'Veuillez prouver que vous êtes humain',
    'widget_verifying_label'      => 'Vérification…',
    'widget_verifying_aria_label' => 'Vérification en cours, veuillez patienter',
    'widget_verified_aria_label'  => 'Vous avez été vérifié, vous pouvez continuer',
    'widget_error_label'          => 'Erreur',
    'widget_error_aria_label'     => 'Une erreur s\'est produite, veuillez réessayer',
    'widget_wasm_disabled'        => 'Activez WASM pour une résolution plus rapide',
    'widget_verify_aria_label'    => 'Cliquez pour prouver que vous êtes humain',
    'widget_troubleshooting_label' => 'Dépannage',
    'widget_solved_label'         => 'Vous êtes humain',
];
