<?php

namespace StatamicCap\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Mockery;
use Statamic\Contracts\Forms\Form;
use Statamic\Contracts\Forms\Submission;
use Statamic\Events\FormSubmitted;
use StatamicCap\Listeners\ValidateCapToken;
use StatamicCap\Tests\TestCase;

class ValidateCapTokenFormOptOutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'statamic-cap.endpoint'    => 'https://cap.example.com/key/',
            'statamic-cap.token_field' => 'cap-token',
            'statamic-cap.timeout'     => 5,
            'statamic-cap.fail_open'   => false,
            'cap.secret'               => 'test-secret',
        ]);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function makeEvent(bool|null $capDisabled): FormSubmitted
    {
        $form = Mockery::mock(Form::class);

        if ($capDisabled === null) {
            // Simule l'absence de la clé : ContainsData::get() retourne le $fallback tel quel.
            $form->shouldReceive('get')
                ->with('cap_disabled', false)
                ->andReturnUsing(fn($key, $fallback) => $fallback);
        } else {
            $form->shouldReceive('get')
                ->with('cap_disabled', false)
                ->andReturn($capDisabled);
        }

        $submission = Mockery::mock(Submission::class);
        $submission->shouldReceive('form')->andReturn($form);

        return new FormSubmitted($submission);
    }

    private function bindRequest(string $token): void
    {
        $request = Request::create('/', 'POST', ['cap-token' => $token]);
        $this->app->instance('request', $request);
    }

    // ---------------------------------------------------------------
    // cap_disabled=true — court-circuit complet, aucun appel réseau
    // ---------------------------------------------------------------

    public function test_disabled_form_skips_verification_entirely(): void
    {
        Http::fake();
        $this->bindRequest(''); // token absent — serait rejeté sans opt-out

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent(true));

        // Aucune requête HTTP ne doit partir — la vérification réseau est court-circuitée
        Http::assertNothingSent();
        $this->assertTrue(true);
    }

    public function test_disabled_form_does_not_throw_even_with_invalid_token(): void
    {
        Http::fake([
            '*' => Http::response(['success' => false], 200),
        ]);
        $this->bindRequest('clearly-invalid-token');

        // Aucune exception ne doit être levée malgré le token invalide
        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent(true));

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------
    // cap_disabled=false explicite — comportement identique à avant
    // ---------------------------------------------------------------

    public function test_explicitly_enabled_form_rejects_invalid_token(): void
    {
        Http::fake([
            'https://cap.example.com/key/siteverify' => Http::response(['success' => false], 200),
        ]);
        $this->bindRequest('bad-token');

        $this->expectException(ValidationException::class);

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent(false));
    }

    public function test_explicitly_enabled_form_accepts_valid_token(): void
    {
        Http::fake([
            'https://cap.example.com/key/siteverify' => Http::response(['success' => true], 200),
        ]);
        $this->bindRequest('good-token');

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent(false));

        $this->assertTrue(true);
    }

    // ---------------------------------------------------------------
    // cap_disabled absent — équivaut à cap_disabled=false (protégé)
    // ---------------------------------------------------------------

    public function test_form_without_cap_disabled_key_behaves_as_protected(): void
    {
        Http::fake([
            'https://cap.example.com/key/siteverify' => Http::response(['success' => false], 200),
        ]);
        $this->bindRequest('bad-token');

        // null → clé absente → fallback false → le formulaire est protégé → rejet
        $this->expectException(ValidationException::class);

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent(null));
    }
}
