<x-pharmacy-layout :title="__('Employees')">
    <section class="page-intro">
        <div><span class="eyebrow">{{ __('Human resources') }}</span><h2>{{ __('Employees') }}</h2><p>{{ __("Add the employee's personal, job, and salary details in two steps.") }}</p></div>
    </section>

    @if (session('status'))
        <div class="form-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="form-error-summary" role="alert">{{ __('Review the highlighted fields below. The information was not saved.') }}</div>
    @endif

    <section class="surface-card form-card">
        <div class="surface-heading">
            <div><span class="eyebrow">{{ __('New employee') }}</span><h3>{{ __('Employee details') }}</h3></div>
            <span class="wizard-progress">{{ __('Two steps') }}</span>
        </div>
        <form method="POST" action="{{ route('pharmacy.employees.store-page') }}" data-wizard data-wizard-start="{{ $errors->hasAny(['employee_number', 'national_id', 'job_title', 'hire_date', 'salary_type', 'salary_basic', 'allowances_fixed', 'commission_rate', 'license_number', 'license_expiry']) ? 1 : 0 }}">
            @csrf
            <section class="wizard-step" data-wizard-step="0">
                <h4>{{ __('1. Basic information') }}</h4>
                <div class="form-grid">
                    <div class="form-field">
                        <label class="field-label" for="employee-name">{{ __('Employee name') }}</label>
                        <input id="employee-name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="employee-email">{{ __('Email') }}</label>
                        <input id="employee-email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email">
                        @error('email')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="employee-branch">{{ __('Branch') }}</label>
                        <select id="employee-branch" name="branch_id" required>
                            <option value="">{{ __('Select branch') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field form-field-wide">
                        <label class="field-label" for="employee-address">{{ __('Address') }}</label>
                        <input id="employee-address" name="address" value="{{ old('address') }}" maxlength="255">
                        @error('address')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
                <button class="button button-primary" type="button" data-wizard-next>{{ __('Next: job and salary') }}</button>
            </section>

            <section class="wizard-step" data-wizard-step="1" hidden>
                <h4>{{ __('2. Job and salary') }}</h4>
                <div class="form-grid">
                    <div class="form-field">
                        <label class="field-label" for="employee-number">{{ __('Employee number') }}</label>
                        <input id="employee-number" name="employee_number" value="{{ old('employee_number') }}" required maxlength="255">
                        @error('employee_number')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="national-id">{{ __('National ID') }}</label>
                        <input id="national-id" name="national_id" value="{{ old('national_id') }}" required maxlength="255">
                        @error('national_id')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="job-title">{{ __('Job title') }}</label>
                        <select id="job-title" name="job_title" required>
                            <option value="">{{ __('Select a job') }}</option>
                            @foreach (['manager' => 'Manager', 'pharmacist' => 'Pharmacist', 'assistant_pharmacist' => 'Assistant pharmacist', 'cashier' => 'Cashier', 'accountant' => 'Accountant', 'storekeeper' => 'Storekeeper', 'cleaner' => 'Cleaner'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('job_title') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        @error('job_title')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="hire-date">{{ __('Hire date') }}</label>
                        <input id="hire-date" name="hire_date" type="date" value="{{ old('hire_date') }}" required>
                        @error('hire_date')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="salary-type">{{ __('Salary basis') }}</label>
                        <select id="salary-type" name="salary_type" required>
                            <option value="monthly" @selected(old('salary_type', 'monthly') === 'monthly')>{{ __('Monthly') }}</option>
                            <option value="daily" @selected(old('salary_type') === 'daily')>{{ __('Daily') }}</option>
                            <option value="shift" @selected(old('salary_type') === 'shift')>{{ __('Per shift') }}</option>
                        </select>
                        @error('salary_type')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="salary-basic">{{ __('Base salary / daily rate') }}</label>
                        <input id="salary-basic" name="salary_basic" type="number" min="0" step="0.01" value="{{ old('salary_basic') }}" required>
                        @error('salary_basic')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="allowances">{{ __('Fixed allowances') }}</label>
                        <input id="allowances" name="allowances_fixed" type="number" min="0" step="0.01" value="{{ old('allowances_fixed', 0) }}">
                        @error('allowances_fixed')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="commission">{{ __('Commission (%)') }}</label>
                        <input id="commission" name="commission_rate" type="number" min="0" max="100" step="0.01" value="{{ old('commission_rate', 0) }}">
                        @error('commission_rate')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="license-number">{{ __('Professional license number') }}</label>
                        <input id="license-number" name="license_number" value="{{ old('license_number') }}" maxlength="255">
                        @error('license_number')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="license-expiry">{{ __('License expiry') }}</label>
                        <input id="license-expiry" name="license_expiry" type="date" value="{{ old('license_expiry') }}">
                        @error('license_expiry')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="wizard-actions">
                    <button class="button button-secondary" type="button" data-wizard-previous>{{ __('Previous') }}</button>
                    <button class="button button-primary" type="submit">{{ __('Save employee') }}</button>
                </div>
            </section>
        </form>
    </section>

    <section class="surface-card report-section">
        <div class="surface-heading"><div><span class="eyebrow">{{ __('Employee register') }}</span><h3>{{ __('Registered employees') }}</h3></div></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Employee number') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Job title') }}</th><th>{{ __('Base salary') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="table-primary-cell">{{ $employee->contact->name }}</td>
                            <td>{{ $employee->employee_number }}</td>
                            <td>{{ $employee->branch->name }}</td>
                            <td>{{ __($employee->job_title) }}</td>
                            <td>{{ number_format((float) $employee->salary_basic, 2) }}</td>
                            <td><span class="status-badge {{ $employee->is_active ? 'status-success' : '' }}">{{ $employee->is_active ? __('Active') : __('Inactive') }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="6">{{ __('No employees have been registered yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $employees->links() }}</div>
    </section>
</x-pharmacy-layout>
