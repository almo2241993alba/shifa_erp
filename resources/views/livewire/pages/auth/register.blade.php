<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $usernameBase = Str::limit(Str::slug(Str::before($validated['email'], '@'), ''), 240, '') ?: 'user';
        $username = $usernameBase;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $usernameBase . $suffix++;
        }

        $validated['username'] = $username;
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'cashier';
        $validated['is_active'] = false;
        $validated['registration_status'] = 'pending';

        User::create($validated);

        session()->flash('registration_pending', true);
        $this->redirect(route('login', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="auth-form-content">
        <span class="eyebrow">{{ __('Create a new account') }}</span>
        <h2>{{ __('Register a new user') }}</h2>
        <p class="auth-form-description">{{ __('Send your details to the general manager. Your account will be activated after review.') }}</p>
    </div>
    <form wire:submit="register" data-wizard data-wizard-start="{{ $errors->hasAny(['password', 'password_confirmation']) ? 1 : 0 }}">
        <section class="wizard-step" data-wizard-step="0">
            <div class="auth-field">
                <label class="field-label" for="name">{{ __('Full name') }}</label>
                <x-text-input wire:model="name" id="name" class="auth-input" type="text" name="name" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="auth-field-error" />
            </div>

            <div class="auth-field">
                <label class="field-label" for="email">{{ __('Email') }}</label>
                <x-text-input wire:model="email" id="email" class="auth-input" type="email" name="email" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="auth-field-error" />
            </div>

            <button class="button button-primary auth-submit" type="button" data-wizard-next>{{ __('Next') }}</button>
        </section>

        <section class="wizard-step" data-wizard-step="1" hidden>
            <div class="auth-field">
                <label class="field-label" for="password">{{ __('Password') }}</label>
                <x-text-input wire:model="password" id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="auth-field-error" />
            </div>

            <div class="auth-field">
                <label class="field-label" for="password_confirmation">{{ __('Confirm Password') }}</label>
                <x-text-input wire:model="password_confirmation" id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="auth-field-error" />
            </div>

            <div class="auth-register-actions">
                <button class="button button-secondary auth-submit" type="button" data-wizard-previous>{{ __('Previous') }}</button>
                <button class="button button-primary auth-submit" type="submit">{{ __('Submit registration request') }}</button>
            </div>
        </section>

        <div class="auth-register-actions">
            <a class="auth-inline-link" href="{{ route('login') }}" wire:navigate>{{ __('Already have an account? Sign in') }}</a>
        </div>
    </form>
</div>
