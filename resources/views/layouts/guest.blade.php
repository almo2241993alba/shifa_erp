@php
    $appName = config('app.name') === 'شفاء' ? __('Shifa') : config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#102f40">
        <title>{{ $appName }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-body">
        <main class="auth-layout">
            <section class="auth-brand-panel">
                <a class="brand-lockup auth-brand-lockup" href="{{ url('/') }}">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none"><path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/></svg>
                    </span>
                    <span class="brand-copy"><strong>{{ $appName }}</strong><small>{{ __('Smarter pharmacy management') }}</small></span>
                </a>
                <div class="auth-brand-copy">
                    <span class="eyebrow eyebrow-light">{{ __('The complete pharmacy platform') }}</span>
                    <h1>{{ __('Everything your pharmacy needs,') }}<br><em>{{ __('in one place.') }}</em></h1>
                    <p>{{ __('From point of sale and inventory to accounting and insurance, clear tools help you manage your day with confidence.') }}</p>
                </div>
                <div class="auth-feature-row">
                    <span><i>✓</i> {{ __('More accurate inventory') }}</span>
                    <span><i>✓</i> {{ __('Faster checkout') }}</span>
                    <span><i>✓</i> {{ __('Clearer reports') }}</span>
                </div>
                <span class="auth-orbit auth-orbit-one"></span>
                <span class="auth-orbit auth-orbit-two"></span>
            </section>
            <section class="auth-form-panel">
                <form class="auth-locale-switch" method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
                    <label class="sr-only" for="guest-locale-switch">{{ __('Language') }}</label>
                    <select id="guest-locale-switch" name="locale" aria-label="{{ __('Language') }}" onchange="this.form.submit()">
                        <option value="ar" @selected(app()->getLocale() === 'ar')>{{ __('Arabic') }}</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>{{ __('English') }}</option>
                    </select>
                </form>
                <div class="auth-mobile-brand">
                    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/></svg></span>
                    <strong>{{ $appName }}</strong>
                </div>
                <div class="auth-card">
                    {{ $slot }}
                </div>
                <p class="auth-copyright">© {{ now()->year }} {{ $appName }} · {{ __('All rights reserved') }}</p>
            </section>
        </main>
    </body>
</html>
