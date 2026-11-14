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
    <span class="eyebrow">مرحبًا بعودتك</span>
    <h2>تسجيل الدخول</h2>
    <p class="auth-form-description">سجّل الدخول للمتابعة إلى مساحة عمل الصيدلية.</p>

    <x-auth-session-status class="auth-status-message" :status="session('status')" />

    <form wire:submit="login" class="auth-form">
        <div class="auth-field">
            <label class="field-label" for="email">البريد الإلكتروني</label>
            <x-text-input wire:model="form.email" id="email" class="auth-input" type="email" name="email" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('form.email')" class="auth-field-error" />
        </div>

        <div class="auth-field">
            <div class="auth-label-row">
                <label class="field-label" for="password">كلمة المرور</label>
                @if (Route::has('password.request'))
                    <a class="auth-inline-link" href="{{ route('password.request') }}" wire:navigate>نسيت كلمة المرور؟</a>
                @endif
            </div>
            <x-text-input wire:model="form.password" id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="أدخل كلمة المرور" />
            <x-input-error :messages="$errors->get('form.password')" class="auth-field-error" />
        </div>

        <label class="auth-remember" for="remember">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember">
            <span>تذكر تسجيل الدخول على هذا الجهاز</span>
        </label>

        <button class="button button-primary auth-submit" type="submit">
            <span>دخول إلى النظام</span>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </form>

    <div class="auth-security-note"><span>✓</span> دخولك محمي ومخصص لمستخدمي النظام المعتمدين.</div>
</div>
