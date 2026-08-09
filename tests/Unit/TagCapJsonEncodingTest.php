<?php

namespace StatamicCap\Tests\Unit;

use StatamicCap\Tags\Cap;
use StatamicCap\Tests\TestCase;

class TagCapJsonEncodingTest extends TestCase
{
    // ---------------------------------------------------------------
    // {{ cap:config }} — endpoint et tokenField encodés avec JSON_HEX_*
    // ---------------------------------------------------------------

    public function test_config_encodes_angle_brackets_in_endpoint(): void
    {
        // Sans JSON_HEX_TAG, json_encode produit "<script>alert(1)" en clair
        // à l'intérieur du bloc <script> → injection XSS possible.
        config(['statamic-cap.endpoint'    => 'https://cap.example.com/<script>alert(1)</script>']);
        config(['statamic-cap.token_field' => 'cap-token']);

        $output = $this->makeTag()->config();

        $this->assertStringNotContainsString('<script>alert', $output,
            '{{ cap:config }} expose <script> non encodé dans le JSON — injection XSS possible.');
        $this->assertStringContainsString('\u003Cscript\u003E', $output,
            '{{ cap:config }} devrait encoder < et > en \u003C / \u003E via JSON_HEX_TAG.');
    }

    public function test_config_encodes_ampersand_in_endpoint(): void
    {
        // JSON_HEX_AMP encode & en \u0026, évitant toute double-interprétation
        // HTML dans des parseurs qui traiteraient le JSON comme de l'HTML.
        config(['statamic-cap.endpoint'    => 'https://cap.example.com/key?a=1&b=2']);
        config(['statamic-cap.token_field' => 'cap-token']);

        $output = $this->makeTag()->config();

        $this->assertStringNotContainsString('"&b', $output,
            '{{ cap:config }} expose & non encodé dans le JSON.');
        $this->assertStringContainsString('\u0026', $output,
            '{{ cap:config }} devrait encoder & en \u0026 via JSON_HEX_AMP.');
    }

    // ---------------------------------------------------------------
    // {{ cap:scripts nonce="..." }} — nonce encodé avec JSON_HEX_*
    // ---------------------------------------------------------------

    public function test_scripts_encodes_angle_brackets_in_nonce(): void
    {
        // Un nonce contenant <script> serait injecté tel quel dans
        // window.CAP_CSP_NONCE sans les flags JSON_HEX_TAG, ce qui
        // permettrait de fermer prématurément le bloc <script>.
        $output = $this->makeTag('<script>evil')->scripts();

        $this->assertStringNotContainsString('<script>evil', $output,
            '{{ cap:scripts }} expose <script> non encodé dans le nonce.');
        $this->assertStringContainsString('\u003Cscript\u003E', $output,
            '{{ cap:scripts }} devrait encoder < et > via JSON_HEX_TAG.');
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    private function makeTag(?string $nonce = null): Cap
    {
        $tag = new Cap();

        // params est une propriété publique de Statamic\Tags\Tags.
        // On injecte un objet anonyme qui répond à get() pour éviter
        // de devoir construire l'objet Parameters (requiert un Context).
        $tag->params = new class ($nonce) {
            public function __construct(private ?string $nonce) {}

            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'nonce' ? $this->nonce : $default;
            }
        };

        return $tag;
    }
}
