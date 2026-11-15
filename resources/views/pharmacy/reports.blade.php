<x-pharmacy-layout :title="$title">
    <section class="page-intro">
        <div>
            <span class="eyebrow">{{ $isCashierReport ? __('مبيعات حسابك') : __('تحليل الأداء') }}</span>
            <h2>{{ $title }}</h2>
            <p>{{ $isCashierReport ? __('راجع فواتيرك ومبالغ التحصيل خلال الفترة المحددة.') : __('ملخص مبيعات الفروع والفواتير خلال الفترة المحددة.') }}</p>
        </div>
    </section>

    <form class="report-filters surface-card" method="GET">
        <div>
            <label class="field-label" for="from_date">{{ __('من تاريخ') }}</label>
            <input id="from_date" name="from_date" type="date" value="{{ $fromDate }}" required>
            @error('from_date')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div>
            <label class="field-label" for="to_date">{{ __('إلى تاريخ') }}</label>
            <input id="to_date" name="to_date" type="date" value="{{ $toDate }}" required>
            @error('to_date')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <button class="button button-primary" type="submit">{{ __('عرض التقرير') }}</button>
    </form>

    <section class="stats-grid report-stats" aria-label="ملخص التقرير">
        <article class="stat-card stat-card-primary">
            <span class="stat-caption">{{ __('إجمالي الفواتير') }}</span>
            <strong class="stat-value">{{ number_format((int) $summary->invoice_count) }}</strong>
            <span class="stat-footnote">{{ __('ضمن الفترة المحددة') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('إجمالي المبيعات') }}</span>
            <strong class="stat-value">{{ number_format((float) $summary->gross_total, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('قيمة الفواتير المسجلة') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('إجمالي التحصيل') }}</span>
            <strong class="stat-value">{{ number_format((float) $summary->paid_total, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('المبالغ المدفوعة على الفواتير') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('المرتجعات') }}</span>
            <strong class="stat-value">{{ number_format((float) $returnTotal, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('قيمة المبالغ المرتجعة خلال الفترة') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('المصروفات') }}</span>
            <strong class="stat-value">{{ number_format((float) $expenseTotal, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('المصروفات المسجلة خلال الفترة') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('المبالغ المرحلة للخزينة') }}</span>
            <strong class="stat-value">{{ number_format((float) $sessionSummary->transferred_total, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('عدد الجلسات') }}: {{ number_format((int) $sessionSummary->session_count) }}</span>
        </article>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('ملخص يومي') }}</span><h3>{{ __('حركة المبيعات حسب اليوم') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('التاريخ') }}</th><th>{{ __('عدد الفواتير') }}</th><th>{{ __('إجمالي المبيعات') }}</th><th>{{ __('التحصيل') }}</th></tr></thead>
                <tbody>
                    @forelse ($daily as $day)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($day->sales_date)->format('Y-m-d') }}</td>
                            <td>{{ number_format((int) $day->invoice_count) }}</td>
                            <td class="table-amount">{{ number_format((float) $day->gross_total, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $day->paid_total, 2) }} <small>{{ $currency }}</small></td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="4">{{ __('لا توجد مبيعات في الفترة المحددة.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('وسائل التحصيل') }}</span><h3>{{ __('التحصيل حسب الطريقة والشبكة') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('طريقة التحصيل') }}</th><th>{{ __('الخزينة / الشبكة') }}</th><th>{{ __('عدد الدفعات') }}</th><th>{{ __('المبلغ') }}</th></tr></thead>
                <tbody>
                    @forelse ($paymentBreakdown as $payment)
                        <tr>
                            <td>{{ __($payment->payment_method) }}</td>
                            <td>{{ $payment->treasury?->name ?? '—' }}</td>
                            <td>{{ number_format((int) $payment->payment_count) }}</td>
                            <td class="table-amount">{{ number_format((float) $payment->amount, 2) }} <small>{{ $currency }}</small></td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="4">{{ __('لا توجد دفعات في الفترة المحددة.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('تفاصيل الفواتير') }}</span><h3>{{ __('المبيعات المسجلة') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('رقم الفاتورة') }}</th><th>{{ __('العميل') }}</th>@unless($isCashierReport)<th>{{ __('الكاشير') }}</th>@endunless<th>{{ __('الحالة') }}</th><th>{{ __('الإجمالي') }}</th><th>{{ __('المدفوع') }}</th><th>{{ __('التاريخ') }}</th></tr></thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="table-primary-cell">{{ $sale->invoice_number }}</td>
                            <td>{{ $sale->customer?->name ?? __('عميل نقدي') }}</td>
                            @unless($isCashierReport)<td>{{ $sale->user?->name ?? '—' }}</td>@endunless
                            <td>{{ __(['completed' => 'مكتملة', 'returned' => 'مرتجعة', 'partially_returned' => 'مرتجعة جزئياً'][$sale->status] ?? $sale->status) }}</td>
                            <td class="table-amount">{{ number_format((float) $sale->grand_total, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $sale->paid_amount, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="{{ $isCashierReport ? 6 : 7 }}">{{ __('لا توجد فواتير في الفترة المحددة.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $sales->links() }}</div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('تسويات الدرج') }}</span><h3>{{ __('جلسات الكاشير والإيداعات') }}</h3></div>
            <span class="report-variance">{{ __('صافي الفروقات') }}: {{ number_format((float) $sessionSummary->variance_total, 2) }} {{ $currency }}</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('الكاشير') }}</th><th>{{ __('درج النقد') }}</th><th>{{ __('النقد المتوقع') }}</th><th>{{ __('النقد المعدود') }}</th><th>{{ __('الفرق') }}</th><th>{{ __('المبلغ المرحّل') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>{{ $session->user?->name ?? '—' }}</td>
                            <td>{{ $session->treasury?->name ?? '—' }}</td>
                            <td>{{ number_format((float) $session->expected_cash, 2) }}</td>
                            <td>{{ $session->actual_closing_cash === null ? '—' : number_format((float) $session->actual_closing_cash, 2) }}</td>
                            <td>{{ $session->status === 'closed' ? number_format((float) $session->difference, 2) : '—' }}</td>
                            <td>{{ number_format((float) $session->cash_transfer_amount, 2) }} <small>{{ $session->closingTreasury?->name }}</small></td>
                            <td>{{ __($session->status === 'open' ? 'مفتوحة' : 'مغلقة') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="7">{{ __('لا توجد جلسات في الفترة المحددة.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('المرتجعات') }}</span><h3>{{ __('مرتجعات المبيعات') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('رقم الإرجاع') }}</th><th>{{ __('رقم الفاتورة') }}</th><th>{{ __('المستخدم') }}</th><th>{{ __('المبلغ المسترد') }}</th><th>{{ __('التاريخ') }}</th></tr></thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td class="table-primary-cell">{{ $return->return_number }}</td>
                            <td>{{ $return->sale?->invoice_number ?? '—' }}</td>
                            <td>{{ $return->user?->name ?? '—' }}</td>
                            <td>{{ number_format((float) $return->total_refund_amount, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ $return->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="5">{{ __('لا توجد مرتجعات في الفترة المحددة.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('المصروفات') }}</span><h3>{{ __('المصروفات المسجلة') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('نوع المصروف') }}</th><th>{{ __('المستخدم') }}</th><th>{{ __('الخزينة') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('التاريخ') }}</th></tr></thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expenseType?->type_name ?? '—' }}</td>
                            <td>{{ $expense->user?->name ?? '—' }}</td>
                            <td>{{ $expense->treasury?->name ?? '—' }}</td>
                            <td>{{ number_format((float) $expense->amount + (float) $expense->amount_tax, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="5">{{ __('لا توجد مصروفات في الفترة المحددة.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-pharmacy-layout>
