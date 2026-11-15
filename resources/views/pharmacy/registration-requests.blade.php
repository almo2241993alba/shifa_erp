<x-pharmacy-layout :title="__('طلبات التسجيل')">
    <section class="page-intro">
        <div>
            <span class="eyebrow">{{ __('إدارة الحسابات') }}</span>
            <h2>{{ __('طلبات التسجيل') }}</h2>
            <p>{{ __('راجع بيانات المتقدم، ثم اختر فرعه وصلاحيته قبل تفعيل الحساب.') }}</p>
        </div>
    </section>

    @if (session('status'))
        <div class="form-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="surface-card table-card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>{{ __('الاسم') }}</th><th>{{ __('البريد الإلكتروني') }}</th><th>{{ __('تاريخ الطلب') }}</th><th>{{ __('اعتماد الحساب') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($requests as $registration)
                        <tr>
                            <td class="table-primary-cell">{{ $registration->name }}</td>
                            <td>{{ $registration->email }}</td>
                            <td>{{ $registration->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <form class="approval-form" method="POST" action="{{ route('pharmacy.registration-requests.approve', $registration) }}">
                                    @csrf
                                    <label class="sr-only" for="branch-{{ $registration->id }}">{{ __('الفرع') }}</label>
                                    <select id="branch-{{ $registration->id }}" name="branch_id" required>
                                        <option value="">{{ __('اختر الفرع') }}</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <label class="sr-only" for="role-{{ $registration->id }}">{{ __('الصلاحية') }}</label>
                                    <select id="role-{{ $registration->id }}" name="role" required>
                                        <option value="cashier">{{ __('كاشير') }}</option>
                                        <option value="pharmacist">{{ __('صيدلي') }}</option>
                                        <option value="accountant">{{ __('محاسب') }}</option>
                                        <option value="storekeeper">{{ __('أمين مخزن') }}</option>
                                    </select>
                                    <button class="button button-primary button-small" type="submit">{{ __('اعتماد') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="4">{{ __('لا توجد طلبات تسجيل معلقة.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $requests->links() }}</div>
    </section>
</x-pharmacy-layout>
