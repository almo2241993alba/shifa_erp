<x-pharmacy-layout title="نقطة البيع">
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
                <span class="eyebrow">المبيعات والتحصيل</span>
                <h2>نقطة البيع</h2>
                <p>ابحث عن الصنف، أضفه إلى الفاتورة، ثم أكمل عملية التحصيل.</p>
            </div>
            <div class="cashier-session-controls">
                <span class="session-status {{ $openSession ? 'session-open' : 'session-closed' }}">
                    <i></i>{{ $openSession ? 'الجلسة مفتوحة' : 'لا توجد جلسة مفتوحة' }}
                </span>
                @if ($openSession)
                    <button class="button button-secondary" type="button" data-close-session>إغلاق الجلسة</button>
                @else
                    <button class="button button-primary" type="button" data-open-session>فتح جلسة جديدة</button>
                @endif
            </div>
        </div>

        <div class="cashier-layout">
            <section class="catalog-panel surface-card">
                <div class="catalog-heading">
                    <div>
                        <span class="eyebrow">كتالوج الأصناف</span>
                        <h3>اختر المنتجات</h3>
                    </div>
                    <span class="catalog-count" data-product-count>—</span>
                </div>
                <form class="catalog-search" data-product-search-form>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <input type="search" placeholder="ابحث باسم الدواء أو الباركود..." autocomplete="off" data-product-search>
                    <kbd>Ctrl K</kbd>
                </form>
                <div class="catalog-feedback" data-catalog-feedback hidden></div>
                <div class="product-grid" data-products>
                    <div class="catalog-placeholder">
                        <span class="loading-spinner"></span>
                        <p>جارٍ تجهيز كتالوج الأدوية...</p>
                    </div>
                </div>
                <div class="catalog-footer">
                    <span>الأسعار تُحتسب وفق قائمة الأسعار المعتمدة</span>
                    <button class="text-button" type="button" data-refresh-products>تحديث القائمة</button>
                </div>
            </section>

            <aside class="checkout-panel surface-card">
                <div class="checkout-heading">
                    <div>
                        <span class="eyebrow">الفاتورة الحالية</span>
                        <h3>سلة البيع</h3>
                    </div>
                    <button class="text-button danger-text" type="button" data-clear-cart>تفريغ السلة</button>
                </div>

                <div class="checkout-selectors">
                    <label class="field-label" for="cashier-customer">العميل / المريض</label>
                    <select id="cashier-customer" data-customer>
                        <option value="">عميل نقدي</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" data-credit-limit="{{ $patient->credit_limit }}">{{ $patient->name }}</option>
                        @endforeach
                    </select>

                    <label class="field-label" for="cashier-policy">التأمين الطبي <span>اختياري</span></label>
                    <select id="cashier-policy" data-policy disabled>
                        <option value="">بدون تأمين</option>
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
                        <strong>السلة فارغة</strong>
                        <small>أضف صنفًا للبدء في إعداد الفاتورة.</small>
                    </div>
                </div>

                <div class="discount-fields">
                    <label class="field-label" for="invoice-discount">خصم الفاتورة</label>
                    <div class="input-with-suffix"><input id="invoice-discount" type="number" min="0" step="0.01" value="0" data-invoice-discount><span>{{ $currency }}</span></div>
                    <input class="discount-reason-input" type="text" placeholder="سبب الخصم (مطلوب عند تطبيقه)" data-discount-reason hidden>
                </div>

                <div class="quote-error" data-quote-error hidden></div>
                <div class="totals-box">
                    <div><span>المجموع قبل الخصم</span><strong data-subtotal>0.00 {{ $currency }}</strong></div>
                    <div><span>الخصومات</span><strong class="total-discount" data-discount>0.00 {{ $currency }}</strong></div>
                    <div><span>الضريبة ({{ number_format($taxRate, 2) }}%)</span><strong data-tax>0.00 {{ $currency }}</strong></div>
                    <div class="grand-total-row"><span>الإجمالي</span><strong data-grand-total>0.00 <small>{{ $currency }}</small></strong></div>
                    <div class="insurance-total-row" data-insurance-row hidden><span>حصة التأمين</span><strong data-insurance-due>0.00 {{ $currency }}</strong></div>
                    <div class="patient-total-row"><span>المطلوب من العميل</span><strong data-patient-due>0.00 {{ $currency }}</strong></div>
                </div>

                <div class="payment-fields">
                    <label class="field-label" for="cashier-payment-method">طريقة التحصيل</label>
                    <select id="cashier-payment-method" data-payment-method>
                        <option value="cash">نقدًا</option>
                        <option value="card">بطاقة / شبكة</option>
                        <option value="credit">آجل على حساب العميل</option>
                    </select>
                    <label class="field-label" for="cashier-payment-amount">المبلغ المدفوع</label>
                    <div class="input-with-suffix"><input id="cashier-payment-amount" type="number" min="0" step="0.01" data-payment-amount><span>{{ $currency }}</span></div>
                </div>

                <button class="button button-primary checkout-button" type="button" data-checkout disabled>
                    <span>تأكيد وإتمام البيع</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <p class="checkout-note">سيتم تحديث المخزون والقيود تلقائيًا بعد تأكيد الفاتورة.</p>
            </aside>
        </div>

        <div class="modal-backdrop" data-open-modal hidden>
            <section class="resource-modal session-modal" role="dialog" aria-modal="true" aria-labelledby="open-session-title">
                <div class="modal-heading">
                    <div><span class="eyebrow">بداية الوردية</span><h2 id="open-session-title">فتح جلسة الكاشير</h2></div>
                    <button class="icon-button" type="button" aria-label="إغلاق" data-session-modal-close>×</button>
                </div>
                <form data-open-session-form>
                    @if ($requiresBranchSelection)
                        <label class="field-label" for="session-branch">الفرع</label>
                        <select id="session-branch" name="branch_id" required data-session-branch>
                            <option value="">اختر الفرع</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <label class="field-label" for="session-treasury">الخزينة</label>
                    <select id="session-treasury" name="treasury_id" required data-session-treasury>
                        <option value="">اختر الخزينة</option>
                        @foreach ($treasuries as $treasury)
                            <option value="{{ $treasury->id }}" data-branch="{{ $treasury->branch_id }}">{{ $treasury->name }} · {{ $treasury->type }}</option>
                        @endforeach
                    </select>
                    <label class="field-label" for="opening-cash">رصيد بداية الوردية</label>
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
                    <div><span class="eyebrow">نهاية الوردية</span><h2 id="close-session-title">إغلاق جلسة الكاشير</h2></div>
                    <button class="icon-button" type="button" aria-label="إغلاق" data-close-session-modal>×</button>
                </div>
                <form data-close-session-form>
                    <p class="modal-description">أدخل النقد الفعلي الموجود في الخزينة لإتمام تسوية الوردية.</p>
                    <label class="field-label" for="closing-cash">النقد الفعلي عند الإغلاق</label>
                    <div class="input-with-suffix"><input id="closing-cash" name="actual_closing_cash" type="number" min="0" step="0.01" value="{{ $openSession?->expected_cash ?? 0 }}" required><span>{{ $currency }}</span></div>
                    <div class="form-error" data-close-error hidden></div>
                    <div class="modal-actions">
                        <button class="button button-secondary" type="button" data-close-session-modal>إلغاء</button>
                        <button class="button button-primary" type="submit" data-close-session-submit>تأكيد الإغلاق</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="toast-region" aria-live="polite" data-toast-region></div>
    </section>
</x-pharmacy-layout>
