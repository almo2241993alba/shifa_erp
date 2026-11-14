const cashierRoot = document.querySelector('[data-cashier-root]');

if (cashierRoot) {
    const config = JSON.parse(cashierRoot.dataset.config);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const productSearchForm = cashierRoot.querySelector('[data-product-search-form]');
    const productSearch = cashierRoot.querySelector('[data-product-search]');
    const productGrid = cashierRoot.querySelector('[data-products]');
    const cartRoot = cashierRoot.querySelector('[data-cart]');
    const customerSelect = cashierRoot.querySelector('[data-customer]');
    const policySelect = cashierRoot.querySelector('[data-policy]');
    const discountInput = cashierRoot.querySelector('[data-invoice-discount]');
    const discountReason = cashierRoot.querySelector('[data-discount-reason]');
    const paymentMethod = cashierRoot.querySelector('[data-payment-method]');
    const paymentAmount = cashierRoot.querySelector('[data-payment-amount]');
    const checkoutButton = cashierRoot.querySelector('[data-checkout]');
    const quoteError = cashierRoot.querySelector('[data-quote-error]');
    const toastRegion = cashierRoot.querySelector('[data-toast-region]');
    const openModal = cashierRoot.querySelector('[data-open-modal]');
    const closeModal = cashierRoot.querySelector('[data-close-modal]');
    const openForm = cashierRoot.querySelector('[data-open-session-form]');
    const closeForm = cashierRoot.querySelector('[data-close-session-form]');
    let activeSession = config.openSession;
    let selectedBranchId = activeSession?.branch_id ?? config.branchId;
    let cart = [];
    let quote = null;
    let searchTimer;
    let quoteTimer;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const money = (value) => `${new Intl.NumberFormat('ar', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value ?? 0))} ${escapeHtml(config.currency)}`;

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(options.method && options.method !== 'GET' ? { 'X-CSRF-TOKEN': csrf } : {}),
                ...options.headers,
            },
        });
        const payload = response.status === 204 ? null : await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(payload?.message ?? 'تعذر إكمال الطلب. تحقق من البيانات وحاول مرة أخرى.');
            error.payload = payload;
            throw error;
        }
        return payload;
    };

    const toast = (message, type = 'success') => {
        const item = document.createElement('div');
        item.className = `toast toast-${type}`;
        item.textContent = message;
        toastRegion.append(item);
        window.setTimeout(() => item.remove(), 4000);
    };

    const showError = (element, error) => {
        const errors = error.payload?.errors;
        element.textContent = errors ? Object.values(errors).flat().join(' · ') : error.message;
        element.hidden = false;
    };

    const closeError = (selector) => {
        const element = cashierRoot.querySelector(selector);
        if (element) {
            element.hidden = true;
            element.textContent = '';
        }
    };

    const availableStock = (medicine) => {
        const batches = medicine.batches ?? [];
        return batches
            .filter((batch) => {
                const correctBranch = selectedBranchId === null || Number(batch.branch_id) === Number(selectedBranchId);
                const expiryDate = batch.expiry_date ? String(batch.expiry_date).slice(0, 10) : null;
                const isValid = batch.is_active && Number(batch.remaining_quantity_base) > 0
                    && (!expiryDate || new Date(`${expiryDate}T23:59:59`) >= new Date());
                return correctBranch && isValid;
            })
            .reduce((total, batch) => total + Number(batch.remaining_quantity_base), 0);
    };

    const sellableUnits = (medicine) => (medicine.medicine_units ?? [])
        .filter((unit) => unit.is_sellable && unit.unit)
        .sort((left, right) => Number(right.is_base_unit) - Number(left.is_base_unit));

    const loadProducts = async (search = '') => {
        productGrid.innerHTML = '<div class="catalog-placeholder"><span class="loading-spinner"></span><p>جارٍ البحث عن الأصناف...</p></div>';
        closeError('[data-catalog-feedback]');
        const params = new URLSearchParams({ per_page: '24' });
        if (search.trim()) params.set('search', search.trim());
        if (selectedBranchId) params.set('branch_id', String(selectedBranchId));

        try {
            const response = await request(`/pharmacy/cashier/products?${params.toString()}`);
            const medicines = (response.data ?? []).filter((medicine) => medicine.is_active);
            renderProducts(medicines);
            cashierRoot.querySelector('[data-product-count]').textContent = `${new Intl.NumberFormat('ar').format(medicines.length)} صنف`;
        } catch (error) {
            const feedback = cashierRoot.querySelector('[data-catalog-feedback]');
            feedback.textContent = error.message;
            feedback.hidden = false;
            productGrid.replaceChildren();
        }
    };

    const renderProducts = (medicines) => {
        productGrid.replaceChildren();
        if (!medicines.length) {
            const empty = document.createElement('div');
            empty.className = 'catalog-empty';
            empty.innerHTML = '<span>⌕</span><strong>لم نجد أصنافًا مطابقة</strong><small>جرّب البحث باسم آخر أو امسح الباركود.</small>';
            productGrid.append(empty);
            return;
        }

        medicines.forEach((medicine) => {
            const units = sellableUnits(medicine);
            const unit = units[0];
            const stock = availableStock(medicine);
            const card = document.createElement('article');
            card.className = 'product-card';
            card.innerHTML = `
                <div class="product-card-top">
                    <span class="product-symbol">${escapeHtml((medicine.trade_name ?? 'د').slice(0, 1))}</span>
                    <span class="product-stock ${stock > 0 ? '' : 'product-out-of-stock'}">${stock > 0 ? `المتاح ${new Intl.NumberFormat('ar').format(stock)}` : 'نفد المخزون'}</span>
                </div>
                <div class="product-info">
                    <strong>${escapeHtml(medicine.trade_name)}</strong>
                    <small>${escapeHtml(medicine.scientific_name ?? medicine.category?.name ?? 'صنف صيدلاني')}</small>
                </div>
                <div class="product-card-bottom">
                    <span>${unit ? `${money(unit.selling_price)} <small>/${escapeHtml(unit.unit.name)}</small>` : 'السعر غير محدد'}</span>
                    <button class="add-product-button" type="button" aria-label="إضافة ${escapeHtml(medicine.trade_name)}" data-add-product="${escapeHtml(medicine.id)}" ${!unit || stock < Number(unit.conversion_factor) ? 'disabled' : ''}>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                </div>`;
            card.dataset.medicine = medicine.id;
            card._medicine = medicine;
            productGrid.append(card);
        });
    };

    const addToCart = (medicine) => {
        const units = sellableUnits(medicine);
        const unit = units[0];
        if (!unit) {
            toast('لم يتم إعداد وحدة بيع لهذا الصنف.', 'error');
            return;
        }

        const existing = cart.find((item) => item.medicine.id === medicine.id && item.unit.id === unit.unit_id);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({ medicine, unit, quantity: 1 });
        }
        renderCart();
        scheduleQuote();
        toast('تمت إضافة الصنف إلى الفاتورة.');
    };

    const renderCart = () => {
        cartRoot.replaceChildren();
        if (!cart.length) {
            cartRoot.innerHTML = '<div class="cart-empty"><span class="cart-empty-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.5 11h10L20 8H6M9 19a1 1 0 1 0 0 .01M17 19a1 1 0 1 0 0 .01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><strong>السلة فارغة</strong><small>أضف صنفًا للبدء في إعداد الفاتورة.</small></div>';
            checkoutButton.disabled = true;
            updateTotals(null);
            return;
        }

        cart.forEach((item, index) => {
            const row = document.createElement('article');
            row.className = 'cart-item';
            row.innerHTML = `
                <div class="cart-item-heading">
                    <span class="cart-item-monogram">${escapeHtml((item.medicine.trade_name ?? 'د').slice(0, 1))}</span>
                    <div class="cart-item-name"><strong>${escapeHtml(item.medicine.trade_name)}</strong><small>${money(item.unit.selling_price)} / ${escapeHtml(item.unit.unit?.name ?? '')}</small></div>
                    <button class="cart-remove" type="button" aria-label="حذف الصنف" data-remove-item="${index}">×</button>
                </div>
                <div class="cart-item-controls">
                    <select aria-label="وحدة البيع" data-cart-unit="${index}">${sellableUnits(item.medicine).map((unit) => `<option value="${escapeHtml(unit.unit_id)}" ${Number(unit.unit_id) === Number(item.unit.unit_id) ? 'selected' : ''} ${availableStock(item.medicine) < Number(unit.conversion_factor) ? 'disabled' : ''}>${escapeHtml(unit.unit.name)} · ${money(unit.selling_price)}</option>`).join('')}</select>
                    <div class="quantity-control">
                        <button type="button" aria-label="تقليل الكمية" data-quantity-step="-1" data-index="${index}">−</button>
                        <input type="number" min="1" step="1" value="${item.quantity}" aria-label="الكمية" data-quantity="${index}">
                        <button type="button" aria-label="زيادة الكمية" data-quantity-step="1" data-index="${index}">+</button>
                    </div>
                    <strong class="cart-line-total">${money(Number(item.unit.selling_price) * item.quantity)}</strong>
                </div>`;
            cartRoot.append(row);
        });
        checkoutButton.disabled = !activeSession || !quote;
    };

    const updateTotals = (data) => {
        quote = data;
        const due = Number(data?.patient_due ?? 0);
        cashierRoot.querySelector('[data-subtotal]').textContent = money(data?.subtotal);
        cashierRoot.querySelector('[data-discount]').textContent = money(data?.discount_amount);
        cashierRoot.querySelector('[data-tax]').textContent = money(data?.tax_amount);
        cashierRoot.querySelector('[data-grand-total]').innerHTML = `${money(data?.grand_total)}`;
        cashierRoot.querySelector('[data-patient-due]').textContent = money(due);
        const insuranceRow = cashierRoot.querySelector('[data-insurance-row]');
        insuranceRow.hidden = Number(data?.insurance_due ?? 0) <= 0;
        cashierRoot.querySelector('[data-insurance-due]').textContent = money(data?.insurance_due);
        if (data && !paymentAmount.dataset.touched) {
            paymentAmount.value = due.toFixed(2);
        }
        checkoutButton.disabled = !activeSession || cart.length === 0 || !data;
    };

    const scheduleQuote = () => {
        window.clearTimeout(quoteTimer);
        if (!cart.length) {
            quote = null;
            updateTotals(null);
            closeError('[data-quote-error]');
            return;
        }
        quoteTimer = window.setTimeout(refreshQuote, 180);
    };

    const refreshQuote = async () => {
        const errorBox = cashierRoot.querySelector('[data-quote-error]');
        errorBox.hidden = true;
        checkoutButton.disabled = true;
        try {
            const result = await request('/pharmacy/sales/quote', {
                method: 'POST',
                body: JSON.stringify({
                    branch_id: selectedBranchId,
                    customer_id: customerSelect.value || null,
                    insurance_policy_id: policySelect.value || null,
                    invoice_discount: Number(discountInput.value || 0),
                    discount_reason: discountReason.value.trim() || null,
                    items: cart.map((item) => ({
                        medicine_id: item.medicine.id,
                        unit_id: Number(item.unit.unit_id),
                        quantity: Number(item.quantity),
                    })),
                }),
            });
            updateTotals(result.data);
        } catch (error) {
            quote = null;
            updateTotals(null);
            showError(errorBox, error);
        }
    };

    const refreshPolicyOptions = (selectedId = '') => {
        const customerId = customerSelect.value;
        policySelect.disabled = !customerId;
        [...policySelect.options].forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            const belongsToPatient = option.dataset.patient === customerId;
            option.hidden = !belongsToPatient;
            if (!belongsToPatient && option.selected) option.selected = false;
        });
        if (selectedId) policySelect.value = selectedId;
    };

    const openSessionDialog = () => {
        openModal.hidden = false;
        document.body.classList.add('modal-visible');
        filterTreasuries();
        openModal.querySelector('select, input')?.focus();
    };

    const closeSessionDialog = () => {
        openModal.hidden = true;
        document.body.classList.remove('modal-visible');
    };

    const filterTreasuries = () => {
        const branchSelect = cashierRoot.querySelector('[data-session-branch]');
        const treasurySelect = cashierRoot.querySelector('[data-session-treasury]');
        const branchId = branchSelect?.value || selectedBranchId;
        [...treasurySelect.options].forEach((option, index) => {
            if (index === 0) return;
            const matchesBranch = !branchId || option.dataset.branch === String(branchId);
            option.hidden = !matchesBranch;
            if (!matchesBranch && option.selected) option.selected = false;
        });
    };

    productSearchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadProducts(productSearch.value);
    });
    productSearch.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => loadProducts(productSearch.value), 250);
    });
    cashierRoot.querySelector('[data-refresh-products]').addEventListener('click', () => loadProducts(productSearch.value));
    productGrid.addEventListener('click', (event) => {
        const button = event.target.closest('[data-add-product]');
        if (!button || button.disabled) return;
        const card = button.closest('[data-medicine]');
        addToCart(card._medicine);
    });
    cashierRoot.querySelector('[data-clear-cart]').addEventListener('click', () => {
        cart = [];
        renderCart();
        scheduleQuote();
    });
    cartRoot.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-item]');
        if (remove) {
            cart.splice(Number(remove.dataset.removeItem), 1);
            renderCart();
            scheduleQuote();
            return;
        }
        const step = event.target.closest('[data-quantity-step]');
        if (step) {
            const item = cart[Number(step.dataset.index)];
            item.quantity = Math.max(1, item.quantity + Number(step.dataset.quantityStep));
            renderCart();
            scheduleQuote();
        }
    });
    cartRoot.addEventListener('change', (event) => {
        const quantity = event.target.closest('[data-quantity]');
        if (quantity) {
            cart[Number(quantity.dataset.quantity)].quantity = Math.max(1, Number(quantity.value || 1));
            renderCart();
            scheduleQuote();
        }
        const unitSelect = event.target.closest('[data-cart-unit]');
        if (unitSelect) {
            const item = cart[Number(unitSelect.dataset.cartUnit)];
            item.unit = sellableUnits(item.medicine).find((unit) => Number(unit.unit_id) === Number(unitSelect.value));
            renderCart();
            scheduleQuote();
        }
    });
    customerSelect.addEventListener('change', () => {
        refreshPolicyOptions();
        scheduleQuote();
    });
    policySelect.addEventListener('change', () => {
        const selectedOption = policySelect.selectedOptions[0];
        if (selectedOption?.dataset.patient && customerSelect.value !== selectedOption.dataset.patient) {
            customerSelect.value = selectedOption.dataset.patient;
            refreshPolicyOptions(policySelect.value);
        }
        scheduleQuote();
    });
    discountInput.addEventListener('input', () => {
        discountReason.hidden = Number(discountInput.value) <= 0;
        scheduleQuote();
    });
    discountReason.addEventListener('input', scheduleQuote);
    paymentAmount.addEventListener('input', () => {
        paymentAmount.dataset.touched = 'true';
    });
    paymentMethod.addEventListener('change', () => {
        delete paymentAmount.dataset.touched;
        if (quote) paymentAmount.value = Number(quote.patient_due ?? 0).toFixed(2);
    });
    cashierRoot.querySelector('[data-open-session]')?.addEventListener('click', openSessionDialog);
    cashierRoot.querySelectorAll('[data-session-modal-close]').forEach((button) => button.addEventListener('click', closeSessionDialog));
    openModal.addEventListener('click', (event) => {
        if (event.target === openModal) closeSessionDialog();
    });
    cashierRoot.querySelector('[data-session-branch]')?.addEventListener('change', (event) => {
        selectedBranchId = event.target.value ? Number(event.target.value) : null;
        filterTreasuries();
        loadProducts(productSearch.value);
    });
    cashierRoot.querySelector('[data-session-treasury]')?.addEventListener('change', (event) => {
        const branchId = event.target.selectedOptions[0]?.dataset.branch;
        if (branchId) selectedBranchId = Number(branchId);
    });
    openForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorBox = cashierRoot.querySelector('[data-session-error]');
        errorBox.hidden = true;
        const submit = cashierRoot.querySelector('[data-open-session-submit]');
        submit.disabled = true;
        try {
            const formData = new FormData(openForm);
            const payload = {
                treasury_id: Number(formData.get('treasury_id')),
                opening_cash: Number(formData.get('opening_cash') || 0),
            };
            const branchId = formData.get('branch_id') || selectedBranchId;
            if (branchId) payload.branch_id = Number(branchId);
            const response = await request('/pharmacy/pos-sessions/open', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            activeSession = response.data;
            toast('تم فتح جلسة الكاشير بنجاح.');
            window.location.reload();
        } catch (error) {
            showError(errorBox, error);
        } finally {
            submit.disabled = false;
        }
    });
    cashierRoot.querySelector('[data-close-session]')?.addEventListener('click', () => {
        closeModal.hidden = false;
        document.body.classList.add('modal-visible');
    });
    cashierRoot.querySelectorAll('[data-close-session-modal]').forEach((button) => button.addEventListener('click', () => {
        closeModal.hidden = true;
        document.body.classList.remove('modal-visible');
    }));
    closeModal.addEventListener('click', (event) => {
        if (event.target === closeModal) {
            closeModal.hidden = true;
            document.body.classList.remove('modal-visible');
        }
    });
    closeForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const errorBox = cashierRoot.querySelector('[data-close-error]');
        errorBox.hidden = true;
        const submit = cashierRoot.querySelector('[data-close-session-submit]');
        submit.disabled = true;
        try {
            const formData = new FormData(closeForm);
            await request(`/pharmacy/pos-sessions/${encodeURIComponent(activeSession.id)}/close`, {
                method: 'POST',
                body: JSON.stringify({ actual_closing_cash: Number(formData.get('actual_closing_cash')) }),
            });
            document.addEventListener('keydown', (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                    event.preventDefault();
                    productSearch.focus();
                }
                if (event.key === 'Escape') {
                    if (!openModal.hidden) closeSessionDialog();
                    if (!closeModal.hidden) {
                        closeModal.hidden = true;
                        document.body.classList.remove('modal-visible');
                    }
                }
            });
            toast('تم إغلاق الجلسة وتسوية الوردية.');
            window.location.reload();
        } catch (error) {
            showError(errorBox, error);
        } finally {
            submit.disabled = false;
        }
    });
    checkoutButton.addEventListener('click', async () => {
        if (!activeSession || !quote || !cart.length) return;
        checkoutButton.disabled = true;
        checkoutButton.querySelector('span').textContent = 'جارٍ إتمام البيع...';
        closeError('[data-quote-error]');
        try {
            const due = Number(quote.patient_due ?? 0);
            const amount = Number(paymentAmount.value || 0);
            const payments = due <= 0 ? [] : [{
                payment_method: paymentMethod.value,
                amount: amount > 0 ? amount : due,
            }];
            const response = await request('/pharmacy/sales', {
                method: 'POST',
                body: JSON.stringify({
                    pos_session_id: activeSession.id,
                    customer_id: customerSelect.value ? Number(customerSelect.value) : null,
                    insurance_policy_id: policySelect.value ? Number(policySelect.value) : null,
                    invoice_discount: Number(discountInput.value || 0),
                    discount_reason: discountReason.value.trim() || null,
                    items: cart.map((item) => ({
                        medicine_id: item.medicine.id,
                        unit_id: Number(item.unit.unit_id),
                        quantity: Number(item.quantity),
                    })),
                    payments,
                }),
            });
            toast(`تمت الفاتورة ${response.data.invoice_number} بنجاح.`);
            cart = [];
            quote = null;
            discountInput.value = '0';
            discountReason.value = '';
            discountReason.hidden = true;
            delete paymentAmount.dataset.touched;
            paymentMethod.value = 'cash';
            renderCart();
            await loadProducts(productSearch.value);
        } catch (error) {
            showError(quoteError, error);
        } finally {
            checkoutButton.querySelector('span').textContent = 'تأكيد وإتمام البيع';
            checkoutButton.disabled = !activeSession || cart.length === 0 || !quote;
        }
    });

    refreshPolicyOptions();
    renderCart();
    loadProducts();
    if (!activeSession && config.treasuries.length > 0) {
        window.setTimeout(openSessionDialog, 500);
    }
}
