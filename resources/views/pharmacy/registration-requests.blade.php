<x-pharmacy-layout title="طلبات التسجيل">
    <section class="page-intro">
        <div>
            <span class="eyebrow">إدارة الحسابات</span>
            <h2>طلبات التسجيل</h2>
            <p>راجع بيانات المتقدم، ثم اختر فرعه وصلاحيته قبل تفعيل الحساب.</p>
        </div>
    </section>

    @if (session('status'))
        <div class="form-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="surface-card table-card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>الاسم</th><th>البريد الإلكتروني</th><th>تاريخ الطلب</th><th>اعتماد الحساب</th></tr>
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
                                    <label class="sr-only" for="branch-{{ $registration->id }}">الفرع</label>
                                    <select id="branch-{{ $registration->id }}" name="branch_id" required>
                                        <option value="">اختر الفرع</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <label class="sr-only" for="role-{{ $registration->id }}">الصلاحية</label>
                                    <select id="role-{{ $registration->id }}" name="role" required>
                                        <option value="cashier">كاشير</option>
                                        <option value="pharmacist">صيدلي</option>
                                        <option value="accountant">محاسب</option>
                                        <option value="storekeeper">أمين مخزن</option>
                                    </select>
                                    <button class="button button-primary button-small" type="submit">اعتماد</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="4">لا توجد طلبات تسجيل معلقة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $requests->links() }}</div>
    </section>
</x-pharmacy-layout>
