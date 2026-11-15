<x-pharmacy-layout title="صرف الرواتب">
    <section class="page-intro">
        <div><span class="eyebrow">الموارد البشرية</span><h2>صرف الرواتب</h2><p>أنشئ مسير الشهر؛ تُحسب الاستحقاقات ويُسجل القيد المالي من خدمة الرواتب المعتمدة.</p></div>
    </section>

    @if (session('status'))
        <div class="form-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="form-error-summary" role="alert">تعذر إنشاء المسير. راجع الأخطاء وأرصدة الخزينة ثم حاول مرة أخرى.</div>
    @endif

    <section class="surface-card form-card">
        <div class="surface-heading"><div><span class="eyebrow">مسير جديد</span><h3>بيانات الصرف</h3></div></div>
        <form method="POST" action="{{ route('pharmacy.payroll.store') }}" class="payroll-form">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label class="field-label" for="payroll-employee">الموظف</label>
                    <select id="payroll-employee" name="employee_id" required>
                        <option value="">اختر الموظف</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id') === $employee->id)>{{ $employee->contact->name }} — {{ $employee->branch->name }}</option>
                        @endforeach
                    </select>
                    @error('employee_id')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="year-month">شهر الراتب</label>
                    <input id="year-month" name="year_month_key" type="month" value="{{ old('year_month_key', now()->format('Y-m')) }}" required>
                    @error('year_month_key')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="paid-amount">المبلغ المصروف</label>
                    <input id="paid-amount" name="actual_paid_amount" type="number" min="0" step="0.01" value="{{ old('actual_paid_amount', 0) }}">
                    @error('actual_paid_amount')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="bonus">المكافأة</label>
                    <input id="bonus" name="amount_bonus" type="number" min="0" step="0.01" value="{{ old('amount_bonus', 0) }}">
                    @error('amount_bonus')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="deductions">الاستقطاعات والغرامات</label>
                    <input id="deductions" name="total_deductions_fines" type="number" min="0" step="0.01" value="{{ old('total_deductions_fines', 0) }}">
                    @error('total_deductions_fines')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="payment-method">طريقة الدفع</label>
                    <select id="payment-method" name="payment_method">
                        <option value="">بدون صرف الآن</option>
                        <option value="cash" @selected(old('payment_method') === 'cash')>نقداً</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>تحويل بنكي</option>
                    </select>
                    @error('payment_method')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field">
                    <label class="field-label" for="treasury">الخزينة</label>
                    <select id="treasury" name="treasury_id">
                        <option value="">اختر الخزينة عند وجود دفعة</option>
                        @foreach ($treasuries as $treasury)
                            <option value="{{ $treasury->id }}" @selected(old('treasury_id') == $treasury->id)>{{ $treasury->name }} — {{ $treasury->type }} — {{ number_format((float) $treasury->current_balance, 2) }} {{ $currency }}</option>
                        @endforeach
                    </select>
                    @error('treasury_id')<small class="field-error">{{ $message }}</small>@enderror
                </div>
                <div class="form-field form-field-wide">
                    <label class="field-label" for="payroll-notes">ملاحظات</label>
                    <textarea id="payroll-notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea>
                    @error('notes')<small class="field-error">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="form-actions"><span class="form-hint">عند إدخال مبلغ مصروف، يلزم تحديد طريقة الدفع وخزينة برصيد كافٍ.</span><button class="button button-primary" type="submit">إنشاء مسير الراتب</button></div>
        </form>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading"><div><span class="eyebrow">سجل الرواتب</span><h3>المسيرات السابقة</h3></div></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>الموظف</th><th>الفرع</th><th>الشهر</th><th>المستحق</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th></tr></thead>
                <tbody>
                    @forelse ($slips as $slip)
                        <tr>
                            <td class="table-primary-cell">{{ $slip->employee->contact->name }}</td>
                            <td>{{ $slip->branch->name }}</td>
                            <td>{{ $slip->year_month_key }}</td>
                            <td>{{ number_format((float) $slip->gross_deserved_amount, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $slip->actual_paid_amount, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ number_format((float) $slip->balance_remaining, 2) }} <small>{{ $currency }}</small></td>
                            <td>{{ ['paid' => 'مدفوع', 'partially_paid' => 'مدفوع جزئياً', 'unpaid' => 'غير مدفوع'][$slip->payout_status] ?? $slip->payout_status }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="7">لا توجد مسيرات رواتب بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $slips->links() }}</div>
    </section>
</x-pharmacy-layout>
