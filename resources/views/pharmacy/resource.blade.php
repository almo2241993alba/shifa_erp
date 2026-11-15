<x-pharmacy-layout :title="$page['title']">
    <section
        class="management-page"
        id="management-root"
        data-management-root
        data-config="{{ json_encode($page, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
        data-currency="{{ $currency }}"
    >
        <div class="page-intro">
            <div>
                <span class="eyebrow">{{ __('إدارة الصيدلية') }}</span>
                <h2>{{ $page['title'] }}</h2>
                <p>{{ $page['description'] }}</p>
            </div>
            @if ($page['writable'])
                <button class="button button-primary" type="button" data-create>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    {{ __('إضافة سجل') }}
                </button>
            @endif
        </div>

        <div class="resource-toolbar surface-card">
            <form class="resource-search" data-search-form>
                <label class="sr-only" for="resource-search-input">{{ __('ابحث في السجلات') }}</label>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                <input id="resource-search-input" type="search" placeholder="{{ __('ابحث في السجلات...') }}" autocomplete="off" data-search>
                <button class="button button-secondary button-small" type="submit">{{ __('بحث') }}</button>
            </form>
            <div class="resource-count" data-count>{{ __('جارٍ تحميل السجلات...') }}</div>
        </div>

        <div class="surface-card table-card">
            <div class="table-wrap">
                <table class="data-table resource-table">
                    <thead><tr data-table-head></tr></thead>
                    <tbody data-table-body>
                        <tr><td class="table-loading" colspan="8"><span class="loading-spinner"></span> {{ __('جارٍ تحميل البيانات...') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="resource-pagination" data-pagination></div>
        </div>

        <div class="modal-backdrop" data-modal hidden>
            <section class="resource-modal" role="dialog" aria-modal="true" aria-labelledby="resource-modal-title">
                <div class="modal-heading">
                    <div><span class="eyebrow">{{ __('بيانات السجل') }}</span><h2 id="resource-modal-title">{{ __('إضافة سجل') }}</h2></div>
                    <button class="icon-button" type="button" aria-label="{{ __('إغلاق') }}" data-modal-close>×</button>
                </div>
                <form data-resource-form>
                    <div class="form-grid" data-fields></div>
                    <div class="form-error" data-form-error hidden></div>
                    <div class="modal-actions">
                        <button class="button button-secondary" type="button" data-modal-close>{{ __('إلغاء') }}</button>
                        <button class="button button-primary" type="submit" data-save>{{ __('حفظ البيانات') }}</button>
                    </div>
                </form>
            </section>
        </div>
        <div class="toast-region" aria-live="polite" data-toast-region></div>
    </section>
</x-pharmacy-layout>
