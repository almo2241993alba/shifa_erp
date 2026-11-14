<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#102f40">
        <title>{{ config('app.name', 'شفاء') }} | إدارة صيدليتك بثقة</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="landing-body">
        <header class="landing-nav">
            <a class="brand-lockup" href="{{ url('/') }}">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/></svg></span>
                <span class="brand-copy"><strong>{{ config('app.name', 'شفاء') }}</strong><small>إدارة الصيدلية الذكية</small></span>
            </a>
            <div class="landing-nav-actions">
                <span class="landing-status"><i></i> نظام محلي آمن</span>
                @auth
                    <a class="button button-primary" href="{{ route('dashboard') }}">الانتقال إلى النظام</a>
                @else
                    <a class="button button-primary" href="{{ route('login') }}">تسجيل الدخول <span aria-hidden="true">←</span></a>
                @endauth
            </div>
        </header>

        <main class="landing-main">
            <section class="landing-hero">
                <div class="landing-hero-copy">
                    <span class="landing-kicker"><i></i> تجربة إدارة صيدلية متكاملة</span>
                    <h1>إدارة يومك أسهل.<br><em>ورعاية مرضاك أفضل.</em></h1>
                    <p>من لحظة استلام الدواء وحتى إتمام البيع، يجمع شفاء عمليات الصيدلية في مساحة واحدة مصممة لتكون واضحة وسريعة.</p>
                    <div class="landing-hero-actions">
                        @auth
                            <a class="button button-primary button-large" href="{{ route('dashboard') }}">افتح لوحة الإدارة <span aria-hidden="true">←</span></a>
                        @else
                            <a class="button button-primary button-large" href="{{ route('login') }}">ابدأ تسجيل الدخول <span aria-hidden="true">←</span></a>
                        @endauth
                        <span class="landing-note"><span>✓</span> مصمم لسير العمل اليومي للصيدلية</span>
                    </div>
                    <div class="landing-trust-row">
                        <div><strong>01</strong><span>المبيعات ونقطة البيع</span></div>
                        <div><strong>02</strong><span>المخزون والدفعات</span></div>
                        <div><strong>03</strong><span>التأمين والحسابات</span></div>
                    </div>
                </div>
                <div class="landing-preview">
                    <div class="preview-glow"></div>
                    <div class="preview-window">
                        <div class="preview-topbar">
                            <span class="preview-dots"><i></i><i></i><i></i></span>
                            <span>معاينة توضيحية · لوحة الإدارة</span>
                            <span class="preview-avatar">م</span>
                        </div>
                        <div class="preview-content">
                            <div class="preview-greeting"><span>ملخص أداء الصيدلية</span><strong>{{ now()->locale('ar')->translatedFormat('l، j F') }}</strong></div>
                            <div class="preview-stats">
                                <div><small>إجمالي المبيعات</small><strong>١٢,٨٥٠ <i>ج.س</i></strong><span>↗ أداء اليوم</span></div>
                                <div><small>فواتير اليوم</small><strong>٤٨ <i>فاتورة</i></strong><span>عمليات مكتملة</span></div>
                                <div><small>أصناف نشطة</small><strong>٢,٣٤٠ <i>صنف</i></strong><span>ضمن الدليل</span></div>
                            </div>
                            <div class="preview-panels">
                                <div class="preview-chart">
                                    <div class="preview-panel-title"><strong>حركة المبيعات</strong><span>آخر ٧ أيام</span></div>
                                    <div class="preview-bars"><i style="height:34%"></i><i style="height:53%"></i><i style="height:44%"></i><i style="height:71%"></i><i style="height:58%"></i><i style="height:84%"></i><i style="height:68%"></i></div>
                                    <div class="preview-days"><span>س</span><span>ح</span><span>ن</span><span>ث</span><span>ر</span><span>خ</span><span>ج</span></div>
                                </div>
                                <div class="preview-stock">
                                    <div class="preview-panel-title"><strong>تنبيهات المخزون</strong><span class="preview-alert">٢</span></div>
                                    <div class="preview-stock-item"><i>د</i><span>أموكسيسيلين</span><b>١٢</b></div>
                                    <div class="preview-stock-item"><i>م</i><span>باراسيتامول</span><b>٨</b></div>
                                    <div class="preview-stock-item"><i>س</i><span>فيتامين سي</span><b>٢٤</b></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="preview-float-card"><span>✓</span><div><strong>جلسة البيع جاهزة</strong><small>كل العمليات في مكان واحد</small></div></div>
                    <span class="landing-orbit landing-orbit-one"></span>
                    <span class="landing-orbit landing-orbit-two"></span>
                </div>
            </section>

            <section class="landing-capabilities" aria-label="مزايا النظام">
                <div class="capability-card"><span>01</span><strong>بيع مرن وسريع</strong><small>واجهة كاشير واضحة وفواتير مرتبة.</small></div>
                <div class="capability-card"><span>02</span><strong>مخزون موثوق</strong><small>تتبع الدفعات والصلاحية بطريقة أدق.</small></div>
                <div class="capability-card"><span>03</span><strong>حسابات مترابطة</strong><small>حركات مالية وقيود موثقة تلقائيًا.</small></div>
                <div class="capability-card"><span>04</span><strong>تأمين صحي جاهز</strong><small>تغطيات ومطالبات ضمن دورة العمل.</small></div>
            </section>
        </main>
        <footer class="landing-footer">
            <span>© {{ now()->year }} {{ config('app.name', 'شفاء') }}</span>
            <span>منصة محلية لإدارة الصيدلية</span>
        </footer>
    </body>
</html>
