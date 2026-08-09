<?php

namespace StatamicCap\Tests\Unit;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Mockery;
use Statamic\Contracts\Forms\Form;
use Statamic\Contracts\Forms\Submission;
use Statamic\Events\FormSubmitted;
use StatamicCap\Listeners\ValidateCapToken;
use StatamicCap\Tests\TestCase;

class ValidateCapTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'statamic-cap.endpoint'    => 'https://cap.example.com/key/',
            'statamic-cap.token_field' => 'cap-token',
            'statamic-cap.timeout'     => 5,
            'statamic-cap.fail_open'   => false,
            'cap.secret'               => 'test-secret-from-cap-config',
        ]);
    }

    private function makeEvent(): FormSubmitted
    {
        // Depuis l'ajout de la sortie anticipée cap_disabled, handle() appelle
        // $event->submission->form()->get('cap_disabled', false) en première ligne.
        // On stubbe form() pour retourner un Form avec cap_disabled=false (formulaire
        // protégé), reproduisant le comportement d'un formulaire sans cette clé.
        $form = Mockery::mock(Form::class);
        $form->shouldReceive('get')->with('cap_disabled', false)->andReturn(false);

        $submission = Mockery::mock(Submission::class);
        $submission->shouldReceive('form')->andReturn($form);

        return new FormSubmitted($submission);
    }

    private function bindRequest(string $token): void
    {
        $request = Request::create('/', 'POST', ['cap-token' => $token]);
        $this->app->instance('request', $request);
    }

    public function test_valid_token_does_not_throw(): void
    {
        Http::fake([
            'https://cap.example.com/key/siteverify' => Http::response(['success' => true], 200),
        ]);

        $this->bindRequest('valid-token-abc');

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent());

        $this->assertTrue(true);
    }

    public function test_invalid_token_throws_validation_exception(): void
    {
        Http::fake([
            'https://cap.example.com/key/siteverify' => Http::response(['success' => false], 200),
        ]);

        $this->bindRequest('invalid-token-xyz');

        $this->expectException(ValidationException::class);

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent());
    }

    public function test_verification_uses_statamic_cap_endpoint_and_cap_secret(): void
    {
        Http::fake([
            'https://cap.example.com/key/siteverify' => Http::response(['success' => true], 200),
        ]);

        $this->bindRequest('my-token');

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent());

        Http::assertSent(function (HttpRequest $request) {
            return $request->url() === 'https://cap.example.com/key/siteverify'
                && $request['secret'] === 'test-secret-from-cap-config'
                && $request['response'] === 'my-token';
        });
    }

    public function test_fail_open_lets_request_through_on_network_error(): void
    {
        config(['statamic-cap.fail_open' => true]);

        // Simuler une panne réseau (réponse 500)
        Http::fake([
            'https://cap.example.com/key/siteverify' => Http::response('', 500),
        ]);

        $this->bindRequest('any-token');

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent());

        // Avec fail_open=true, aucune exception ne doit être levée
        $this->assertTrue(true);
    }

    public function test_secret_comes_from_cap_namespace_not_statamic_cap(): void
    {
        $this->assertNull(config('statamic-cap.secret'),
            'config("statamic-cap.secret") ne doit pas exister.');

        Http::fake([
            'https://cap.example.com/key/siteverify' => Http::response(['success' => true], 200),
        ]);

        $this->bindRequest('token');

        $listener = new ValidateCapToken();
        $listener->handle($this->makeEvent());

        Http::assertSent(function (HttpRequest $request) {
            return $request['secret'] === 'test-secret-from-cap-config';
        });
    }
}
