const sidebar = document.querySelector('#pharmacy-sidebar');
const mobileToggle = document.querySelector('[data-sidebar-toggle]');
const collapseToggle = document.querySelector('[data-sidebar-collapse-toggle]');
const closeButtons = document.querySelectorAll('[data-sidebar-close]');
const sidebarStorageKey = 'shifa-sidebar-collapsed';
const mobileBreakpoint = 900;
const i18n = window.pharmacyI18n ?? {};

const setSidebarCollapsed = (collapsed) => {
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    collapseToggle?.setAttribute('aria-pressed', String(collapsed));
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
            const isOpen = group.classList.toggle('is-open');
            button.setAttribute('aria-expanded', String(isOpen));
        });
    });
}

if (collapseToggle) {
    restoreSidebarState();
    collapseToggle.addEventListener('click', () => {
        const collapsed = !document.body.classList.contains('sidebar-collapsed');
        setSidebarCollapsed(collapsed);
        localStorage.setItem(sidebarStorageKey, String(collapsed));
    });
    window.addEventListener('resize', restoreSidebarState);
}

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
