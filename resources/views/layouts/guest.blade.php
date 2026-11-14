<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#102f40">
        <title>{{ config('app.name', 'شفاء') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-body">
        <main class="auth-layout">
            <section class="auth-brand-panel">
                <a class="brand-lockup auth-brand-lockup" href="{{ url('/') }}">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none"><path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/></svg>
                    </span>
                    <span class="brand-copy"><strong>{{ config('app.name', 'شفاء') }}</strong><small>إدارة صيدلية أكثر ذكاءً</small></span>
                </a>
                <div class="auth-brand-copy">
                    <span class="eyebrow eyebrow-light">منصة الصيدلية المتكاملة</span>
                    <h1>كل تفاصيل صيدليتك،<br><em>في مكان واحد.</em></h1>
                    <p>من نقطة البيع والمخزون إلى الحسابات والتأمين — أدوات واضحة تساعدك على إدارة يومك بثقة.</p>
                </div>
                <div class="auth-feature-row">
                    <span><i>✓</i> مخزون أدق</span>
                    <span><i>✓</i> بيع أسرع</span>
                    <span><i>✓</i> تقارير أوضح</span>
                </div>
                <span class="auth-orbit auth-orbit-one"></span>
                <span class="auth-orbit auth-orbit-two"></span>
            </section>
            <section class="auth-form-panel">
                <div class="auth-mobile-brand">
                    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/></svg></span>
                    <strong>{{ config('app.name', 'شفاء') }}</strong>
                </div>
                <div class="auth-card">
                    {{ $slot }}
                </div>
                <p class="auth-copyright">© {{ now()->year }} {{ config('app.name', 'شفاء') }} · جميع الحقوق محفوظة</p>
            </section>
        </main>
    </body>
</html>
