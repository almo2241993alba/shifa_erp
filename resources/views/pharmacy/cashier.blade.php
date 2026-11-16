<x-pharmacy-layout :title="__('Point of sale')">
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
                <span class="eyebrow">{{ __('Sales and collections') }}</span>
                <h2>{{ __('Point of sale') }}</h2>
                <p>{{ __('Search for an item, add it to the invoice, then complete checkout.') }}</p>
            </div>
            <div class="cashier-session-controls">
                <a class="button button-secondary" href="{{ route('pharmacy.cashier.reports') }}">{{ __('Cashier reports') }}</a>
                <span class="session-status {{ $openSession ? 'session-open' : 'session-closed' }}">
                    <i></i>{{ $openSession ? __('Session open') : __('No open session') }}
                </span>
                @if ($openSession)
                    <button class="button button-secondary" type="button" data-close-session>{{ __('Close session') }}</button>
                @else
                    <button class="button button-primary" type="button" data-open-session>{{ __('Open a new session') }}</button>
                @endif
            </div>
        </div>

        <div class="cashier-layout">
            <section class="catalog-panel surface-card">
                <div class="catalog-heading">
                    <div>
                        <span class="eyebrow">{{ __('Product catalog') }}</span>
                        <h3>{{ __('Select products') }}</h3>
                    </div>
                    <span class="catalog-count" data-product-count>—</span>
                </div>
                <form class="catalog-search" data-product-search-form>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <input type="search" placeholder="{{ __('Search by medicine name or barcode...') }}" autocomplete="off" data-product-search>
                    <kbd>Ctrl K</kbd>
                </form>
                <div class="catalog-feedback" data-catalog-feedback hidden></div>
                <div class="product-grid" data-products>
                    <div class="catalog-placeholder">
                        <span class="loading-spinner"></span>
                        <p>{{ __('Preparing the medicine catalog...') }}</p>
                    </div>
                </div>
                <div class="catalog-footer">
                    <span>{{ __('Prices use the approved price list') }}</span>
                    <button class="text-button" type="button" data-refresh-products>{{ __('Refresh list') }}</button>
                </div>
            </section>

            <aside class="checkout-panel surface-card">
                <div class="checkout-heading">
                    <div>
                        <span class="eyebrow">{{ __('Current invoice') }}</span>
                        <h3>{{ __('Cart') }}</h3>
                    </div>
                    <button class="text-button danger-text" type="button" data-clear-cart>{{ __('Clear cart') }}</button>
                </div>

                <div class="checkout-selectors">
                    <label class="field-label" for="cashier-customer">{{ __('Customer / patient') }}</label>
                    <select id="cashier-customer" data-customer>
                        <option value="">{{ __('Walk-in customer') }}</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" data-credit-limit="{{ $patient->credit_limit }}">{{ $patient->name }}</option>
                        @endforeach
                    </select>

                    <label class="field-label" for="cashier-policy">{{ __('Medical insurance') }} <span>{{ __('Optional') }}</span></label>
                    <select id="cashier-policy" data-policy disabled>
                        <option value="">{{ __('No insurance') }}</option>
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
                        <strong>{{ __('The cart is empty') }}</strong>
                        <small>{{ __('Add an item to start building the invoice.') }}</small>
                    </div>
                </div>

                <div class="discount-fields">
                    <label class="field-label" for="invoice-discount">{{ __('Invoice discount') }}</label>
                    <div class="input-with-suffix"><input id="invoice-discount" type="number" min="0" step="0.01" value="0" data-invoice-discount><span>{{ $currency }}</span></div>
                    <input class="discount-reason-input" type="text" placeholder="{{ __('Discount reason (required when applying a discount)') }}" data-discount-reason hidden>
                </div>

                <div class="quote-error" data-quote-error hidden></div>
                <div class="totals-box">
                    <div><span>{{ __('Subtotal before discount') }}</span><strong data-subtotal>0.00 {{ $currency }}</strong></div>
                    <div><span>{{ __('Discounts') }}</span><strong class="total-discount" data-discount>0.00 {{ $currency }}</strong></div>
                    <div><span>{{ __('Tax') }} ({{ number_format($taxRate, 2) }}%)</span><strong data-tax>0.00 {{ $currency }}</strong></div>
                    <div class="grand-total-row"><span>{{ __('Total') }}</span><strong data-grand-total>0.00 <small>{{ $currency }}</small></strong></div>
                    <div class="insurance-total-row" data-insurance-row hidden><span>{{ __('Insurance share') }}</span><strong data-insurance-due>0.00 {{ $currency }}</strong></div>
                    <div class="patient-total-row"><span>{{ __('Amount due from customer') }}</span><strong data-patient-due>0.00 {{ $currency }}</strong></div>
                </div>

                <div class="payment-fields">
                    <label class="field-label" for="cashier-payment-method">{{ __('Payment method') }}</label>
                    <select id="cashier-payment-method" data-payment-method>
                        <option value="cash">{{ __('cash') }}</option>
                        <option value="card">{{ __('card') }}</option>
                        <option value="credit">{{ __('credit') }}</option>
                    </select>
                    <div class="network-payment-field" data-network-field hidden>
                        <label class="field-label" for="cashier-network-treasury">{{ __('Network / settlement account') }}</label>
                        <select id="cashier-network-treasury" data-network-treasury>
                            <option value="">{{ __('Select network') }}</option>
                            @foreach ($treasuries as $treasury)
                                @if ($treasury->type === 'bank')
                                    <option value="{{ $treasury->id }}" data-branch="{{ $treasury->branch_id }}" data-type="{{ $treasury->type }}">{{ $treasury->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <label class="field-label" for="cashier-payment-amount">{{ __('Amount paid') }}</label>
                    <div class="input-with-suffix"><input id="cashier-payment-amount" type="number" min="0" step="0.01" data-payment-amount><span>{{ $currency }}</span></div>
                </div>

                <button class="button button-primary checkout-button" type="button" data-checkout disabled>
                    <span>{{ __('Confirm and complete sale') }}</span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <p class="checkout-note">{{ __('Inventory and journal entries update after confirmation.') }}</p>
            </aside>
        </div>

        <div class="modal-backdrop" data-open-modal hidden>
            <section class="resource-modal session-modal" role="dialog" aria-modal="true" aria-labelledby="open-session-title">
                <div class="modal-heading">
                    <div><span class="eyebrow">{{ __('Shift start') }}</span><h2 id="open-session-title">{{ __('Open cashier session') }}</h2></div>
                    <button class="icon-button" type="button" aria-label="{{ __('Close') }}" data-session-modal-close>×</button>
                </div>
                <form data-open-session-form>
                    @if ($requiresBranchSelection)
                        <label class="field-label" for="session-branch">{{ __('Branch') }}</label>
                        <select id="session-branch" name="branch_id" required data-session-branch>
                            <option value="">{{ __('Select branch') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <label class="field-label" for="session-treasury">{{ __('Cash drawer') }}</label>
                    <select id="session-treasury" name="treasury_id" required data-session-treasury>
                        <option value="">{{ __('Select cash drawer') }}</option>
                        @foreach ($treasuries as $treasury)
                            @if ($treasury->type === 'cash_drawer')
                                <option value="{{ $treasury->id }}" data-branch="{{ $treasury->branch_id }}" data-type="{{ $treasury->type }}" data-reserve="{{ $treasury->drawer_reserve_amount }}">{{ $treasury->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <p class="modal-description">{{ __('Enter the total cash counted in the drawer at shift start. The fixed reserve is separate and shown at closing.') }}</p>
                    <label class="field-label" for="opening-cash">{{ __('Opening cash counted') }}</label>
                    <div class="input-with-suffix"><input id="opening-cash" name="opening_cash" type="number" min="0" step="0.01" value="0" required><span>{{ $currency }}</span></div>
                    <div class="form-error" data-session-error hidden></div>
                    <div class="modal-actions">
                        <button class="button button-secondary" type="button" data-session-modal-close>{{ __('Cancel') }}</button>
                        <button class="button button-primary" type="submit" data-open-session-submit>{{ __('Start shift') }}</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="modal-backdrop" data-close-modal hidden>
            <section class="resource-modal session-modal" role="dialog" aria-modal="true" aria-labelledby="close-session-title">
                <div class="modal-heading">
                <div><span class="eyebrow">{{ __('Shift end') }}</span><h2 id="close-session-title">{{ __('Close cashier session') }}</h2></div>
                <button class="icon-button" type="button" aria-label="{{ __('Close') }}" data-close-session-modal>×</button>
                </div>
                <form data-close-session-form>
                    <p class="modal-description">{{ __('Count the cash in the drawer. Cash above the fixed reserve will be transferred to the main safe.') }}</p>
                    <div class="session-close-summary">
                        <div><span>{{ __('Expected cash') }}</span><strong>{{ number_format((float) ($openSession?->expected_cash ?? 0), 2) }} {{ $currency }}</strong></div>
                        <div><span>{{ __('Fixed reserve') }}</span><strong>{{ number_format((float) ($openSession?->treasury?->drawer_reserve_amount ?? 0), 2) }} {{ $currency }}</strong></div>
                        <div><span>{{ __('Estimated transfer') }}</span><strong data-transfer-estimate>0.00 {{ $currency }}</strong></div>
                    </div>
                    <label class="field-label" for="closing-cash">{{ __('Closing cash counted') }}</label>
                    <div class="input-with-suffix"><input id="closing-cash" name="actual_closing_cash" type="number" min="0" step="0.01" value="{{ $openSession?->expected_cash ?? 0 }}" data-closing-cash required><span>{{ $currency }}</span></div>
                    <label class="field-label" for="closing-safe">{{ __('Main safe') }}</label>
                    <select id="closing-safe" name="closing_treasury_id" required data-closing-safe>
                        <option value="">{{ __('Select main safe') }}</option>
                        @foreach ($treasuries as $treasury)
                            @if ($treasury->type === 'main_safe')
                                <option value="{{ $treasury->id }}" data-branch="{{ $treasury->branch_id }}" data-type="{{ $treasury->type }}">{{ $treasury->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <div class="form-error" data-close-error hidden></div>
                    <div class="modal-actions">
                        <button class="button button-secondary" type="button" data-close-session-modal>{{ __('Cancel') }}</button>
                        <button class="button button-primary" type="submit" data-close-session-submit>{{ __('Confirm close') }}</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="toast-region" aria-live="polite" data-toast-region></div>
    </section>
</x-pharmacy-layout>
