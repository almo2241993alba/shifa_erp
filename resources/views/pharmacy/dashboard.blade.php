<x-pharmacy-layout title="لوحة الإدارة">
    <section class="welcome-banner">
        <div class="welcome-copy">
            <span class="eyebrow eyebrow-light">لوحة التحكم</span>
            <h2>مرحبًا، {{ auth()->user()->name }}</h2>
            <p>إليك ملخص أداء الصيدلية وحركة العمل لهذا اليوم.</p>
        </div>
        <div class="welcome-meta">
            <span class="welcome-date">{{ $todayLabel }}</span>
            <a class="button button-light" href="{{ route('pharmacy.cashier') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h3M8 16h2M15 13h1M15 16h1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                فتح نقطة البيع
            </a>
        </div>
        <span class="banner-orbit banner-orbit-one"></span>
        <span class="banner-orbit banner-orbit-two"></span>
    </section>

    <div class="section-heading dashboard-section-heading">
        <div>
            <span class="eyebrow">نظرة عامة</span>
            <h2>ملخص اليوم</h2>
        </div>
        <span class="live-indicator"><i></i> تحديث مباشر من قاعدة البيانات</span>
    </div>

    <section class="stats-grid" aria-label="ملخص مؤشرات اليوم">
        <article class="stat-card stat-card-primary">
            <div class="stat-card-top">
                <span class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="stat-caption">إجمالي المبيعات</span>
            </div>
            <strong class="stat-value">{{ number_format($todaySales, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">قيمة الفواتير المسجلة اليوم</span>
        </article>

        <article class="stat-card">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon-violet">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 5h14v15H5zM8 9h8M8 13h5M8 16h3M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="stat-caption">عدد الفواتير</span>
            </div>
            <strong class="stat-value">{{ number_format($todaySalesCount) }}</strong>
            <span class="stat-footnote">فاتورة بيع مكتملة اليوم</span>
        </article>

        <article class="stat-card">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon-amber">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 8h16l-1 12H5L4 8ZM8 8a4 4 0 0 1 8 0M9 12v4M15 12v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="stat-caption">المشتريات اليوم</span>
            </div>
            <strong class="stat-value">{{ number_format($todayPurchases, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">إجمالي فواتير التوريد</span>
        </article>

        <article class="stat-card">
            <div class="stat-card-top">
                <span class="stat-icon stat-icon-rose">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v18M17 7.5c0-1.4-1.8-2.5-4-2.5S9 6.1 9 7.5 10.8 10 13 10s4 1.1 4 2.5-1.8 2.5-4 2.5-4-1.1-4-2.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                </span>
                <span class="stat-caption">المصروفات اليوم</span>
            </div>
            <strong class="stat-value">{{ number_format($todayExpenses, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">مصروفات مسجلة خلال اليوم</span>
        </article>
    </section>

    <section class="dashboard-grid">
        <article class="surface-card sales-chart-card">
            <div class="surface-heading">
                <div>
                    <span class="eyebrow">الأداء</span>
                    <h3>حركة المبيعات</h3>
                    <p>آخر سبعة أيام</p>
                </div>
                <a class="text-link" href="{{ route('pharmacy.manage', 'sales') }}">كل المبيعات <span aria-hidden="true">←</span></a>
            </div>

            <div class="chart-summary">
                <strong>{{ number_format($chart->sum('total'), 2) }} <small>{{ $currency }}</small></strong>
                <span>إجمالي الفترة</span>
            </div>

            <div class="sales-chart" role="img" aria-label="رسم بياني لمبيعات آخر سبعة أيام">
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
                    <span class="eyebrow">المخزون</span>
                    <h3>تنبيهات المخزون</h3>
                    <p>أصناف عند حد إعادة الطلب أو أقل</p>
                </div>
                <span class="heading-counter">{{ $lowStock->count() }}</span>
            </div>

            <div class="inventory-summary">
                <span class="inventory-summary-icon">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div><strong>{{ number_format($availableStock) }}</strong><small>وحدة أساسية متاحة</small></div>
                <div class="inventory-summary-total"><strong>{{ number_format($medicineCount) }}</strong><small>صنف نشط</small></div>
            </div>

            <div class="low-stock-list">
                @forelse ($lowStock as $medicine)
                    <div class="low-stock-row">
                        <span class="medicine-monogram">{{ mb_substr($medicine->trade_name, 0, 1) }}</span>
                        <div class="low-stock-name">
                            <strong>{{ $medicine->trade_name }}</strong>
                            <small>حد التنبيه: {{ number_format($medicine->min_stock_alert) }}</small>
                        </div>
                        <span class="stock-quantity">{{ number_format($medicine->available_stock) }}</span>
                    </div>
                @empty
                    <div class="empty-state compact-empty">
                        <span class="empty-check">✓</span>
                        <strong>المخزون بحالة جيدة</strong>
                        <small>لا توجد أصناف عند حد التنبيه حاليًا.</small>
                    </div>
                @endforelse
            </div>
            <a class="card-bottom-link" href="{{ route('pharmacy.manage', 'batches') }}">مراجعة دفعات المخزون <span aria-hidden="true">←</span></a>
        </article>
    </section>

    <section class="dashboard-bottom-grid">
        <article class="surface-card recent-sales-card">
            <div class="surface-heading">
                <div>
                    <span class="eyebrow">آخر النشاطات</span>
                    <h3>أحدث فواتير البيع</h3>
                </div>
                <a class="text-link" href="{{ route('pharmacy.manage', 'sales') }}">عرض الكل <span aria-hidden="true">←</span></a>
            </div>

            @if ($recentSales->isNotEmpty())
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>العميل</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th>الوقت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentSales as $sale)
                                <tr>
                                    <td class="table-primary-cell">{{ $sale->invoice_number }}</td>
                                    <td>{{ $sale->customer?->name ?? 'عميل نقدي' }}</td>
                                    <td class="table-amount">{{ number_format((float) $sale->grand_total, 2) }} <small>{{ $currency }}</small></td>
                                    <td><span class="status-badge status-success">مكتملة</span></td>
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
                    <strong>لا توجد فواتير بعد</strong>
                    <small>ستظهر أحدث المبيعات هنا فور تسجيلها.</small>
                    <a class="button button-secondary" href="{{ route('pharmacy.cashier') }}">ابدأ عملية بيع</a>
                </div>
            @endif
        </article>

        <aside class="surface-card quick-actions-card">
            <div class="surface-heading">
                <div>
                    <span class="eyebrow">إنجاز سريع</span>
                    <h3>اختصارات العمل</h3>
                </div>
            </div>
            <div class="quick-actions-list">
                <a class="quick-action" href="{{ route('pharmacy.cashier') }}">
                    <span class="quick-action-icon quick-action-teal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h3M8 16h2M15 13h1M15 16h1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span><strong>عملية بيع جديدة</strong><small>فتح شاشة الكاشير</small></span><b>←</b>
                </a>
                <a class="quick-action" href="{{ route('pharmacy.manage', 'medicines') }}">
                    <span class="quick-action-icon quick-action-blue"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4a5 5 0 0 0 0 10h8a5 5 0 0 0 0-10H8ZM12 4v10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <span><strong>إدارة الأدوية</strong><small>الأصناف والأسعار</small></span><b>←</b>
                </a>
                <a class="quick-action" href="{{ route('pharmacy.manage', 'contacts') }}">
                    <span class="quick-action-icon quick-action-violet"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span>
                    <span><strong>دليل العملاء</strong><small>العملاء والموردون</small></span><b>←</b>
                </a>
                @if ($pendingClaims !== null)
                    <a class="quick-action" href="{{ route('pharmacy.manage', 'insurance-claims') }}">
                        <span class="quick-action-icon quick-action-amber"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11ZM9 12l2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span><strong>مطالبات قيد المراجعة</strong><small>{{ number_format($pendingClaims) }} مطالبة معلقة</small></span><b>←</b>
                    </a>
                @endif
            </div>
            <div class="dashboard-note">
                <span>i</span>
                <p>تُحدّث المؤشرات تلقائيًا من سجل العمليات في النظام.</p>
            </div>
        </aside>
    </section>
</x-pharmacy-layout>
