<x-pharmacy-layout :title="__('Profile')">
    <section class="page-intro profile-intro">
        <div>
            <span class="eyebrow">{{ __('ملف الحساب') }}</span>
            <h2>{{ __('Profile') }}</h2>
            <p>{{ __('Manage your personal information, password, and account settings.') }}</p>
        </div>
    </section>

    <div class="profile-page">
        <section class="surface-card profile-section">
            <livewire:profile.update-profile-information-form />
        </section>
        <section class="surface-card profile-section">
            <livewire:profile.update-password-form />
        </section>
        <section class="surface-card profile-section">
            <livewire:profile.delete-user-form />
        </section>
    </div>
</x-pharmacy-layout>
