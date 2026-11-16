<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="auth-form-content">
    <span class="eyebrow">{{ __('Welcome back') }}</span>
    <h2>{{ __('Sign in') }}</h2>
    <p class="auth-form-description">{{ __('Sign in to continue to your pharmacy workspace.') }}</p>

    @if (session('registration_pending'))
        <div class="auth-status-message" role="status">{{ __('Your registration request was received. Your account will be available after the general manager reviews and approves it.') }}</div>
    @endif

    <x-auth-session-status class="auth-status-message" :status="session('status')" />

    <form wire:submit="login" class="auth-form">
        <div class="auth-field">
            <label class="field-label" for="email">{{ __('Email') }}</label>
            <x-text-input wire:model="form.email" id="email" class="auth-input" type="email" name="email" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('form.email')" class="auth-field-error" />
        </div>

        <div class="auth-field">
            <div class="auth-label-row">
                <label class="field-label" for="password">{{ __('Password') }}</label>
                @if (Route::has('password.request'))
                    <a class="auth-inline-link" href="{{ route('password.request') }}" wire:navigate>{{ __('Forgot your password?') }}</a>
                @endif
            </div>
            <div class="auth-password-control">
                <x-text-input wire:model="form.password" id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="{{ __('Enter your password') }}" />
                <button class="password-toggle" type="button" aria-label="{{ __('Show password') }}" aria-controls="password" aria-pressed="false" data-password-toggle>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="auth-field-error" />
        </div>

        <label class="auth-remember" for="remember">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember">
            <span>{{ __('Remember me on this device') }}</span>
        </label>

        <button class="button button-primary auth-submit" type="submit">
            <span>{{ __('Sign in to the system') }}</span>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </form>

    <div class="auth-security-note"><span>✓</span> {{ __('Your access is protected and limited to approved system users.') }}</div>
</div>
