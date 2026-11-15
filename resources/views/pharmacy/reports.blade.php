<x-pharmacy-layout :title="$title">
    <section class="page-intro">
        <div>
            <span class="eyebrow">{{ $isCashierReport ? 'مبيعات حسابك' : 'تحليل الأداء' }}</span>
            <h2>{{ $title }}</h2>
            <p>{{ $isCashierReport ? 'راجع فواتيرك ومبالغ التحصيل خلال الفترة المحددة.' : 'ملخص مبيعات الفروع والفواتير خلال الفترة المحددة.' }}</p>
        </div>
    </section>

    <form class="report-filters surface-card" method="GET">
        <div>
            <label class="field-label" for="from_date">من تاريخ</label>
            <input id="from_date" name="from_date" type="date" value="{{ $fromDate }}" required>
            @error('from_date')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div>
            <label class="field-label" for="to_date">إلى تاريخ</label>
            <input id="to_date" name="to_date" type="date" value="{{ $toDate }}" required>
            @error('to_date')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <button class="button button-primary" type="submit">عرض التقرير</button>
    </form>

    <section class="stats-grid report-stats" aria-label="ملخص التقرير">
        <article class="stat-card stat-card-primary">
            <span class="stat-caption">إجمالي الفواتير</span>
            <strong class="stat-value">{{ number_format((int) $summary->invoice_count) }}</strong>
            <span class="stat-footnote">ضمن الفترة المحددة</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">إجمالي المبيعات</span>
            <strong class="stat-value">{{ number_format((float) $summary->gross_total, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">قيمة الفواتير المسجلة</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">إجمالي التحصيل</span>
            <strong class="stat-value">{{ number_format((float) $summary->paid_total, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">المبالغ المدفوعة على الفواتير</span>
        </article>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">ملخص يومي</span><h3>حركة المبيعات حسب اليوم</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>التاريخ</th><th>عدد الفواتير</th><th>إجمالي المبيعات</th><th>التحصيل</th></tr></thead>
                <tbody>
                    @forelse ($daily as $day)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($day->sales_date)->format('Y-m-d') }}</td>
                            <td>{{ number_format((int) $day->invoice_count) }}</td>
                            <td class="table-amount">{{ number_format((float) $day->gross_total, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $day->paid_total, 2) }} <small>{{ $currency }}</small></td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="4">لا توجد مبيعات في الفترة المحددة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">تفاصيل الفواتير</span><h3>المبيعات المسجلة</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>رقم الفاتورة</th><th>العميل</th>@unless($isCashierReport)<th>الكاشير</th>@endunless<th>الحالة</th><th>الإجمالي</th><th>المدفوع</th><th>التاريخ</th></tr></thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="table-primary-cell">{{ $sale->invoice_number }}</td>
                            <td>{{ $sale->customer?->name ?? 'عميل نقدي' }}</td>
                            @unless($isCashierReport)<td>{{ $sale->user?->name ?? '—' }}</td>@endunless
                            <td>{{ ['completed' => 'مكتملة', 'returned' => 'مرتجعة', 'partially_returned' => 'مرتجعة جزئياً'][$sale->status] ?? $sale->status }}</td>
                            <td class="table-amount">{{ number_format((float) $sale->grand_total, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $sale->paid_amount, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="{{ $isCashierReport ? 6 : 7 }}">لا توجد فواتير في الفترة المحددة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $sales->links() }}</div>
    </section>
</x-pharmacy-layout>
