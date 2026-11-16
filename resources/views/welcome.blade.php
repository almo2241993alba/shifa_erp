@php
    $appName = config('app.name') === 'شفاء' ? __('Shifa') : config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#102f40">
        <title>{{ $appName }} | {{ __('Manage your pharmacy with confidence') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="landing-body">
        <header class="landing-nav">
            <a class="brand-lockup" href="{{ url('/') }}">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/></svg></span>
                <span class="brand-copy"><strong>{{ $appName }}</strong><small>{{ __('Smart pharmacy management') }}</small></span>
            </a>
            <div class="landing-nav-actions">
                <form class="landing-locale-switch" method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
                    <label class="sr-only" for="landing-locale-switch">{{ __('Language') }}</label>
                    <select id="landing-locale-switch" name="locale" aria-label="{{ __('Language') }}" onchange="this.form.submit()">
                        <option value="ar" @selected(app()->getLocale() === 'ar')>{{ __('Arabic') }}</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>{{ __('English') }}</option>
                    </select>
                </form>
                <span class="landing-status"><i></i> {{ __('Secure local system') }}</span>
                @auth
                    <a class="button button-primary" href="{{ route('dashboard') }}">{{ __('Open the application') }}</a>
                @else
                    <a class="button button-primary" href="{{ route('login') }}">{{ __('Sign in') }} <span aria-hidden="true">←</span></a>
                @endauth
            </div>
        </header>

        <main class="landing-main">
            <section class="landing-hero">
                <div class="landing-hero-copy">
                    <span class="landing-kicker"><i></i> {{ __('A complete pharmacy management experience') }}</span>
                    <h1>{{ __('Make your day easier.') }}<br><em>{{ __('Care for your patients better.') }}</em></h1>
                    <p>{{ __('From receiving medicines to completing sales, Shifa brings your pharmacy operations together in one clear, fast workspace.') }}</p>
                    <div class="landing-hero-actions">
                        @auth
                            <a class="button button-primary button-large" href="{{ route('dashboard') }}">{{ __('Open the management dashboard') }} <span aria-hidden="true">←</span></a>
                        @else
                            <a class="button button-primary button-large" href="{{ route('login') }}">{{ __('Get started') }} <span aria-hidden="true">←</span></a>
                        @endauth
                        <span class="landing-note"><span>✓</span> {{ __('Designed for the daily pharmacy workflow') }}</span>
                    </div>
                    <div class="landing-trust-row">
                        <div><strong>01</strong><span>{{ __('Sales and point of sale') }}</span></div>
                        <div><strong>02</strong><span>{{ __('Inventory and batches') }}</span></div>
                        <div><strong>03</strong><span>{{ __('Insurance and accounts') }}</span></div>
                    </div>
                </div>
                <div class="landing-preview">
                    <div class="preview-glow"></div>
                    <div class="preview-window">
                        <div class="preview-topbar">
                            <span class="preview-dots"><i></i><i></i><i></i></span>
                            <span>{{ __('Dashboard preview') }}</span>
                            <span class="preview-avatar">{{ app()->getLocale() === 'ar' ? 'م' : 'M' }}</span>
                        </div>
                        <div class="preview-content">
                            <div class="preview-greeting"><span>{{ __('Pharmacy performance summary') }}</span><strong>{{ now()->locale(app()->getLocale())->translatedFormat('l، j F') }}</strong></div>
                            <div class="preview-stats">
                                <div><small>{{ __('Total sales') }}</small><strong>{{ app()->getLocale() === 'ar' ? '١٢,٨٥٠' : '12,850' }} <i>{{ __('SDG') }}</i></strong><span>↗ {{ __('Today') }}</span></div>
                                <div><small>{{ __('Invoices today') }}</small><strong>{{ app()->getLocale() === 'ar' ? '٤٨' : '48' }} <i>{{ __('invoices') }}</i></strong><span>{{ __('Completed operations') }}</span></div>
                                <div><small>{{ __('Active products') }}</small><strong>{{ app()->getLocale() === 'ar' ? '٢,٣٤٠' : '2,340' }} <i>{{ __('items') }}</i></strong><span>{{ __('In catalog') }}</span></div>
                            </div>
                            <div class="preview-panels">
                                <div class="preview-chart">
                                    <div class="preview-panel-title"><strong>{{ __('Sales activity') }}</strong><span>{{ __('Last 7 days') }}</span></div>
                                    <div class="preview-bars"><i style="height:34%"></i><i style="height:53%"></i><i style="height:44%"></i><i style="height:71%"></i><i style="height:58%"></i><i style="height:84%"></i><i style="height:68%"></i></div>
                                    <div class="preview-days">@foreach (range(0, 6) as $day)<span>{{ now()->locale(app()->getLocale())->startOfWeek()->addDays($day)->translatedFormat('D') }}</span>@endforeach</div>
                                </div>
                                <div class="preview-stock">
                                    <div class="preview-panel-title"><strong>{{ __('Inventory alerts') }}</strong><span class="preview-alert">{{ app()->getLocale() === 'ar' ? '٢' : '2' }}</span></div>
                                    <div class="preview-stock-item"><i>{{ app()->getLocale() === 'ar' ? 'د' : 'M' }}</i><span>{{ __('Amoxicillin') }}</span><b>{{ app()->getLocale() === 'ar' ? '١٢' : '12' }}</b></div>
                                    <div class="preview-stock-item"><i>{{ app()->getLocale() === 'ar' ? 'م' : 'P' }}</i><span>{{ __('Paracetamol') }}</span><b>{{ app()->getLocale() === 'ar' ? '٨' : '8' }}</b></div>
                                    <div class="preview-stock-item"><i>{{ app()->getLocale() === 'ar' ? 'س' : 'V' }}</i><span>{{ __('Vitamin C') }}</span><b>{{ app()->getLocale() === 'ar' ? '٢٤' : '24' }}</b></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="preview-float-card"><span>✓</span><div><strong>{{ __('The sales session is ready') }}</strong><small>{{ __('All operations in one place') }}</small></div></div>
                    <span class="landing-orbit landing-orbit-one"></span>
                    <span class="landing-orbit landing-orbit-two"></span>
                </div>
            </section>

            <section class="landing-capabilities" aria-label="{{ __('System features') }}">
                <div class="capability-card"><span>01</span><strong>{{ __('Flexible, fast sales') }}</strong><small>{{ __('A clear cashier interface and organized invoices.') }}</small></div>
                <div class="capability-card"><span>02</span><strong>{{ __('Reliable inventory') }}</strong><small>{{ __('Track batches and expiry dates more accurately.') }}</small></div>
                <div class="capability-card"><span>03</span><strong>{{ __('Connected accounts') }}</strong><small>{{ __('Financial transactions and documented journal entries.') }}</small></div>
                <div class="capability-card"><span>04</span><strong>{{ __('Insurance ready') }}</strong><small>{{ __('Coverage and claims within your workflow.') }}</small></div>
            </section>
        </main>
        <footer class="landing-footer">
            <span>© {{ now()->year }} {{ $appName }}</span>
            <span>{{ __('A local pharmacy management platform') }}</span>
        </footer>
    </body>
</html>
