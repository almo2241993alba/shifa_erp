const sidebar = document.querySelector('#pharmacy-sidebar');
const mobileToggle = document.querySelector('[data-sidebar-toggle]');
const collapseToggle = document.querySelector('[data-sidebar-collapse-toggle]');
const closeButtons = document.querySelectorAll('[data-sidebar-close]');
const sidebarStorageKey = 'shifa-sidebar-collapsed';
const mobileBreakpoint = 900;
const i18n = window.pharmacyI18n ?? {};

const isFlyoutEnabled = () => window.innerWidth > mobileBreakpoint
    && !document.body.classList.contains('sidebar-collapsed');

const positionFlyout = (group) => {
    const button = group.querySelector('[data-sidebar-group-toggle]');
    const panel = group.querySelector('.nav-group-links');
    if (!button || !panel) return;

    const buttonRect = button.getBoundingClientRect();
    const panelWidth = panel.getBoundingClientRect().width;
    const panelHeight = panel.getBoundingClientRect().height;
    const rtl = document.documentElement.dir === 'rtl';
    const desiredLeft = rtl ? buttonRect.left - panelWidth - 8 : buttonRect.right + 8;
    const left = Math.min(Math.max(8, desiredLeft), window.innerWidth - panelWidth - 8);
    const top = Math.min(Math.max(8, buttonRect.top), window.innerHeight - panelHeight - 8);

    panel.style.left = `${left}px`;
    panel.style.right = 'auto';
    panel.style.top = `${top}px`;
};

const closeFlyouts = () => {
    sidebar?.querySelectorAll('.nav-group.is-flyout').forEach((group) => {
        group.classList.remove('is-flyout', 'is-open');
        group.querySelector('[data-sidebar-group-toggle]')?.setAttribute('aria-expanded', 'false');
    });
};

const syncFlyouts = () => {
    sidebar?.querySelectorAll('.nav-group').forEach((group) => {
        if (isFlyoutEnabled() && group.classList.contains('is-open')) {
            group.classList.add('is-flyout');
            positionFlyout(group);
        } else {
            group.classList.remove('is-flyout');
        }
    });
};

const setSidebarCollapsed = (collapsed) => {
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    collapseToggle?.setAttribute('aria-pressed', String(collapsed));
    if (collapsed) closeFlyouts();
    if (collapseToggle) {
        const label = collapsed ? i18n.expandSidebar : i18n.collapseSidebar;
        collapseToggle.setAttribute('aria-label', label);
        collapseToggle.title = label;
    }
};

const restoreSidebarState = () => {
    setSidebarCollapsed(window.innerWidth > mobileBreakpoint && localStorage.getItem(sidebarStorageKey) === 'true');
};

if (sidebar && mobileToggle) {
    const closeSidebar = () => {
        sidebar.classList.remove('sidebar-open');
        document.body.classList.remove('sidebar-visible');
        mobileToggle.setAttribute('aria-expanded', 'false');
    };

    mobileToggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('sidebar-open');
        document.body.classList.toggle('sidebar-visible', isOpen);
        mobileToggle.setAttribute('aria-expanded', String(isOpen));
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeSidebar));
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
        if (window.innerWidth <= mobileBreakpoint) closeSidebar();
    }));

    sidebar.querySelectorAll('[data-sidebar-group-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.closest('[data-sidebar-group]');
            const isOpen = !group.classList.contains('is-open');
            closeFlyouts();
            group.classList.toggle('is-open', isOpen);
            button.setAttribute('aria-expanded', String(isOpen));
            if (isOpen && isFlyoutEnabled()) {
                group.classList.add('is-flyout');
                positionFlyout(group);
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!sidebar.contains(event.target)) closeFlyouts();
    });
    sidebar.addEventListener('scroll', closeFlyouts);
}

if (collapseToggle) {
    restoreSidebarState();
    collapseToggle.addEventListener('click', () => {
        const collapsed = !document.body.classList.contains('sidebar-collapsed');
        setSidebarCollapsed(collapsed);
        syncFlyouts();
        localStorage.setItem(sidebarStorageKey, String(collapsed));
    });
    window.addEventListener('resize', () => {
        restoreSidebarState();
        syncFlyouts();
    });
}

syncFlyouts();
window.addEventListener('scroll', closeFlyouts, { passive: true });

document.querySelectorAll('[data-user-menu]').forEach((menu) => {
    const toggle = menu.querySelector('[data-user-menu-toggle]');
    const panel = menu.querySelector('[data-user-menu-panel]');
    if (!toggle || !panel) return;

    const close = () => {
        menu.classList.remove('is-open');
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        const isOpen = panel.hidden;
        panel.hidden = !isOpen;
        menu.classList.toggle('is-open', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });
});
