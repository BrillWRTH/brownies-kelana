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

    // Hero Slider Carousel Landing Page (Auto-slide 5 detik, slide mulus, sentuhan & jeda saat hover)
    const heroCarousel = document.getElementById('hero-carousel-container');
    const heroTrack = document.getElementById('hero-slider-track');
    const heroDots = Array.from(document.querySelectorAll('[data-hero-dot-index]'));
    const heroPrevBtn = document.getElementById('hero-prev-btn');
    const heroNextBtn = document.getElementById('hero-next-btn');

    if (heroTrack && heroDots.length > 0) {
        let currentSlide = 0;
        const totalSlides = heroDots.length;
        let autoSlideTimer = null;

        const updateDots = (activeIdx) => {
            heroDots.forEach((dot, i) => {
                if (i === activeIdx) {
                    dot.className = 'h-2.5 w-8 rounded-full bg-white transition-all duration-300';
                } else {
                    dot.className = 'h-2.5 w-2.5 rounded-full bg-white/50 hover:bg-white/80 transition-all duration-300';
                }
            });
        };

        const goToSlide = (idx) => {
            currentSlide = (idx + totalSlides) % totalSlides;
            heroTrack.style.transform = `translateX(-${currentSlide * 100}%)`;
            updateDots(currentSlide);
        };

        const startAutoSlide = () => {
            stopAutoSlide();
            autoSlideTimer = setInterval(() => {
                goToSlide(currentSlide + 1);
            }, 5000);
        };

        const stopAutoSlide = () => {
            if (autoSlideTimer) {
                clearInterval(autoSlideTimer);
                autoSlideTimer = null;
            }
        };

        const userTriggerSlide = (idx) => {
            goToSlide(idx);
            startAutoSlide(); // Reset timer setelah interaksi pengguna
        };

        heroPrevBtn?.addEventListener('click', () => userTriggerSlide(currentSlide - 1));
        heroNextBtn?.addEventListener('click', () => userTriggerSlide(currentSlide + 1));

        heroDots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const targetIdx = parseInt(dot.getAttribute('data-hero-dot-index') || '0', 10);
                userTriggerSlide(targetIdx);
            });
        });

        // Jeda auto slide saat mouse berada di atas banner
        heroCarousel?.addEventListener('mouseenter', stopAutoSlide);
        heroCarousel?.addEventListener('mouseleave', startAutoSlide);

        // Dukungan usapan layar sentuh di ponsel (Touch swipe)
        let touchStartX = 0;
        let touchEndX = 0;

        heroCarousel?.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoSlide();
        }, { passive: true });

        heroCarousel?.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const diffX = touchStartX - touchEndX;
            if (Math.abs(diffX) > 40) {
                if (diffX > 0) {
                    goToSlide(currentSlide + 1); // Swipe kiri -> next
                } else {
                    goToSlide(currentSlide - 1); // Swipe kanan -> prev
                }
            }
            startAutoSlide();
        }, { passive: true });

        // Mulai auto slide 5 detik pertama
        startAutoSlide();
    }
});
