// Interaktivitas global Brownies Kelana (Drawer Keranjang, Dropdown, Modal, Hero Carousel, Toggle Sandi)
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

    // Modal konfirmasi keluar / batal (Customer & Admin)
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

    // Toggle lihat / sembunyikan kata sandi pada input password
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        const inputId = btn.getAttribute('data-toggle-password');
        const inputEl = document.getElementById(inputId);
        if (!inputEl) {
            return;
        }
        btn.addEventListener('click', () => {
            const isHidden = inputEl.type === 'password';
            inputEl.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        });
    });

    // Hero Slider Landing Page
    const heroSlides = Array.from(document.querySelectorAll('[data-hero-slide]'));
    const heroDots = Array.from(document.querySelectorAll('[data-hero-dot]'));
    const heroPrev = document.querySelector('[data-hero-prev]');
    const heroNext = document.querySelector('[data-hero-next]');
    if (heroSlides.length > 0) {
        let currentSlide = 0;
        const showSlide = (idx) => {
            currentSlide = (idx + heroSlides.length) % heroSlides.length;
            heroSlides.forEach((slide, i) => {
                slide.classList.toggle('hidden', i !== currentSlide);
            });
            heroDots.forEach((dot, i) => {
                dot.classList.toggle('bg-white', i === currentSlide);
                dot.classList.toggle('bg-white/45', i !== currentSlide);
            });
        };
        heroPrev?.addEventListener('click', () => showSlide(currentSlide - 1));
        heroNext?.addEventListener('click', () => showSlide(currentSlide + 1));
        heroDots.forEach((dot, i) => dot.addEventListener('click', () => showSlide(i)));
    }
});
