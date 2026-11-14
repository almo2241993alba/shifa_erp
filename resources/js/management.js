const root = document.querySelector('[data-management-root]');

if (root) {
    const config = JSON.parse(root.dataset.config);
    const currency = root.dataset.currency;
    const tableHead = root.querySelector('[data-table-head]');
    const tableBody = root.querySelector('[data-table-body]');
    const countLabel = root.querySelector('[data-count]');
    const pagination = root.querySelector('[data-pagination]');
    const searchForm = root.querySelector('[data-search-form]');
    const searchInput = root.querySelector('[data-search]');
    const modal = root.querySelector('[data-modal]');
    const form = root.querySelector('[data-resource-form]');
    const fieldsRoot = root.querySelector('[data-fields]');
    const modalTitle = root.querySelector('#resource-modal-title');
    const formError = root.querySelector('[data-form-error]');
    const saveButton = root.querySelector('[data-save]');
    const toastRegion = root.querySelector('[data-toast-region]');
    let activeRecord = null;
    let currentPage = 1;
    let searchTimer;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const getValue = (record, path) => path.split('.').reduce((value, key) => {
        if (Array.isArray(value)) return value[Number(key)];
        return value?.[key];
    }, record);

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

        if (response.status === 204) return null;
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(payload.message ?? 'تعذر إكمال الطلب. حاول مرة أخرى.');
            error.payload = payload;
            throw error;
        }
        return payload;
    };

    const showToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toastRegion.append(toast);
        window.setTimeout(() => toast.remove(), 3600);
    };

    const formatValue = (column, value) => {
        if (value === null || value === undefined || value === '') return '—';
        if (column.format === 'boolean') return value ? 'نعم' : 'لا';
        if (column.format === 'status') {
            const active = ['1', 'true', 'active', 'completed', 'paid', 'approved', 'received'].includes(String(value).toLowerCase());
            const labels = {
                active: 'نشط',
                inactive: 'غير نشط',
                completed: 'مكتملة',
                pending: 'قيد المراجعة',
                approved: 'معتمدة',
                rejected: 'مرفوضة',
                paid: 'مدفوعة',
                partial: 'مدفوعة جزئيًا',
                unpaid: 'غير مدفوعة',
                received: 'مستلمة',
                open: 'مفتوحة',
                closed: 'مغلقة',
            };
            const label = labels[String(value).toLowerCase()] ?? (value ? 'نشط' : 'غير نشط');
            return `<span class="status-badge ${active ? 'status-success' : 'status-neutral'}">${escapeHtml(label)}</span>`;
        }
        if (column.format === 'money') {
            return `${new Intl.NumberFormat('ar', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value))} ${escapeHtml(currency)}`;
        }
        if (column.format === 'date') {
            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? escapeHtml(value) : new Intl.DateTimeFormat('ar', { dateStyle: 'medium' }).format(date);
        }
        if (typeof value === 'boolean') return value ? 'نعم' : 'لا';
        return escapeHtml(value);
    };

    const renderHeaders = () => {
        tableHead.replaceChildren();
        config.columns.forEach((column) => {
            const th = document.createElement('th');
            th.textContent = column.label;
            tableHead.append(th);
        });
        if (config.writable) {
            const th = document.createElement('th');
            th.textContent = 'إجراءات';
            tableHead.append(th);
        }
    };

    const renderRows = (records) => {
        tableBody.replaceChildren();
        if (!records.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = config.columns.length + (config.writable ? 1 : 0);
            cell.innerHTML = '<div class="empty-state compact-empty"><span class="empty-check">✓</span><strong>لا توجد سجلات مطابقة</strong><small>جرّب تغيير عبارة البحث أو أضف سجلًا جديدًا.</small></div>';
            row.append(cell);
            tableBody.append(row);
            return;
        }

        records.forEach((record) => {
            const row = document.createElement('tr');
            config.columns.forEach((column, index) => {
                const cell = document.createElement('td');
                const value = getValue(record, column.key);
                if (column.format === 'status') {
                    cell.innerHTML = formatValue(column, value);
                } else {
                    cell.textContent = formatValue(column, value).replace(/<[^>]*>/g, '');
                }
                if (index === 0) cell.classList.add('table-primary-cell');
                row.append(cell);
            });
            if (config.writable) {
                const cell = document.createElement('td');
                cell.className = 'table-actions';
                cell.innerHTML = `<button type="button" class="table-action" data-edit="${escapeHtml(record.id)}">تعديل</button><button type="button" class="table-action table-action-danger" data-delete="${escapeHtml(record.id)}">حذف</button>`;
                row.append(cell);
            }
            tableBody.append(row);
        });
    };

    const renderPagination = (meta) => {
        pagination.replaceChildren();
        if (!meta || meta.last_page <= 1) return;
        const info = document.createElement('span');
        info.textContent = `صفحة ${meta.current_page} من ${meta.last_page}`;
        pagination.append(info);
        const controls = document.createElement('div');
        controls.className = 'pagination-controls';
        [
            { label: 'السابق', page: meta.current_page - 1, disabled: meta.current_page <= 1 },
            { label: 'التالي', page: meta.current_page + 1, disabled: meta.current_page >= meta.last_page },
        ].forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'button button-secondary button-small';
            button.textContent = item.label;
            button.disabled = item.disabled;
            button.dataset.page = String(item.page);
            controls.append(button);
        });
        pagination.append(controls);
    };

    const loadRecords = async () => {
        tableBody.innerHTML = `<tr><td class="table-loading" colspan="${config.columns.length + (config.writable ? 1 : 0)}"><span class="loading-spinner"></span> جارٍ تحميل البيانات...</td></tr>`;
        const params = new URLSearchParams({ per_page: '15', page: String(currentPage) });
        if (searchInput.value.trim()) params.set('search', searchInput.value.trim());

        try {
            const result = await request(`${config.endpoint}?${params.toString()}`);
            renderRows(result.data ?? []);
            countLabel.textContent = `${new Intl.NumberFormat('ar').format(result.meta?.total ?? result.data?.length ?? 0)} سجل`;
            renderPagination(result.meta);
        } catch (error) {
            tableBody.innerHTML = `<tr><td class="table-error" colspan="${config.columns.length + (config.writable ? 1 : 0)}">${escapeHtml(error.message)}</td></tr>`;
            countLabel.textContent = 'تعذر تحميل البيانات';
        }
    };

    const renderField = async (field, record) => {
        const wrapper = document.createElement('div');
        wrapper.className = `form-field ${field.type === 'textarea' ? 'form-field-wide' : ''}`;
        if (field.type === 'hidden') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = field.name;
            input.value = record?.[field.name] ?? field.default ?? '';
            wrapper.append(input);
            return wrapper;
        }

        const label = document.createElement('label');
        label.className = 'field-label';
        label.htmlFor = `field-${field.name}`;
        label.textContent = field.label ?? '';
        wrapper.append(label);

        let input;
        if (field.type === 'select') {
            input = document.createElement('select');
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'اختر قيمة';
            input.append(empty);
            const optionRows = field.options
                ? Object.entries(field.options).map(([value, labelText]) => ({ value, label: labelText }))
                : (await request(field.options_endpoint)).data.map((row) => ({
                    value: getValue(row, field.option_value ?? 'id'),
                    label: getValue(row, field.option_label ?? 'name'),
                }));
            optionRows.forEach((optionRow) => {
                const option = document.createElement('option');
                option.value = optionRow.value;
                option.textContent = optionRow.label;
                input.append(option);
            });
        } else if (field.type === 'textarea') {
            input = document.createElement('textarea');
            input.rows = 3;
        } else {
            input = document.createElement('input');
            input.type = field.type === 'checkbox' ? 'checkbox' : field.type;
            if (field.min !== undefined) input.min = field.min;
            if (field.type !== 'checkbox') input.step = field.step ?? (field.type === 'number' ? 'any' : '');
        }

        input.id = `field-${field.name}`;
        input.name = field.name;
        input.required = Boolean(field.required);
        input.dataset.fieldType = field.type;
        if (field.type === 'checkbox') {
            input.checked = record ? Boolean(record[field.name]) : Boolean(field.default);
            wrapper.classList.add('checkbox-field');
            label.htmlFor = input.id;
            wrapper.replaceChildren(input, label);
        } else {
            const currentValue = record?.[field.name] ?? field.default ?? '';
            if (field.type !== 'select' || currentValue !== '') input.value = currentValue;
            wrapper.append(input);
        }

        return wrapper;
    };

    const openModal = async (record = null) => {
        activeRecord = record;
        modalTitle.textContent = record ? 'تعديل البيانات' : 'إضافة سجل جديد';
        formError.hidden = true;
        fieldsRoot.replaceChildren();
        modal.hidden = false;
        document.body.classList.add('modal-visible');

        try {
            const fields = await Promise.all(config.fields.map((field) => renderField(field, record)));
            fields.forEach((field) => fieldsRoot.append(field));
            modal.querySelector('input:not([type="hidden"]), select, textarea')?.focus();
        } catch (error) {
            formError.textContent = error.message;
            formError.hidden = false;
        }
    };

    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('modal-visible');
        form.reset();
        activeRecord = null;
    };

    const showError = (error) => {
        const errors = error.payload?.errors;
        if (errors) {
            formError.textContent = Object.values(errors).flat().join(' · ');
        } else {
            formError.textContent = error.message;
        }
        formError.hidden = false;
    };

    const readForm = () => {
        const data = {};
        form.querySelectorAll('[name]').forEach((input) => {
            if (input.type === 'checkbox') {
                data[input.name] = input.checked;
            } else if (input.value !== '') {
                data[input.name] = input.value;
            }
        });
        return data;
    };

    renderHeaders();
    loadRecords();

    root.querySelector('[data-create]')?.addEventListener('click', () => openModal());
    root.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', closeModal));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        currentPage = 1;
        loadRecords();
    });
    searchInput.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            currentPage = 1;
            loadRecords();
        }, 300);
    });
    pagination.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (!button || button.disabled) return;
        currentPage = Number(button.dataset.page);
        loadRecords();
    });
    tableBody.addEventListener('click', async (event) => {
        const editButton = event.target.closest('[data-edit]');
        if (editButton) {
            try {
                const result = await request(`${config.endpoint}/${encodeURIComponent(editButton.dataset.edit)}`);
                await openModal(result.data);
            } catch (error) {
                showToast(error.message, 'error');
            }
            return;
        }

        const deleteButton = event.target.closest('[data-delete]');
        if (!deleteButton) return;
        if (!window.confirm('هل تريد حذف هذا السجل؟ لا يمكن التراجع عن هذا الإجراء.')) return;
        try {
            await request(`${config.endpoint}/${encodeURIComponent(deleteButton.dataset.delete)}`, { method: 'DELETE' });
            showToast('تم حذف السجل بنجاح.');
            loadRecords();
        } catch (error) {
            showToast(error.message, 'error');
        }
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        formError.hidden = true;
        saveButton.disabled = true;
        saveButton.textContent = 'جارٍ الحفظ...';
        try {
            const endpoint = activeRecord
                ? `${config.endpoint}/${encodeURIComponent(activeRecord.id)}`
                : config.endpoint;
            await request(endpoint, {
                method: activeRecord ? 'PUT' : 'POST',
                body: JSON.stringify(readForm()),
            });
            closeModal();
            showToast(activeRecord ? 'تم تحديث السجل بنجاح.' : 'تمت إضافة السجل بنجاح.');
            loadRecords();
        } catch (error) {
            showError(error);
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = 'حفظ البيانات';
        }
    });
}
