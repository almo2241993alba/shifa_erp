<x-pharmacy-layout :title="__('Payroll')">
    <section class="page-intro">
        <div><span class="eyebrow">{{ __('Human resources') }}</span><h2>{{ __('Payroll') }}</h2><p>{{ __('Create a monthly payroll slip; entitlements and the journal entry are calculated by the payroll service.') }}</p></div>
    </section>

    @if (session('status'))
        <div class="form-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="form-error-summary" role="alert">{{ __('Could not create the payroll slip. Review the errors and treasury balances, then try again.') }}</div>
    @endif

    <section class="surface-card form-card">
        <div class="surface-heading"><div><span class="eyebrow">{{ __('New payroll slip') }}</span><h3>{{ __('Payment details') }}</h3></div></div>
        <form method="POST" action="{{ route('pharmacy.payroll.store') }}" class="payroll-form">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label class="field-label" for="payroll-employee">{{ __('Employee') }}</label>
                    <select id="payroll-employee" name="employee_id" required>
                        <option value="">{{ __('Select employee') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id') === $employee->id)>{{ $employee->contact->name }} — {{ $employee->branch->name }}</option>
                        @endforeach
                    </select>
                    @error('employee_id')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="year-month">{{ __('Payroll month') }}</label>
                    <input id="year-month" name="year_month_key" type="month" value="{{ old('year_month_key', now()->format('Y-m')) }}" required>
                    @error('year_month_key')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="paid-amount">{{ __('Amount paid') }}</label>
                    <input id="paid-amount" name="actual_paid_amount" type="number" min="0" step="0.01" value="{{ old('actual_paid_amount', 0) }}">
                    @error('actual_paid_amount')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="bonus">{{ __('Bonus') }}</label>
                    <input id="bonus" name="amount_bonus" type="number" min="0" step="0.01" value="{{ old('amount_bonus', 0) }}">
                    @error('amount_bonus')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="deductions">{{ __('Deductions and fines') }}</label>
                    <input id="deductions" name="total_deductions_fines" type="number" min="0" step="0.01" value="{{ old('total_deductions_fines', 0) }}">
                    @error('total_deductions_fines')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="payment-method">{{ __('Payment method') }}</label>
                    <select id="payment-method" name="payment_method">
                        <option value="">{{ __('Do not pay now') }}</option>
                        <option value="cash" @selected(old('payment_method') === 'cash')>{{ __('Cash') }}</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>{{ __('Bank transfer') }}</option>
                    </select>
                    @error('payment_method')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="treasury">{{ __('Treasury') }}</label>
                    <select id="treasury" name="treasury_id">
                        <option value="">{{ __('Select a treasury when recording a payment') }}</option>
                        @foreach ($treasuries as $treasury)
                            <option value="{{ $treasury->id }}" @selected(old('treasury_id') == $treasury->id)>{{ $treasury->name }} — {{ $treasury->type }} — {{ number_format((float) $treasury->current_balance, 2) }} {{ $currency }}</option>
                        @endforeach
                    </select>
                    @error('treasury_id')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field form-field-wide">
                    <label class="field-label" for="payroll-notes">{{ __('Notes') }}</label>
                    <textarea id="payroll-notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
                    @error('notes')<small class="field-error">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="form-actions"><span class="form-hint">{{ __('When entering a paid amount, select a payment method and a treasury with sufficient balance.') }}</span><button class="button button-primary" type="submit">{{ __('Create payroll slip') }}</button></div>
        </form>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading"><div><span class="eyebrow">{{ __('Payroll register') }}</span><h3>{{ __('Previous payroll slips') }}</h3></div></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('Employee') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Month') }}</th><th>{{ __('Due') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Remaining') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($slips as $slip)
                        <tr>
                            <td class="table-primary-cell">{{ $slip->employee->contact->name }}</td>
                            <td>{{ $slip->branch->name }}</td>
                            <td>{{ $slip->year_month_key }}</td>
                            <td>{{ number_format((float) $slip->gross_deserved_amount, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $slip->actual_paid_amount, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $slip->balance_remaining, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ __(['paid' => 'Paid', 'partially_paid' => 'Partially paid', 'unpaid' => 'Unpaid'][$slip->payout_status] ?? $slip->payout_status) }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="7">{{ __('There are no payroll slips yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $slips->links() }}</div>
    </section>
</x-pharmacy-layout>
