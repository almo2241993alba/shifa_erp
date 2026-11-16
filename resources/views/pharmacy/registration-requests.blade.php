<x-pharmacy-layout :title="__('Registration requests')">
    <section class="page-intro">
        <div>
            <span class="eyebrow">{{ __('Account management') }}</span>
            <h2>{{ __('Registration requests') }}</h2>
            <p>{{ __('Review the applicant, then choose a branch and role before activating the account.') }}</p>
        </div>
    </section>

    @if (session('status'))
        <div class="form-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="surface-card table-card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Request date') }}</th><th>{{ __('Approve account') }}</th></tr>
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
                                    <label class="sr-only" for="branch-{{ $registration->id }}">{{ __('Branch') }}</label>
                                    <select id="branch-{{ $registration->id }}" name="branch_id" required>
                                        <option value="">{{ __('Select branch') }}</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <label class="sr-only" for="role-{{ $registration->id }}">{{ __('Role') }}</label>
                                    <select id="role-{{ $registration->id }}" name="role" required>
                                        <option value="cashier">{{ __('Cashier') }}</option>
                                        <option value="pharmacist">{{ __('Pharmacist') }}</option>
                                        <option value="accountant">{{ __('Accountant') }}</option>
                                        <option value="storekeeper">{{ __('Storekeeper') }}</option>
                                    </select>
                                    <button class="button button-primary button-small" type="submit">{{ __('Approve') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-muted" colspan="4">{{ __('There are no pending registration requests.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="resource-pagination">{{ $requests->links() }}</div>
    </section>
</x-pharmacy-layout>
