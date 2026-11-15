@props(['title' => 'لوحة الإدارة'])

@php
    $role = auth()->user()->role;
    $groups = [
        [
            'label' => 'مساحة العمل',
            'links' => [
                ['label' => 'الرئيسية', 'route' => 'dashboard', 'icon' => 'home', 'roles' => ['admin', 'pharmacist', 'accountant', 'storekeeper']],
                ['label' => 'نقطة البيع', 'route' => 'pharmacy.cashier', 'icon' => 'pos', 'roles' => ['admin', 'pharmacist', 'cashier']],
                ['label' => 'تقارير الكاشير', 'route' => 'pharmacy.cashier.reports', 'icon' => 'receipt', 'roles' => ['admin', 'cashier']],
                ['label' => 'التقارير', 'route' => 'pharmacy.reports.index', 'icon' => 'activity', 'roles' => ['admin', 'pharmacist', 'accountant']],
            ],
        ],
        [
            'label' => 'إدارة الصيدلية',
            'links' => [
                ['label' => 'الأدوية والمخزون', 'resource' => 'medicines', 'icon' => 'pill', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => 'دفعات المخزون', 'resource' => 'batches', 'icon' => 'box', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => 'التصنيفات', 'resource' => 'categories', 'icon' => 'grid', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => 'وحدات القياس', 'resource' => 'units', 'icon' => 'ruler', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => 'الأشكال الدوائية', 'resource' => 'dosage-forms', 'icon' => 'layers', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => 'العملاء والموردون', 'resource' => 'contacts', 'icon' => 'users', 'roles' => ['admin', 'pharmacist', 'accountant', 'cashier']],
                ['label' => 'وثائق التأمين', 'resource' => 'insurance-policies', 'icon' => 'shield', 'roles' => ['admin', 'pharmacist', 'accountant']],
            ],
        ],
        [
            'label' => 'المعاملات',
            'links' => [
                ['label' => 'المبيعات', 'resource' => 'sales', 'icon' => 'receipt', 'roles' => ['admin', 'pharmacist', 'cashier']],
                ['label' => 'المشتريات', 'resource' => 'purchases', 'icon' => 'cart', 'roles' => ['admin', 'storekeeper', 'accountant']],
                ['label' => 'المصروفات', 'resource' => 'expenses', 'icon' => 'wallet', 'roles' => ['admin', 'accountant', 'cashier']],
                ['label' => 'مرتجعات المبيعات', 'resource' => 'sale-returns', 'icon' => 'receipt', 'roles' => ['admin', 'pharmacist', 'cashier']],
                ['label' => 'مرتجعات الموردين', 'resource' => 'purchase-returns', 'icon' => 'cart', 'roles' => ['admin', 'storekeeper', 'accountant']],
                ['label' => 'جلسات الكاشير', 'resource' => 'pos-sessions', 'icon' => 'pos', 'roles' => ['admin', 'pharmacist', 'cashier']],
            ],
        ],
        [
            'label' => 'المالية والتأمين',
            'links' => [
                ['label' => 'الخزائن', 'resource' => 'treasuries', 'icon' => 'safe', 'roles' => ['admin', 'accountant']],
                ['label' => 'دليل الحسابات', 'resource' => 'accounts', 'icon' => 'book', 'roles' => ['admin']],
                ['label' => 'مطالبات التأمين', 'resource' => 'insurance-claims', 'icon' => 'shield', 'roles' => ['admin', 'accountant']],
                ['label' => 'خطط التأمين', 'resource' => 'insurance-plans', 'icon' => 'shield', 'roles' => ['admin', 'accountant']],
                ['label' => 'أنواع المصروفات', 'resource' => 'expense-types', 'icon' => 'wallet', 'roles' => ['admin', 'accountant']],
                ['label' => 'القيود المحاسبية', 'resource' => 'journal-entries', 'icon' => 'ledger', 'roles' => ['admin', 'accountant']],
                ['label' => 'الحركات المالية', 'resource' => 'financial-transactions', 'icon' => 'activity', 'roles' => ['admin', 'accountant']],
            ],
        ],
        [
            'label' => 'الموارد البشرية',
            'links' => [
                ['label' => 'الموظفون', 'route' => 'pharmacy.employees.page', 'icon' => 'users', 'roles' => ['admin']],
                ['label' => 'سجلات الدوام', 'resource' => 'employee-work-logs', 'icon' => 'activity', 'roles' => ['admin', 'accountant']],
                ['label' => 'صرف الرواتب', 'route' => 'pharmacy.payroll.index', 'icon' => 'wallet', 'roles' => ['admin', 'accountant']],
            ],
        ],
        [
            'label' => 'إعدادات النظام',
            'links' => [
                ['label' => 'الفروع', 'resource' => 'branches', 'icon' => 'building', 'roles' => ['admin']],
                ['label' => 'طلبات التسجيل', 'route' => 'pharmacy.registration-requests.index', 'icon' => 'users', 'roles' => ['admin']],
            ],
        ],
    ];

    $iconPaths = [
        'home' => 'M3 10.5 12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6',
        'pos' => 'M4 5h16v14H4zM8 9h8M8 13h3M8 16h2M15 13h1M15 16h1',
        'pill' => 'M8 4a5 5 0 0 0 0 10h8a5 5 0 0 0 0-10H8ZM12 4v10M5 18h14',
        'box' => 'm12 3 8 4.5v9L12 21l-8-4.5v-9L12 3ZM4 7.5l8 4.5 8-4.5M12 12v9',
        'grid' => 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
        'ruler' => 'M4 16 16 4l4 4L8 20H4v-4ZM13 7l4 4M10 10l2 2M7 13l2 2',
        'layers' => 'm12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
        'receipt' => 'M6 3h12v18l-3-2-3 2-3-2-3 2V3ZM9 8h6M9 12h6M9 16h3',
        'cart' => 'M3 4h2l2.5 11h10L20 8H6M9 19a1 1 0 1 0 0 .01M17 19a1 1 0 1 0 0 .01',
        'wallet' => 'M4 6h15a2 2 0 0 1 2 2v10H5a2 2 0 0 1-2-2V6a3 3 0 0 1 3-3h12M16 12h5',
        'safe' => 'M4 4h16v16H4zM8 8h8v8H8zM12 11v2M7 2v2M17 2v2',
        'book' => 'M4 5a2 2 0 0 1 2-2h13v17H6a2 2 0 0 1-2-2V5ZM4 17a2 2 0 0 1 2-2h13M8 7h7M8 11h7',
        'shield' => 'M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11ZM9 12l2 2 4-4',
        'ledger' => 'M5 3h14v18H5zM8 7h8M8 11h8M8 15h4M3 7h2M3 12h2M3 17h2',
        'activity' => 'M3 12h4l3-8 4 16 3-8h4',
        'building' => 'M4 21V5l8-3 8 3v16M8 9h2M14 9h2M8 13h2M14 13h2M10 21v-4h4v4',
    ];
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0e2b3c">
        <title>{{ $title }} | {{ config('app.name', 'شفاء') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="pharmacy-shell">
            <button class="sidebar-backdrop" type="button" aria-label="إغلاق القائمة" data-sidebar-close></button>
            <aside class="pharmacy-sidebar" id="pharmacy-sidebar">
                <a class="brand-lockup" href="{{ route('dashboard') }}">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="brand-copy">
                        <strong>{{ config('app.name', 'شفاء') }}</strong>
                        <small>نظام إدارة الصيدلية</small>
                    </span>
                </a>

                <div class="sidebar-branch">
                    <span class="branch-pulse"></span>
                    <span>
                        <small>الموقع الحالي</small>
                        <strong>{{ auth()->user()->branch?->name ?? 'الإدارة المركزية' }}</strong>
                    </span>
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>

                <nav class="sidebar-nav" aria-label="التنقل الرئيسي">
                    @foreach ($groups as $group)
                        @php
                            $visibleLinks = collect($group['links'])->filter(fn ($link) => in_array($role, $link['roles'], true));
                        @endphp
                        @if ($visibleLinks->isNotEmpty())
                            <div class="nav-group">
                                <div class="nav-group-label">{{ $group['label'] }}</div>
                                @foreach ($visibleLinks as $link)
                                    @php
                                        $isActive = isset($link['route'])
                                            ? request()->routeIs($link['route'])
                                            : request()->routeIs('pharmacy.manage') && request()->route('resource') === $link['resource'];
                                        $href = isset($link['route'])
                                            ? route($link['route'])
                                            : route('pharmacy.manage', $link['resource']);
                                    @endphp
                                    <a class="sidebar-link {{ $isActive ? 'is-active' : '' }}" href="{{ $href }}" @if ($isActive) aria-current="page" @endif>
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="{{ $iconPaths[$link['icon']] }}" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span>{{ $link['label'] }}</span>
                                        @if ($isActive)<span class="nav-active-dot"></span>@endif
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </nav>

                <div class="sidebar-footer">
                    <div class="support-card">
                        <span class="support-icon">✦</span>
                        <div>
                            <strong>مساعدة سريعة</strong>
                            <small>النظام جاهز لمساعدتك</small>
                        </div>
                    </div>
                    <div class="sidebar-version">شفاء <span>•</span> إصدار النظام</div>
                </div>
            </aside>

            <div class="pharmacy-main">
                <header class="pharmacy-topbar">
                    <div class="topbar-leading">
                        <button class="icon-button mobile-menu-button" type="button" aria-label="فتح القائمة" aria-controls="pharmacy-sidebar" aria-expanded="false" data-sidebar-toggle>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </button>
                        <div class="topbar-title">
                            <span class="topbar-eyebrow">نظام شفاء للصيدليات</span>
                            <h1>{{ $title }}</h1>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <div class="topbar-date">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ now()->locale('ar')->translatedFormat('j F Y') }}</span>
                        </div>
                        <a class="topbar-quick-link" href="{{ route('profile') }}">حسابي</a>
                        <div class="user-menu">
                            <span class="user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="user-copy">
                                <strong>{{ auth()->user()->name }}</strong>
                                <small>{{ ['admin' => 'مدير النظام', 'pharmacist' => 'صيدلي', 'cashier' => 'كاشير', 'accountant' => 'محاسب', 'storekeeper' => 'أمين مخزن'][auth()->user()->role] ?? 'مستخدم' }}</small>
                            </span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="logout-button" type="submit" aria-label="تسجيل الخروج" title="تسجيل الخروج">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3m9-9h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="pharmacy-content">
                    {{ $slot }}
                </main>
                <footer class="pharmacy-footer">
                    <span>© {{ now()->year }} {{ config('app.name', 'شفاء') }}</span>
                    <span>صحة أفضل، بإدارة أدق</span>
                </footer>
            </div>
        </div>
    </body>
</html>
