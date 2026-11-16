@props(['title' => __('Management dashboard')])

@php
    $appName = config('app.name') === 'شفاء' ? __('Shifa') : config('app.name');
    $role = auth()->user()->role;
    $groups = [
        [
            'label' => __('Workspace'),
            'links' => [
                ['label' => __('Home'), 'route' => 'dashboard', 'icon' => 'home', 'roles' => ['admin', 'pharmacist', 'accountant', 'storekeeper']],
                ['label' => __('Point of sale'), 'route' => 'pharmacy.cashier', 'icon' => 'pos', 'roles' => ['admin', 'pharmacist', 'cashier']],
                ['label' => __('Cashier reports'), 'route' => 'pharmacy.cashier.reports', 'icon' => 'receipt', 'roles' => ['admin', 'cashier']],
                ['label' => __('Reports'), 'route' => 'pharmacy.reports.index', 'icon' => 'activity', 'roles' => ['admin', 'pharmacist', 'accountant']],
            ],
        ],
        [
            'label' => __('Pharmacy management'),
            'links' => [
                ['label' => __('Medicines and inventory'), 'resource' => 'medicines', 'icon' => 'pill', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => __('Inventory batches'), 'resource' => 'batches', 'icon' => 'box', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => __('Categories'), 'resource' => 'categories', 'icon' => 'grid', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => __('Units'), 'resource' => 'units', 'icon' => 'ruler', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => __('Dosage forms'), 'resource' => 'dosage-forms', 'icon' => 'layers', 'roles' => ['admin', 'pharmacist', 'storekeeper']],
                ['label' => __('Customers and suppliers'), 'resource' => 'contacts', 'icon' => 'users', 'roles' => ['admin', 'pharmacist', 'accountant', 'cashier']],
                ['label' => __('Insurance policies'), 'resource' => 'insurance-policies', 'icon' => 'shield', 'roles' => ['admin', 'pharmacist', 'accountant']],
            ],
        ],
        [
            'label' => __('Transactions'),
            'links' => [
                ['label' => __('Sales'), 'resource' => 'sales', 'icon' => 'receipt', 'roles' => ['admin', 'pharmacist', 'cashier']],
                ['label' => __('Purchases'), 'resource' => 'purchases', 'icon' => 'cart', 'roles' => ['admin', 'storekeeper', 'accountant']],
                ['label' => __('Expenses'), 'resource' => 'expenses', 'icon' => 'wallet', 'roles' => ['admin', 'accountant', 'cashier']],
                ['label' => __('Sales returns'), 'resource' => 'sale-returns', 'icon' => 'receipt', 'roles' => ['admin', 'pharmacist', 'cashier']],
                ['label' => __('Purchase returns'), 'resource' => 'purchase-returns', 'icon' => 'cart', 'roles' => ['admin', 'storekeeper', 'accountant']],
                ['label' => __('Cashier sessions'), 'resource' => 'pos-sessions', 'icon' => 'pos', 'roles' => ['admin', 'pharmacist', 'cashier']],
            ],
        ],
        [
            'label' => __('Finance and insurance'),
            'links' => [
                ['label' => __('Treasuries'), 'resource' => 'treasuries', 'icon' => 'safe', 'roles' => ['admin', 'accountant']],
                ['label' => __('Chart of accounts'), 'resource' => 'accounts', 'icon' => 'book', 'roles' => ['admin']],
                ['label' => __('Insurance claims'), 'resource' => 'insurance-claims', 'icon' => 'shield', 'roles' => ['admin', 'accountant']],
                ['label' => __('Insurance plans'), 'resource' => 'insurance-plans', 'icon' => 'shield', 'roles' => ['admin', 'accountant']],
                ['label' => __('Expense types'), 'resource' => 'expense-types', 'icon' => 'wallet', 'roles' => ['admin', 'accountant']],
                ['label' => __('Journal entries'), 'resource' => 'journal-entries', 'icon' => 'ledger', 'roles' => ['admin', 'accountant']],
                ['label' => __('Financial transactions'), 'resource' => 'financial-transactions', 'icon' => 'activity', 'roles' => ['admin', 'accountant']],
            ],
        ],
        [
            'label' => __('Human resources'),
            'links' => [
                ['label' => __('Employees'), 'route' => 'pharmacy.employees.page', 'icon' => 'users', 'roles' => ['admin']],
                ['label' => __('Work logs'), 'resource' => 'employee-work-logs', 'icon' => 'activity', 'roles' => ['admin', 'accountant']],
                ['label' => __('Payroll'), 'route' => 'pharmacy.payroll.index', 'icon' => 'wallet', 'roles' => ['admin', 'accountant']],
            ],
        ],
        [
            'label' => __('System settings'),
            'links' => [
                ['label' => __('Branches'), 'resource' => 'branches', 'icon' => 'building', 'roles' => ['admin']],
                ['label' => __('Registration requests'), 'route' => 'pharmacy.registration-requests.index', 'icon' => 'users', 'roles' => ['admin']],
            ],
        ],
    ];
    $roleLabels = [
        'admin' => __('Administrator'),
        'pharmacist' => __('Pharmacist'),
        'cashier' => __('Cashier'),
        'accountant' => __('Accountant'),
        'storekeeper' => __('Storekeeper'),
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
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0e2b3c">
        <title>{{ __($title) }} | {{ $appName }}</title>
        @php
            $pharmacyI18n = [
                'locale' => app()->getLocale(),
                'collapseSidebar' => __('Collapse sidebar'),
                'expandSidebar' => __('Expand sidebar'),
                'cashNetworkRequired' => __('Select a network or settlement account first.'),
                'requestFailed' => __('Could not complete the request. Check the data and try again.'),
                'genericRequestFailed' => __('Could not complete the request. Try again.'),
                'searchingProducts' => __('Searching products...'),
                'productCount' => __('items'),
                'noMatchingProducts' => __('No matching products'),
                'tryAnotherSearch' => __('Try another search or scan a barcode.'),
                'outOfStock' => __('Out of stock'),
                'available' => __('Available'),
                'pharmacyItem' => __('Pharmacy item'),
                'priceNotSet' => __('Price not set'),
                'addItem' => __('Add'),
                'unitNotConfigured' => __('No selling unit is configured for this item.'),
                'itemAdded' => __('Item added to the invoice.'),
                'emptyCart' => __('The cart is empty'),
                'addItemToStart' => __('Add an item to start building the invoice.'),
                'removeItem' => __('Remove item'),
                'saleUnit' => __('Selling unit'),
                'decreaseQuantity' => __('Decrease quantity'),
                'increaseQuantity' => __('Increase quantity'),
                'quantity' => __('Quantity'),
                'sessionOpened' => __('Cashier session opened.'),
                'sessionClosed' => __('Session closed and shift settled.'),
                'completingSale' => __('Completing sale...'),
                'completeSale' => __('Confirm and complete sale'),
                'saleCompleted' => __('Invoice completed'),
                'loadingRecords' => __('Loading records...'),
                'loadingData' => __('Loading data...'),
                'records' => __('records'),
                'page' => __('Page'),
                'of' => __('of'),
                'actions' => __('Actions'),
                'yes' => __('Yes'),
                'no' => __('No'),
                'active' => __('Active'),
                'inactive' => __('Inactive'),
                'completed' => __('Completed'),
                'pending' => __('Under review'),
                'approved' => __('Approved'),
                'rejected' => __('Rejected'),
                'paid' => __('Paid'),
                'partial' => __('Partially paid'),
                'unpaid' => __('Unpaid'),
                'received' => __('Received'),
                'open' => __('Open'),
                'closed' => __('Closed'),
                'noRecords' => __('No matching records'),
                'changeSearchOrAdd' => __('Try a different search or add a new record.'),
                'selectValue' => __('Select a value'),
                'previous' => __('Previous'),
                'next' => __('Next'),
                'edit' => __('Edit'),
                'delete' => __('Delete'),
                'editRecord' => __('Edit record'),
                'newRecord' => __('Add new record'),
                'recordDetails' => __('Record details'),
                'addRecord' => __('Add record'),
                'saveChanges' => __('Save changes'),
                'couldNotLoad' => __('Could not load data'),
                'deleteConfirm' => __('Delete this record? This action cannot be undone.'),
                'deleted' => __('Record deleted.'),
                'saving' => __('Saving...'),
                'updated' => __('Record updated.'),
                'created' => __('Record added.'),
                'typeCashDrawer' => __('Cash drawer'),
                'typeMainSafe' => __('Main safe'),
                'typeBank' => __('Bank or network'),
            ];
        @endphp
        <script>
            window.pharmacyI18n = @json($pharmacyI18n);
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="pharmacy-shell">
            <button class="sidebar-backdrop" type="button" aria-label="{{ __('Close navigation') }}" data-sidebar-close></button>
            <aside class="pharmacy-sidebar" id="pharmacy-sidebar">
                <a class="brand-lockup" href="{{ route('dashboard') }}">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M12 4h8v8h8v8h-8v8h-8v-8H4v-8h8V4Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="brand-copy">
                        <strong>{{ $appName }}</strong>
                        <small>{{ __('Pharmacy management system') }}</small>
                    </span>
                </a>

                <div class="sidebar-branch">
                    <span class="branch-pulse"></span>
                    <span class="branch-copy">
                        <small>{{ __('Current location') }}</small>
                        <strong>{{ auth()->user()->branch?->name ?? __('Head office') }}</strong>
                    </span>
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>

                <nav class="sidebar-nav" aria-label="{{ __('Main navigation') }}">
                    @foreach ($groups as $group)
                        @php
                            $visibleLinks = collect($group['links'])->filter(fn ($link) => in_array($role, $link['roles'], true));
                            $groupIsActive = $visibleLinks->contains(fn ($link) => isset($link['route'])
                                ? request()->routeIs($link['route'])
                                : request()->routeIs('pharmacy.manage') && request()->route('resource') === $link['resource']);
                        @endphp
                        @if ($visibleLinks->isNotEmpty())
                            <div class="nav-group {{ $groupIsActive ? 'is-open' : '' }}" data-sidebar-group>
                                <button class="nav-group-toggle" type="button" data-sidebar-group-toggle aria-expanded="{{ $groupIsActive ? 'true' : 'false' }}" aria-controls="sidebar-group-{{ $loop->index }}" title="{{ $group['label'] }}">
                                    <span class="nav-group-label">{{ $group['label'] }}</span>
                                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <div class="nav-group-links" id="sidebar-group-{{ $loop->index }}">
                                @foreach ($visibleLinks as $link)
                                    @php
                                        $isActive = isset($link['route'])
                                            ? request()->routeIs($link['route'])
                                            : request()->routeIs('pharmacy.manage') && request()->route('resource') === $link['resource'];
                                        $href = isset($link['route'])
                                            ? route($link['route'])
                                            : route('pharmacy.manage', $link['resource']);
                                    @endphp
                                    <a class="sidebar-link {{ $isActive ? 'is-active' : '' }}" href="{{ $href }}" title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}" @if ($isActive) aria-current="page" @endif>
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="{{ $iconPaths[$link['icon']] }}" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span>{{ $link['label'] }}</span>
                                        @if ($isActive)<span class="nav-active-dot"></span>@endif
                                    </a>
                                @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </nav>

                <div class="sidebar-footer">
                    <div class="support-card">
                        <span class="support-icon">✦</span>
                        <div>
                            <strong>{{ __('Quick help') }}</strong>
                            <small>{{ __('The system is ready to help') }}</small>
                        </div>
                    </div>
                    <div class="sidebar-version">{{ __('Shifa') }} <span>•</span> {{ __('System version') }}</div>
                </div>
            </aside>

            <div class="pharmacy-main">
                <header class="pharmacy-topbar">
                    <div class="topbar-leading">
                        <button class="icon-button mobile-menu-button" type="button" aria-label="{{ __('Open navigation') }}" aria-controls="pharmacy-sidebar" aria-expanded="false" data-sidebar-toggle>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </button>
                        <button class="icon-button sidebar-collapse-button" type="button" aria-label="{{ __('Collapse sidebar') }}" aria-controls="pharmacy-sidebar" aria-pressed="false" data-sidebar-collapse-toggle title="{{ __('Collapse sidebar') }}">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h16M9 5v14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        </button>
                        <div class="topbar-title">
                            <span class="topbar-eyebrow">{{ __('Shifa Pharmacy System') }}</span>
                            <h1>{{ __($title) }}</h1>
                        </div>
                    </div>
                    <div class="topbar-actions">
                        <div class="topbar-date">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ now()->locale(app()->getLocale())->translatedFormat('j F Y') }}</span>
                        </div>
                        <form class="locale-switch" method="POST" action="{{ route('locale.switch') }}">
                            @csrf
                            <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
                            <label class="sr-only" for="locale-switch-select">{{ __('Language') }}</label>
                            <select id="locale-switch-select" name="locale" aria-label="{{ __('Language') }}" onchange="this.form.submit()">
                                <option value="ar" @selected(app()->getLocale() === 'ar')>{{ __('Arabic') }}</option>
                                <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                            </select>
                        </form>
                        <div class="user-menu" data-user-menu>
                            <button class="user-menu-toggle" type="button" data-user-menu-toggle aria-expanded="false" aria-haspopup="true">
                                <span class="user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                                <span class="user-copy">
                                    <strong>{{ auth()->user()->name }}</strong>
                                    <small>{{ $roleLabels[auth()->user()->role] ?? __('User') }}</small>
                                </span>
                                <svg class="user-menu-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <div class="user-menu-panel" data-user-menu-panel hidden>
                                <a href="{{ route('profile') }}">{{ __('My profile') }}</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit">{{ __('Sign out') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="pharmacy-content">
                    {{ $slot }}
                </main>
                <footer class="pharmacy-footer">
                    <span>© {{ now()->year }} {{ $appName }}</span>
                    <span>{{ __('Better health, better management') }}</span>
                </footer>
            </div>
        </div>
    </body>
</html>
