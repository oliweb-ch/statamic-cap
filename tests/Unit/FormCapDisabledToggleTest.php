<?php

namespace StatamicCap\Tests\Unit;

use Statamic\Facades\Form;
use StatamicCap\Tests\TestCase;

class FormCapDisabledToggleTest extends TestCase
{
    // ---------------------------------------------------------------
    // Test principal : enregistrement via appendConfigFields
    // ---------------------------------------------------------------

    public function test_cap_disabled_field_is_registered_as_extra_config(): void
    {
        // extraConfigFor() retourne les champs enregistrés via appendConfigFields().
        // La clé de section est Str::snake($display) : Str::snake('Cap') === 'cap'.
        $config = Form::extraConfigFor('any_handle');

        $this->assertArrayHasKey('cap', $config,
            'La section "cap" est absente de extraConfigFor — appendConfigFields("Cap", ...) n\'a pas été appelé.');

        $this->assertArrayHasKey('cap_disabled', $config['cap']['fields'],
            'Le champ cap_disabled est absent de la section Cap.');

        $this->assertSame('toggle', $config['cap']['fields']['cap_disabled']['type'],
            'Le champ cap_disabled n\'est pas de type toggle.');
    }

    public function test_cap_disabled_field_applies_to_all_forms(): void
    {
        // Le wildcard '*' doit s'appliquer à tout handle, y compris des handles fictifs.
        foreach (['contact', 'newsletter', 'admin-form', 'some-handle'] as $handle) {
            $config = Form::extraConfigFor($handle);
            $this->assertArrayHasKey('cap', $config,
                "La section Cap est absente pour le formulaire « $handle ».");
        }
    }

    // ---------------------------------------------------------------
    // Test bout-en-bout : persistance via Form::save() → Form::find()
    // ---------------------------------------------------------------

    public function test_cap_disabled_persists_through_form_yaml(): void
    {
        // En testbench, resource_path('forms') n'existe pas par défaut.
        // On le crée pour ce test et on nettoie après.
        $formsDir = config('statamic.forms.forms');
        @mkdir($formsDir, 0755, true);
        $formFile = $formsDir . '/cap-test-disabled.yaml';

        try {
            Form::make('cap-test-disabled')
                ->title('Cap Test Disabled')
                ->merge(['cap_disabled' => true])
                ->saveQuietly();

            $form = Form::find('cap-test-disabled');

            $this->assertNotNull($form,
                'Le formulaire est introuvable après saveQuietly() — le fichier YAML n\'a pas été créé.');

            $this->assertTrue((bool) $form->get('cap_disabled'),
                'cap_disabled n\'est pas persisté dans le YAML : Form::find()->get("cap_disabled") ne retourne pas true.');
        } finally {
            @unlink($formFile);
            // Ne supprime pas $formsDir s'il contenait d'autres fichiers.
        }
    }
}
