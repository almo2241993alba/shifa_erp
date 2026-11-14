const sidebar = document.querySelector('#pharmacy-sidebar');
const toggle = document.querySelector('[data-sidebar-toggle]');
const closeButtons = document.querySelectorAll('[data-sidebar-close], [data-sidebar-close] *');

if (sidebar && toggle) {
    const closeSidebar = () => {
        sidebar.classList.remove('sidebar-open');
        document.body.classList.remove('sidebar-visible');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('sidebar-open');
        document.body.classList.toggle('sidebar-visible', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeSidebar));
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeSidebar));
}
