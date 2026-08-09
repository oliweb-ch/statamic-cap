@extends('statamic::layout')
@section('title', 'Cap CAPTCHA')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="flex-1 text-3xl">Cap CAPTCHA</h1>
    </div>

    @if (session('success'))
        <div class="card p-4 mb-6 content bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="card p-4 mb-6 bg-red-100 text-red-800 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card p-4">
        <form method="POST" action="{{ cp_route('statamic-cap.settings.update') }}">
            @csrf

            <div class="publish-fields">

                <div class="form-group mb-4">
                    <label class="block font-bold text-sm mb-1">
                        {{ __('statamic-cap::messages.field_endpoint') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="url"
                           name="endpoint"
                           value="{{ old('endpoint', $settings['endpoint'] ?? '') }}"
                           class="input-text w-full"
                           required
                           placeholder="https://cap.example.com/your-site-key/">
                    <p class="text-sm text-grey mt-1">{{ __('statamic-cap::messages.field_endpoint_hint') }}</p>
                </div>

                <div class="form-group mb-4">
                    <label class="block font-bold text-sm mb-1">
                        {{ __('statamic-cap::messages.field_token_field') }}
                    </label>
                    <input type="text"
                           name="token_field"
                           value="{{ old('token_field', $settings['token_field'] ?? 'cap-token') }}"
                           class="input-text w-64">
                    <p class="text-sm text-grey mt-1">{{ __('statamic-cap::messages.field_token_field_hint') }}</p>
                </div>

                <div class="form-group mb-4">
                    <label class="block font-bold text-sm mb-1">
                        {{ __('statamic-cap::messages.field_timeout') }}
                    </label>
                    <input type="number"
                           name="timeout"
                           value="{{ old('timeout', $settings['timeout'] ?? 5) }}"
                           class="input-text w-24"
                           min="1"
                           max="60">
                    <p class="text-sm text-grey mt-1">{{ __('statamic-cap::messages.field_timeout_hint') }}</p>
                </div>

                <div class="form-group mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="fail_open" value="0">
                        <input type="checkbox"
                               name="fail_open"
                               value="1"
                               @checked(old('fail_open', $settings['fail_open'] ?? false))>
                        <span class="font-bold text-sm">{{ __('statamic-cap::messages.field_fail_open') }}</span>
                    </label>
                    <p class="text-sm text-grey mt-1 ml-6">{{ __('statamic-cap::messages.field_fail_open_hint') }}</p>
                </div>

                <div class="form-group mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="hide_attribution" value="0">
                        <input type="checkbox"
                               name="hide_attribution"
                               value="1"
                               @checked(old('hide_attribution', $settings['hide_attribution'] ?? false))>
                        <span class="font-bold text-sm">{{ __('statamic-cap::messages.field_hide_attribution') }}</span>
                    </label>
                    <p class="text-sm text-grey mt-1 ml-6">{{ __('statamic-cap::messages.field_hide_attribution_hint') }}</p>
                </div>

            </div>

            <div class="mt-6">
                <button type="submit" class="btn-primary">
                    {{ __('statamic-cap::messages.save') }}
                </button>
            </div>
        </form>
    </div>
@endsection
