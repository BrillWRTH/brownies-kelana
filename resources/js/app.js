// Interaktivitas global Brownies Kelana (Drawer Keranjang, Dropdown, Modal Logout)
document.addEventListener('DOMContentLoaded', () => {
    const cartDrawer = document.getElementById('kelana-cart-drawer');
    const cartBackdrop = document.getElementById('kelana-cart-backdrop');
    const openCartButtons = document.querySelectorAll('[data-open-cart-drawer]');
    const closeCartButtons = document.querySelectorAll('[data-close-cart-drawer]');

    const toggleCartDrawer = (show) => {
        if (!cartDrawer || !cartBackdrop) {
            return;
        }
        if (show) {
            cartBackdrop.classList.remove('hidden');
            cartDrawer.classList.remove('translate-x-full');
            document.body.classList.add('overflow-hidden');
        } else {
            cartBackdrop.classList.add('hidden');
            cartDrawer.classList.add('translate-x-full');
            document.body.classList.remove('overflow-hidden');
        }
    };

    openCartButtons.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            toggleCartDrawer(true);
        });
    });

    closeCartButtons.forEach((btn) => {
        btn.addEventListener('click', () => toggleCartDrawer(false));
    });

    if (cartBackdrop) {
        cartBackdrop.addEventListener('click', () => toggleCartDrawer(false));
    }

    if (cartDrawer && cartDrawer.dataset.autoOpen === 'true') {
        toggleCartDrawer(true);
    }

    // Popover & Dropdown toggle sederhana
    document.querySelectorAll('[data-dropdown-toggle]').forEach((trigger) => {
        const targetId = trigger.getAttribute('data-dropdown-toggle');
        const targetEl = document.getElementById(targetId);
        if (!targetEl) {
            return;
        }

        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            document.querySelectorAll('[data-dropdown-menu]').forEach((menu) => {
                if (menu.id !== targetId) {
                    menu.classList.add('hidden');
                }
            });
            targetEl.classList.toggle('hidden');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown-menu]').forEach((menu) => {
            menu.classList.add('hidden');
        });
    });

    // Modal konfirmasi keluar (Customer & Admin)
    document.querySelectorAll('[data-open-modal]').forEach((trigger) => {
        const modalId = trigger.getAttribute('data-open-modal');
        const modalEl = document.getElementById(modalId);
        if (!modalEl) {
            return;
        }
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            modalEl.classList.remove('hidden');
            modalEl.classList.add('flex');
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((trigger) => {
        const modalId = trigger.getAttribute('data-close-modal');
        const modalEl = document.getElementById(modalId);
        if (!modalEl) {
            return;
        }
        trigger.addEventListener('click', () => {
            modalEl.classList.add('hidden');
            modalEl.classList.remove('flex');
        });
    });
});
