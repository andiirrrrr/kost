const menuButton = document.querySelector('[data-mobile-menu-button]');
const mobileMenu = document.querySelector('[data-mobile-menu]');
const mobileMenuPanel = mobileMenu?.querySelector('[data-mobile-menu-panel]');
const mobileMenuBackdrop = mobileMenu?.querySelector('[data-mobile-menu-backdrop]');

const setMobileMenuOpen = (isOpen) => {
    menuButton?.setAttribute('aria-expanded', String(isOpen));
    mobileMenu?.setAttribute('aria-hidden', String(! isOpen));
    mobileMenu?.classList.toggle('pointer-events-none', ! isOpen);
    mobileMenuPanel?.classList.toggle('translate-x-full', ! isOpen);
    mobileMenuBackdrop?.classList.toggle('opacity-0', ! isOpen);
    document.body.classList.toggle('overflow-hidden', isOpen);
};

menuButton?.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';

    setMobileMenuOpen(! isOpen);
});

mobileMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        setMobileMenuOpen(false);
    });
});

mobileMenu?.querySelectorAll('[data-mobile-menu-close], [data-mobile-menu-backdrop]').forEach((button) => {
    button.addEventListener('click', () => setMobileMenuOpen(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menuButton?.getAttribute('aria-expanded') === 'true') {
        setMobileMenuOpen(false);
        menuButton.focus();
    }
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (! input) {
            return;
        }

        const isVisible = input.type === 'text';
        input.type = isVisible ? 'password' : 'text';
        button.setAttribute('aria-pressed', String(! isVisible));
        button.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');

        const icon = button.querySelector('.material-symbols-outlined');
        if (icon) {
            icon.textContent = isVisible ? 'visibility' : 'visibility_off';
        }
    });
});
