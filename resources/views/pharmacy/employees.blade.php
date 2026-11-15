<x-pharmacy-layout :title="__('الموظفون')">
    <section class="page-intro">
        <div><span class="eyebrow">{{ __('الموارد البشرية') }}</span><h2>{{ __('الموظفون') }}</h2><p>{{ __('أضف بيانات الموظف ومعلومات وظيفته وراتبه على خطوتين.') }}</p></div>
    </section>

    @if (session('status'))
        <div class="form-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="form-error-summary" role="alert">{{ __('راجع الحقول المحددة أدناه. لم يتم حفظ البيانات.') }}</div>
    @endif

    <section class="surface-card form-card">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('موظف جديد') }}</span><h3>{{ __('بيانات الموظف') }}</h3></div>
            <span class="wizard-progress">{{ __('خطوتان') }}</span>
        </div>
        <form method="POST" action="{{ route('pharmacy.employees.store-page') }}" data-wizard data-wizard-start="{{ $errors->hasAny(['employee_number', 'national_id', 'job_title', 'hire_date', 'salary_type', 'salary_basic', 'allowances_fixed', 'commission_rate', 'license_number', 'license_expiry']) ? 1 : 0 }}">
            @csrf
            <section class="wizard-step" data-wizard-step="0">
                <h4>{{ __('١. البيانات الأساسية') }}</h4>
                <div class="form-grid">
                    <div class="form-field">
                        <label class="field-label" for="employee-name">{{ __('اسم الموظف') }}</label>
                        <input id="employee-name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="employee-email">{{ __('البريد الإلكتروني') }}</label>
                        <input id="employee-email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email">
                        @error('email')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="employee-branch">{{ __('الفرع') }}</label>
                        <select id="employee-branch" name="branch_id" required>
                            <option value="">{{ __('اختر الفرع') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field form-field-wide">
                        <label class="field-label" for="employee-address">{{ __('العنوان') }}</label>
                        <input id="employee-address" name="address" value="{{ old('address') }}" maxlength="255">
                        @error('address')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
                <button class="button button-primary" type="button" data-wizard-next>{{ __('التالي: الوظيفة والراتب') }}</button>
            </section>

            <section class="wizard-step" data-wizard-step="1" hidden>
                <h4>{{ __('٢. الوظيفة والراتب') }}</h4>
                <div class="form-grid">
                    <div class="form-field">
                        <label class="field-label" for="employee-number">{{ __('الرقم الوظيفي') }}</label>
                        <input id="employee-number" name="employee_number" value="{{ old('employee_number') }}" required maxlength="255">
                        @error('employee_number')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="national-id">{{ __('رقم الهوية') }}</label>
                        <input id="national-id" name="national_id" value="{{ old('national_id') }}" required maxlength="255">
                        @error('national_id')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="job-title">{{ __('المسمى الوظيفي') }}</label>
                        <select id="job-title" name="job_title" required>
                            <option value="">{{ __('اختر الوظيفة') }}</option>
                            @foreach (['manager' => 'مدير', 'pharmacist' => 'صيدلي', 'assistant_pharmacist' => 'مساعد صيدلي', 'cashier' => 'كاشير', 'accountant' => 'محاسب', 'storekeeper' => 'أمين مخزن', 'cleaner' => 'عامل نظافة'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('job_title') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        @error('job_title')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="hire-date">{{ __('تاريخ التعيين') }}</label>
                        <input id="hire-date" name="hire_date" type="date" value="{{ old('hire_date') }}" required>
                        @error('hire_date')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="salary-type">{{ __('نظام الراتب') }}</label>
                        <select id="salary-type" name="salary_type" required>
                            <option value="monthly" @selected(old('salary_type', 'monthly') === 'monthly')>{{ __('شهري') }}</option>
                            <option value="daily" @selected(old('salary_type') === 'daily')>{{ __('يومي') }}</option>
                            <option value="shift" @selected(old('salary_type') === 'shift')>{{ __('بالوردية') }}</option>
                        </select>
                        @error('salary_type')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="salary-basic">{{ __('الراتب الأساسي / قيمة اليومية') }}</label>
                        <input id="salary-basic" name="salary_basic" type="number" min="0" step="0.01" value="{{ old('salary_basic') }}" required>
                        @error('salary_basic')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="allowances">{{ __('البدلات الثابتة') }}</label>
                        <input id="allowances" name="allowances_fixed" type="number" min="0" step="0.01" value="{{ old('allowances_fixed', 0) }}">
                        @error('allowances_fixed')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="commission">{{ __('العمولة (%)') }}</label>
                        <input id="commission" name="commission_rate" type="number" min="0" max="100" step="0.01" value="{{ old('commission_rate', 0) }}">
                        @error('commission_rate')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="license-number">{{ __('رقم الترخيص المهني') }}</label>
                        <input id="license-number" name="license_number" value="{{ old('license_number') }}" maxlength="255">
                        @error('license_number')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="license-expiry">{{ __('انتهاء الترخيص') }}</label>
                        <input id="license-expiry" name="license_expiry" type="date" value="{{ old('license_expiry') }}">
                        @error('license_expiry')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="wizard-actions">
                    <button class="button button-secondary" type="button" data-wizard-previous>{{ __('السابق') }}</button>
                    <button class="button button-primary" type="submit">{{ __('حفظ الموظف') }}</button>
                </div>
            </section>
        </form>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading"><div><span class="eyebrow">{{ __('سجل الموظفين') }}</span><h3>{{ __('الموظفون المسجلون') }}</h3></div></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('الاسم') }}</th><th>{{ __('الرقم الوظيفي') }}</th><th>{{ __('الفرع') }}</th><th>{{ __('الوظيفة') }}</th><th>{{ __('الراتب الأساسي') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="table-primary-cell">{{ $employee->contact->name }}</td>
                            <td>{{ $employee->employee_number }}</td>
                            <td>{{ $employee->branch->name }}</td>
                            <td>{{ __($employee->job_title) }}</td>
                            <td>{{ number_format((float) $employee->salary_basic, 2) }}</td>
                            <td><span class="status-badge {{ $employee->is_active ? 'status-success' : '' }}">{{ $employee->is_active ? __('نشط') : __('غير نشط') }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="6">{{ __('لا يوجد موظفون مسجلون بعد.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $employees->links() }}</div>
    </section>
</x-pharmacy-layout>
