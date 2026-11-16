<?php

use App\Notifications\PasswordResetRequested;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        Notification::route('mail', config('app.general_manager_email'))
            ->notify(new PasswordResetRequested($this->email));

        $this->reset('email');

        session()->flash('status', __('We sent a reset link to your email and notified the general manager.'));
    }
}; ?>

<div class="auth-form-content">
    <span class="eyebrow">{{ __('Account recovery') }}</span>
    <h2>{{ __('Forgot your password?') }}</h2>
    <p class="auth-form-description">{{ __('Enter your account email. We will send a secure password reset link and notify the general manager.') }}</p>

    <x-auth-session-status class="auth-status-message" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="auth-form">
        <div class="auth-field">
            <label class="field-label" for="email">{{ __('Email') }}</label>
            <x-text-input wire:model="email" id="email" class="auth-input" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="auth-field-error" />
        </div>

        <button class="button button-primary auth-submit" type="submit">{{ __('Send reset link') }}</button>
        <a class="auth-inline-link" href="{{ route('login') }}" wire:navigate>{{ __('Back to sign in') }}</a>
    </form>
</div>
