<?php

namespace StatamicCap\Listeners;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Validation\ValidationException;
use LaravelCap\Cap;
use Statamic\Events\FormSubmitted;

class ValidateCapToken
{
    public function handle(FormSubmitted $event): void
    {
        $tokenField = config('statamic-cap.token_field', 'cap-token');
        $token = request()->input($tokenField);

        $config = [
            'endpoint'    => config('statamic-cap.endpoint', config('cap.endpoint')),
            'secret'      => config('cap.secret'),
            'token_field' => config('statamic-cap.token_field', config('cap.token_field', 'cap-token')),
            'timeout'     => config('statamic-cap.timeout', config('cap.timeout', 5)),
            'fail_open'   => config('statamic-cap.fail_open', config('cap.fail_open', false)),
        ];

        $cap = new Cap(app(HttpFactory::class), $config);

        if (! $cap->verify((string) $token)) {
            throw ValidationException::withMessages([
                $tokenField => [__('statamic-cap::messages.validation_failed')],
            ]);
        }
    }
}
