<x-pharmacy-layout :title="__('نقطة البيع')">
    <section
        class="cashier-page"
        id="cashier-root"
        data-cashier-root
        data-config="{{ json_encode([
            'branchId' => auth()->user()->branch_id,
            'role' => auth()->user()->role,
            'taxRate' => $taxRate,
            'currency' => $currency,
            'openSession' => $openSession,
            'treasuries' => $treasuries,
            'branches' => $branches,
            'patients' => $patients,
            'policies' => $policies,
            'requiresBranchSelection' => $requiresBranchSelection,
        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
    >
        <div class="page-intro cashier-intro">
            <div>
                <span class="eyebrow">{{ __('المبيعات والتحصيل') }}</span>
                <h2>{{ __('نقطة البيع') }}</h2>
                <p>{{ __('ابحث عن الصنف، أضفه إلى الفاتورة، ثم أكمل عملية التحصيل.') }}</p>
            </div>
            <div class="cashier-session-controls">
                <a class="button button-secondary" href="{{ route('pharmacy.cashier.reports') }}">{{ __('تقارير الكاشير') }}</a>
                <span class="session-status {{ $openSession ? 'session-open' : 'session-closed' }}">
                    <i></i>{{ $openSession ? __('الجلسة مفتوحة') : __('لا توجد جلسة مفتوحة') }}
                </span>
                @if ($openSession)
                    <button class="button button-secondary" type="button" data-close-session>{{ __('إغلاق الجلسة') }}</button>
                @else
                    <button class="button button-primary" type="button" data-open-session>{{ __('فتح جلسة جديدة') }}</button>
                @endif
            </div>
        </div>

        <div class="cashier-layout">
            <section class="catalog-panel surface-card">
                <div class="catalog-heading">
                    <div>
                        <span class="eyebrow">{{ __('كتالوج الأصناف') }}</span>
                        <h3>{{ __('اختر المنتجات') }}</h3>
                    </div>
                    <span class="catalog-count" data-product-count>—</span>
                </div>
                <form class="catalog-search" data-product-search-form>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <input type="search" placeholder="{{ __('ابحث باسم الدواء أو الباركود...') }}" autocomplete="off" data-product-search>
                    <kbd>Ctrl K</kbd>
                </form>
                <div class="catalog-feedback" data-catalog-feedback hidden></div>
                <div class="product-grid" data-products>
                    <div class="catalog-placeholder">
                        <span class="loading-spinner"></span>
                        <p>{{ __('جارٍ تجهيز كتالوج الأدوية...') }}</p>
                    </div>
                </div>
                <div class="catalog-footer">
                    <span>{{ __('الأسعار تُحتسب وفق قائمة الأسعار المعتمدة') }}</span>
                    <button class="text-button" type="button" data-refresh-products>{{ __('تحديث القائمة') }}</button>
                </div>
            </section>

            <aside class="checkout-panel surface-card">
                <div class="checkout-heading">
                    <div>
                        <span class="eyebrow">{{ __('الفاتورة الحالية') }}</span>
                        <h3>{{ __('سلة البيع') }}</h3>
                    </div>
                    <button class="text-button danger-text" type="button" data-clear-cart>{{ __('تفريغ السلة') }}</button>
                </div>

                <div class="checkout-selectors">
                    <label class="field-label" for="cashier-customer">{{ __('العميل / المريض') }}</label>
                    <select id="cashier-customer" data-customer>
                        <option value="">{{ __('عميل نقدي') }}</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" data-credit-limit="{{ $patient->credit_limit }}">{{ $patient->name }}</option>
                        @endforeach
                    </select>

                    <label class="field-label" for="cashier-policy">{{ __('التأمين الطبي') }} <span>{{ __('اختياري') }}</span></label>
                    <select id="cashier-policy" data-policy disabled>
                        <option value="">{{ __('بدون تأمين') }}</option>
                        @foreach ($policies as $policy)
                            <option value="{{ $policy->id }}" data-patient="{{ $policy->patient_contact_id }}">
                                {{ $policy->plan->name }} · {{ $policy->patient->name }} · {{ $policy->policy_number }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="cart-list" data-cart>
                    <div class="cart-empty">
                        <span class="cart-empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.5 11h10L20 8H6M9 19a1 1 0 1 0 0 .01M17 19a1 1 0 1 0 0 .01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <strong>{{ __('السلة فارغة') }}</strong>
                        <small>{{ __('أضف صنفًا للبدء في إعداد الفاتورة.') }}</small>
                    </div>
                </div>

                <div class="discount-fields">
                    <label class="field-label" for="invoice-discount">{{ __('خصم الفاتورة') }}</label>
                    <div class="input-with-suffix"><input id="invoice-discount" type="number" min="0" step="0.01" value="0" data-invoice-discount><span>{{ $currency }}</span></div>
                    <input class="discount-reason-input" type="text" placeholder="{{ __('سبب الخصم (مطلوب عند تطبيقه)') }}" data-discount-reason hidden>
                </div>

                <div class="quote-error" data-quote-error hidden></div>
                <div class="totals-box">
                    <div><span>{{ __('المجموع قبل الخصم') }}</span><strong data-subtotal>0.00 {{ $currency }}</strong></div>
                    <div><span>{{ __('الخصومات') }}</span><strong class="total-discount" data-discount>0.00 {{ $currency }}</strong></div>
                    <div><span>{{ __('الضريبة') }} ({{ number_format($taxRate, 2) }}%)</span><strong data-tax>0.00 {{ $currency }}</strong></div>
                    <div class="grand-total-row"><span>{{ __('الإجمالي') }}</span><strong data-grand-total>0.00 <small>{{ $currency }}</small></strong></div>
                    <div class="insurance-total-row" data-insurance-row hidden><span>{{ __('حصة التأمين') }}</span><strong data-insurance-due>0.00 {{ $currency }}</strong></div>
                    <div class="patient-total-row"><span>{{ __('المطلوب من العميل') }}</span><strong data-patient-due>0.00 {{ $currency }}</strong></div>
                </div>

                <div class="payment-fields">
                    <label class="field-label" for="cashier-payment-method">{{ __('طريقة التحصيل') }}</label>
                    <select id="cashier-payment-method" data-payment-method>
                        <option value="cash">{{ __('cash') }}</option>
                        <option value="card">{{ __('card') }}</option>
                        <option value="credit">{{ __('credit') }}</option>
                    </select>
                    <div class="network-payment-field" data-network-field hidden>
                        <label class="field-label" for="cashier-network-treasury">{{ __('الشبكة / حساب التحصيل') }}</label>
                        <select id="cashier-network-treasury" data-network-treasury>
                            <option value="">{{ __('اختر الشبكة') }}</option>
                            @foreach ($treasuries as $treasury)
                                @if ($treasury->type === 'bank')
                                    <option value="{{ $treasury->id }}" data-branch="{{ $treasury->branch_id }}" data-type="{{ $treasury->type }}">{{ $treasury->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <label class="field-label" for="cashier-payment-amount">{{ __('المبلغ المدفوع') }}</label>
                    <div class="input-with-suffix"><input id="cashier-payment-amount" type="number" min="0" step="0.01" data-payment-amount><span>{{ $currency }}</span></div>
                </div>

                <button class="button button-primary checkout-button" type="button" data-checkout disabled>
                    <span>{{ __('تأكيد وإتمام البيع') }}</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <p class="checkout-note">{{ __('سيتم تحديث المخزون والقيود تلقائيًا بعد تأكيد الفاتورة.') }}</p>
            </aside>
        </div>

        <div class="modal-backdrop" data-open-modal hidden>
            <section class="resource-modal session-modal" role="dialog" aria-modal="true" aria-labelledby="open-session-title">
                <div class="modal-heading">
                    <div><span class="eyebrow">{{ __('بداية الوردية') }}</span><h2 id="open-session-title">{{ __('فتح جلسة الكاشير') }}</h2></div>
                    <button class="icon-button" type="button" aria-label="{{ __('إغلاق') }}" data-session-modal-close>×</button>
                </div>
                <form data-open-session-form>
                    @if ($requiresBranchSelection)
                        <label class="field-label" for="session-branch">{{ __('الفرع') }}</label>
                        <select id="session-branch" name="branch_id" required data-session-branch>
                            <option value="">{{ __('اختر الفرع') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <label class="field-label" for="session-treasury">{{ __('درج النقد') }}</label>
                    <select id="session-treasury" name="treasury_id" required data-session-treasury>
                        <option value="">{{ __('اختر درج النقد') }}</option>
                        @foreach ($treasuries as $treasury)
                            @if ($treasury->type === 'cash_drawer')
                                <option value="{{ $treasury->id }}" data-branch="{{ $treasury->branch_id }}" data-type="{{ $treasury->type }}" data-reserve="{{ $treasury->drawer_reserve_amount }}">{{ $treasury->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <p class="modal-description">{{ __('أدخل كامل النقد الموجود في الدرج عند بداية الوردية. العهدة الثابتة تبقى منفصلة وتُعرض عند الإغلاق.') }}</p>
                    <label class="field-label" for="opening-cash">{{ __('النقد الفعلي عند بداية الوردية') }}</label>
                    <div class="input-with-suffix"><input id="opening-cash" name="opening_cash" type="number" min="0" step="0.01" value="0" required><span>{{ $currency }}</span></div>
                    <div class="form-error" data-session-error hidden></div>
                    <div class="modal-actions">
                        <button class="button button-secondary" type="button" data-session-modal-close>إلغاء</button>
                        <button class="button button-primary" type="submit" data-open-session-submit>بدء الوردية</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="modal-backdrop" data-close-modal hidden>
            <section class="resource-modal session-modal" role="dialog" aria-modal="true" aria-labelledby="close-session-title">
                <div class="modal-heading">
                <div><span class="eyebrow">{{ __('نهاية الوردية') }}</span><h2 id="close-session-title">{{ __('إغلاق جلسة الكاشير') }}</h2></div>
                <button class="icon-button" type="button" aria-label="{{ __('إغلاق') }}" data-close-session-modal>×</button>
                </div>
                <form data-close-session-form>
                    <p class="modal-description">{{ __('عدّ النقد الموجود في الدرج. سيُرحّل الفائض بعد العهدة الثابتة إلى الخزينة الرئيسية.') }}</p>
                    <div class="session-close-summary">
                        <div><span>{{ __('النقد المتوقع') }}</span><strong>{{ number_format((float) ($openSession?->expected_cash ?? 0), 2) }} {{ $currency }}</strong></div>
                        <div><span>{{ __('العهدة الثابتة') }}</span><strong>{{ number_format((float) ($openSession?->treasury?->drawer_reserve_amount ?? 0), 2) }} {{ $currency }}</strong></div>
                        <div><span>{{ __('المبلغ المرحّل تقديريًا') }}</span><strong data-transfer-estimate>0.00 {{ $currency }}</strong></div>
                    </div>
                    <label class="field-label" for="closing-cash">{{ __('النقد المعدود عند الإغلاق') }}</label>
                    <div class="input-with-suffix"><input id="closing-cash" name="actual_closing_cash" type="number" min="0" step="0.01" value="{{ $openSession?->expected_cash ?? 0 }}" data-closing-cash required><span>{{ $currency }}</span></div>
                    <label class="field-label" for="closing-safe">{{ __('الخزينة الرئيسية') }}</label>
                    <select id="closing-safe" name="closing_treasury_id" required data-closing-safe>
                        <option value="">{{ __('اختر الخزينة الرئيسية') }}</option>
                        @foreach ($treasuries as $treasury)
                            @if ($treasury->type === 'main_safe')
                                <option value="{{ $treasury->id }}" data-branch="{{ $treasury->branch_id }}" data-type="{{ $treasury->type }}">{{ $treasury->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <div class="form-error" data-close-error hidden></div>
                    <div class="modal-actions">
                        <button class="button button-secondary" type="button" data-close-session-modal>{{ __('إلغاء') }}</button>
                        <button class="button button-primary" type="submit" data-close-session-submit>{{ __('تأكيد الإغلاق') }}</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="toast-region" aria-live="polite" data-toast-region></div>
    </section>
</x-pharmacy-layout>
