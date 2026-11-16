<x-pharmacy-layout :title="__('Management dashboard')">
    <section class="welcome-banner">
        <div class="welcome-copy">
            <span class="eyebrow eyebrow-light">{{ __('Dashboard') }}</span>
            <h2>{{ __('Welcome,') }} {{ auth()->user()->name }}</h2>
            <p>{{ __("Here is today's overview of pharmacy performance and activity.") }}</p>
        </div>
        <div class="welcome-meta">
            <span class="welcome-date">{{ $todayLabel }}</span>
            <a class="button button-light" href="{{ route('pharmacy.cashier') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h3M8 16h2M15 13h1M15 16h1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Open point of sale') }}
            </a>
        </div>
        <span class="banner-orbit banner-orbit-one"></span>
        <span class="banner-orbit banner-orbit-two"></span>
    </section>

    <div class="section-heading dashboard-section-heading">
        <div>
            <span class="eyebrow">{{ __('Overview') }}</span>
            <h2>{{ __("Today's summary") }}</h2>
        </div>
        <span class="live-indicator"><i></i> {{ __('Live database updates') }}</span>
    </div>

    <section class="stats-grid" aria-label="{{ __("Today's key metrics") }}">
        <article class="stat-card stat-card-primary">
            <div class="stat-card-top">
                <span class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="stat-caption">{{ __('Gross sales') }}</span>
            </div>
            <strong class="stat-value">{{ number_format($todaySales, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('Value of invoices recorded today') }}</span>
        </article>

        <article class="stat-card">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon-violet">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5h14v15H5zM8 9h8M8 13h5M8 16h3M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="stat-caption">{{ __('Invoice count') }}</span>
            </div>
            <strong class="stat-value">{{ number_format($todaySalesCount) }}</strong>
            <span class="stat-footnote">{{ __('Completed sales invoices today') }}</span>
        </article>

        <article class="stat-card">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon-amber">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 8h16l-1 12H5L4 8ZM8 8a4 4 0 0 1 8 0M9 12v4M15 12v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="stat-caption">{{ __('Purchases today') }}</span>
            </div>
            <strong class="stat-value">{{ number_format($todayPurchases, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('Total purchase invoices') }}</span>
        </article>

        <article class="stat-card">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon-rose">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18M17 7.5c0-1.4-1.8-2.5-4-2.5S9 6.1 9 7.5 10.8 10 13 10s4 1.1 4 2.5-1.8 2.5-4 2.5-4-1.1-4-2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                </span>
                <span class="stat-caption">{{ __('Expenses today') }}</span>
            </div>
            <strong class="stat-value">{{ number_format($todayExpenses, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('Expenses recorded today') }}</span>
        </article>
    </section>

    <section class="dashboard-grid">
        <article class="surface-card sales-chart-card">
            <div class="surface-heading">
                <div>
                    <span class="eyebrow">{{ __('Performance') }}</span>
                    <h3>{{ __('Sales activity') }}</h3>
                    <p>{{ __('Last seven days') }}</p>
                </div>
                <a class="text-link" href="{{ route('pharmacy.manage', 'sales') }}">{{ __('All sales') }} <span aria-hidden="true">←</span></a>
            </div>

            <div class="chart-summary">
                <strong>{{ number_format($chart->sum('total'), 2) }} <small>{{ $currency }}</small></strong>
                <span>{{ __('Period total') }}</span>
            </div>

            <div class="sales-chart" role="img" aria-label="{{ __('Sales chart for the last seven days') }}">
                @foreach ($chart as $day)
                    @php($height = $day['total'] > 0 ? max(8, ($day['total'] / $chartMax) * 100) : 4)
                    <div class="chart-column" title="{{ $day['date'] }}: {{ number_format($day['total'], 2) }} {{ $currency }}">
                        <div class="chart-bar {{ $loop->last ? 'chart-bar-today' : '' }}" style="height: {{ $height }}%"></div>
                        <span>{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="surface-card inventory-card">
            <div class="surface-heading">
                <div>
                    <span class="eyebrow">{{ __('Inventory') }}</span>
                    <h3>{{ __('Inventory alerts') }}</h3>
                    <p>{{ __('Items at or below reorder level') }}</p>
                </div>
                <span class="heading-counter">{{ $lowStock->count() }}</span>
            </div>

            <div class="inventory-summary">
                <span class="inventory-summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div><strong>{{ number_format($availableStock) }}</strong><small>{{ __('Base units available') }}</small></div>
                <div class="inventory-summary-total"><strong>{{ number_format($medicineCount) }}</strong><small>{{ __('Active items') }}</small></div>
            </div>

            <div class="low-stock-list">
                @forelse ($lowStock as $medicine)
                    <div class="low-stock-row">
                        <span class="medicine-monogram">{{ mb_substr($medicine->trade_name, 0, 1) }}</span>
                        <div class="low-stock-name">
                            <strong>{{ $medicine->trade_name }}</strong>
                            <small>{{ __('Alert threshold:') }} {{ number_format($medicine->min_stock_alert) }}</small>
                        </div>
                        <span class="stock-quantity">{{ number_format($medicine->available_stock) }}</span>
                    </div>
                @empty
                    <div class="empty-state compact-empty">
                        <span class="empty-check">✓</span>
                        <strong>{{ __('Inventory is healthy') }}</strong>
                        <small>{{ __('No items are currently at the alert threshold.') }}</small>
                    </div>
                @endforelse
            </div>
            <a class="card-bottom-link" href="{{ route('pharmacy.manage', 'batches') }}">{{ __('Review inventory batches') }} <span aria-hidden="true">←</span></a>
        </article>
    </section>

    <section class="dashboard-bottom-grid">
        <article class="surface-card recent-sales-card">
            <div class="surface-heading">
                <div>
                    <span class="eyebrow">{{ __('Recent activity') }}</span>
                    <h3>{{ __('Latest sales invoices') }}</h3>
                </div>
                <a class="text-link" href="{{ route('pharmacy.manage', 'sales') }}">{{ __('View all') }} <span aria-hidden="true">←</span></a>
            </div>

            @if ($recentSales->isNotEmpty())
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Invoice number') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentSales as $sale)
                                <tr>
                                    <td class="table-primary-cell">{{ $sale->invoice_number }}</td>
                                    <td>{{ $sale->customer?->name ?? __('Walk-in customer') }}</td>
                                    <td class="table-amount">{{ number_format((float) $sale->grand_total, 2) }} <small>{{ $currency }}</small></td>
                                    <td><span class="status-badge status-success">{{ __('Completed') }}</span></td>
                                    <td class="table-muted">{{ $sale->created_at->format('H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <span class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h14v17l-3-2-4 2-4-2-3 2V4ZM8 9h8M8 13h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <strong>{{ __('No invoices yet') }}</strong>
                    <small>{{ __('Recent sales will appear here as soon as they are recorded.') }}</small>
                    <a class="button button-secondary" href="{{ route('pharmacy.cashier') }}">{{ __('Start a sale') }}</a>
                </div>
            @endif
        </article>

        <aside class="surface-card quick-actions-card">
            <div class="surface-heading">
                <div>
                    <span class="eyebrow">{{ __('Quick actions') }}</span>
                    <h3>{{ __('Shortcuts') }}</h3>
                </div>
            </div>
            <div class="quick-actions-list">
                <a class="quick-action" href="{{ route('pharmacy.cashier') }}">
                    <span class="quick-action-icon quick-action-teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h3M8 16h2M15 13h1M15 16h1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span><strong>{{ __('New sale') }}</strong><small>{{ __('Open cashier') }}</small></span><b>←</b>
                </a>
                <a class="quick-action" href="{{ route('pharmacy.manage', 'medicines') }}">
                    <span class="quick-action-icon quick-action-blue"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4a5 5 0 0 0 0 10h8a5 5 0 0 0 0-10H8ZM12 4v10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <span><strong>{{ __('Manage medicines') }}</strong><small>{{ __('Items and prices') }}</small></span><b>←</b>
                </a>
                <a class="quick-action" href="{{ route('pharmacy.manage', 'contacts') }}">
                    <span class="quick-action-icon quick-action-violet"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <span><strong>{{ __('Customer directory') }}</strong><small>{{ __('Customers and suppliers') }}</small></span><b>←</b>
                </a>
                @if ($pendingClaims !== null)
                    <a class="quick-action" href="{{ route('pharmacy.manage', 'insurance-claims') }}">
                        <span class="quick-action-icon quick-action-amber"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11ZM9 12l2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span><strong>{{ __('Claims under review') }}</strong><small>{{ number_format($pendingClaims) }} {{ __('pending claims') }}</small></span><b>←</b>
                    </a>
                @endif
            </div>
            <div class="dashboard-note">
                <span>i</span>
                <p>{{ __('Metrics update automatically from system activity.') }}</p>
            </div>
        </aside>
    </section>
</x-pharmacy-layout>
