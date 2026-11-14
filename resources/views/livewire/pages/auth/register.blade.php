<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
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

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="auth-form-content">
        <span class="eyebrow">إنشاء حساب جديد</span>
        <h2>تسجيل مستخدم جديد</h2>
        <p class="auth-form-description">أنشئ حسابك للبدء باستخدام النظام.</p>
    </div>
    <form wire:submit="register">
        <div class="auth-field">
            <label class="field-label" for="name">الاسم الكامل</label>
            <x-text-input wire:model="name" id="name" class="auth-input" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="auth-field">
            <label class="field-label" for="email">البريد الإلكتروني</label>
            <x-text-input wire:model="email" id="email" class="auth-input" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="auth-field">
            <label class="field-label" for="password">كلمة المرور</label>
            <x-text-input wire:model="password" id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="auth-field">
            <label class="field-label" for="password_confirmation">تأكيد كلمة المرور</label>
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="auth-register-actions">
            <a class="auth-inline-link" href="{{ route('login') }}" wire:navigate>
                لديك حساب بالفعل؟ سجل الدخول
            </a>

            <button class="button button-primary auth-submit" type="submit">إنشاء الحساب</button>
        </div>
    </form>
</div>
