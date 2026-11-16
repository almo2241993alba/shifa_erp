<x-pharmacy-layout :title="$title">
    <section class="page-intro">
        <div>
            <span class="eyebrow">{{ $isCashierReport ? __('Your sales') : __('Performance analysis') }}</span>
            <h2>{{ $title }}</h2>
            <p>{{ $isCashierReport ? __('Review your invoices and collections for the selected period.') : __('Sales and invoice summary for the selected period.') }}</p>
        </div>
    </section>

    <form class="report-filters surface-card" method="GET">
        <div>
            <label class="field-label" for="from_date">{{ __('From') }}</label>
            <input id="from_date" name="from_date" type="date" value="{{ $fromDate }}" required>
            @error('from_date')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div>
            <label class="field-label" for="to_date">{{ __('To') }}</label>
            <input id="to_date" name="to_date" type="date" value="{{ $toDate }}" required>
            @error('to_date')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <button class="button button-primary" type="submit">{{ __('Run report') }}</button>
    </form>

    <section class="stats-grid report-stats" aria-label="{{ __('Report summary') }}">
        <article class="stat-card stat-card-primary">
            <span class="stat-caption">{{ __('Invoices') }}</span>
            <strong class="stat-value">{{ number_format((int) $summary->invoice_count) }}</strong>
            <span class="stat-footnote">{{ __('For the selected period') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('Gross sales') }}</span>
            <strong class="stat-value">{{ number_format((float) $summary->gross_total, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('Value of recorded invoices') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('Collected') }}</span>
            <strong class="stat-value">{{ number_format((float) $summary->paid_total, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('Payments received for invoices') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('Returns') }}</span>
            <strong class="stat-value">{{ number_format((float) $returnTotal, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('Refund value during this period') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('Expenses') }}</span>
            <strong class="stat-value">{{ number_format((float) $expenseTotal, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('Expenses recorded during this period') }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-caption">{{ __('Cash transferred to safe') }}</span>
            <strong class="stat-value">{{ number_format((float) $sessionSummary->transferred_total, 2) }} <small>{{ $currency }}</small></strong>
            <span class="stat-footnote">{{ __('Sessions') }}: {{ number_format((int) $sessionSummary->session_count) }}</span>
        </article>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('Daily summary') }}</span><h3>{{ __('Sales by day') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Invoice count') }}</th><th>{{ __('Gross sales') }}</th><th>{{ __('Collections') }}</th></tr></thead>
                <tbody>
                    @forelse ($daily as $day)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($day->sales_date)->format('Y-m-d') }}</td>
                            <td>{{ number_format((int) $day->invoice_count) }}</td>
                            <td class="table-amount">{{ number_format((float) $day->gross_total, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $day->paid_total, 2) }} <small>{{ $currency }}</small></td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="4">{{ __('No sales in the selected period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('Payment methods') }}</span><h3>{{ __('Collections by method and network') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('Payment method') }}</th><th>{{ __('Treasury / network') }}</th><th>{{ __('Payments') }}</th><th>{{ __('Amount') }}</th></tr></thead>
                <tbody>
                    @forelse ($paymentBreakdown as $payment)
                        <tr>
                            <td>{{ __($payment->payment_method) }}</td>
                            <td>{{ $payment->treasury?->name ?? '—' }}</td>
                            <td>{{ number_format((int) $payment->payment_count) }}</td>
                            <td class="table-amount">{{ number_format((float) $payment->amount, 2) }} <small>{{ $currency }}</small></td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="4">{{ __('No payments in the selected period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('Invoice details') }}</span><h3>{{ __('Recorded sales') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('Invoice number') }}</th><th>{{ __('Customer') }}</th>@unless($isCashierReport)<th>{{ __('Cashier') }}</th>@endunless<th>{{ __('Status') }}</th><th>{{ __('Total') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Date') }}</th></tr></thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="table-primary-cell">{{ $sale->invoice_number }}</td>
                            <td>{{ $sale->customer?->name ?? __('Walk-in customer') }}</td>
                            @unless($isCashierReport)<td>{{ $sale->user?->name ?? '—' }}</td>@endunless
                            <td>{{ __(['completed' => 'Completed', 'returned' => 'Returned', 'partially_returned' => 'Partially returned'][$sale->status] ?? $sale->status) }}</td>
                            <td class="table-amount">{{ number_format((float) $sale->grand_total, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $sale->paid_amount, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="{{ $isCashierReport ? 6 : 7 }}">{{ __('No invoices in the selected period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $sales->links() }}</div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('Drawer settlements') }}</span><h3>{{ __('Cashier sessions and deposits') }}</h3></div>
            <span class="report-variance">{{ __('Net variance') }}: {{ number_format((float) $sessionSummary->variance_total, 2) }} {{ $currency }}</span>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('Cashier') }}</th><th>{{ __('Cash drawer') }}</th><th>{{ __('Expected cash') }}</th><th>{{ __('Counted cash') }}</th><th>{{ __('Difference') }}</th><th>{{ __('Transferred amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>{{ $session->user?->name ?? '—' }}</td>
                            <td>{{ $session->treasury?->name ?? '—' }}</td>
                            <td>{{ number_format((float) $session->expected_cash, 2) }}</td>
                            <td>{{ $session->actual_closing_cash === null ? '—' : number_format((float) $session->actual_closing_cash, 2) }}</td>
                            <td>{{ $session->status === 'closed' ? number_format((float) $session->difference, 2) : '—' }}</td>
                            <td>{{ number_format((float) $session->cash_transfer_amount, 2) }} <small>{{ $session->closingTreasury?->name }}</small></td>
                            <td>{{ __($session->status === 'open' ? 'Open' : 'Closed') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="7">{{ __('No sessions in the selected period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('Returns') }}</span><h3>{{ __('Sales returns') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('Return number') }}</th><th>{{ __('Invoice number') }}</th><th>{{ __('User') }}</th><th>{{ __('Refund amount') }}</th><th>{{ __('Date') }}</th></tr></thead>
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
                        <tr><td class="table-muted" colspan="5">{{ __('No returns in the selected period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('Expenses') }}</span><h3>{{ __('Recorded expenses') }}</h3></div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('Expense type') }}</th><th>{{ __('User') }}</th><th>{{ __('Treasury') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Date') }}</th></tr></thead>
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
                        <tr><td class="table-muted" colspan="5">{{ __('No expenses in the selected period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-pharmacy-layout>
