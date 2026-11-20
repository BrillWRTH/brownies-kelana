import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// Toast SweetAlert2 Pop Up Pojok Kanan Atas dengan Tombol Close
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    showCloseButton: true,
    timer: 4500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});

window.Swal = Swal;
window.Toast = Toast;

window.showToast = (icon, title, html = null, timer = 4500) => {
    return Toast.fire({
        icon,
        title,
        html,
        showCloseButton: true,
        timer,
    });
};

// Pop-up SweetAlert untuk Melihat Bukti Pembayaran Tanpa Pindah Halaman
window.viewPaymentProof = (imageUrl, orderNumber, amount, date) => {
    Swal.fire({
        title: `Bukti Pembayaran ${orderNumber}`,
        html: `
            <div class="text-xs text-[#593822] mb-3">
                <span>Nominal: <strong>${amount}</strong></span>
                ${date ? '<span class="mx-1.5">&middot;</span><span>Waktu: ' + date + '</span>' : ''}
            </div>
            <div class="overflow-hidden rounded-xl border border-[#d9c8b4] bg-[#fcf3e3] p-1 shadow-inner">
                <img src="${imageUrl}" alt="Bukti Pembayaran ${orderNumber}" class="max-h-[60vh] w-auto mx-auto rounded-lg object-contain">
            </div>
            <div class="mt-3 flex justify-center">
                <a href="${imageUrl}" target="_blank" rel="noopener" class="text-[11px] font-bold text-[#8f6743] hover:text-[#593822] hover:underline">
                    Buka Gambar Penuh di Tab Baru &rarr;
                </a>
            </div>
        `,
        showCloseButton: true,
        showConfirmButton: true,
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#593822',
        customClass: {
            popup: 'rounded-2xl border border-[#d9c8b4] max-w-lg p-5',
            title: 'font-serif text-lg font-bold text-[#593822]',
            confirmButton: 'rounded-xl px-6 py-2 text-xs font-bold'
        }
    });
};

// Override native alert() agar selalu menggunakan SweetAlert toast di pojok kanan atas
window.alert = (message) => {
    Toast.fire({
        icon: 'info',
        title: message,
    });
};

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

    // Slider Kategori Produk Landing Page (Slide ke kanan & kiri)
    const catTrack = document.getElementById('category-slider-track');
    const catPrevBtn = document.getElementById('cat-prev-btn');
    const catNextBtn = document.getElementById('cat-next-btn');

    if (catTrack) {
        catPrevBtn?.addEventListener('click', () => {
            catTrack.scrollBy({ left: -340, behavior: 'smooth' });
        });
        catNextBtn?.addEventListener('click', () => {
            catTrack.scrollBy({ left: 340, behavior: 'smooth' });
        });
    }

    // Slider Ulasan / Testimoni Landing Page (Auto-slide + Tombol Manual + Swipe)
    const reviewTrack = document.getElementById('reviews-slider-track');
    const reviewPrevBtn = document.getElementById('review-prev-btn');
    const reviewNextBtn = document.getElementById('review-next-btn');
    const reviewDots = Array.from(document.querySelectorAll('[data-review-dot-index]'));
    const reviewContainer = document.getElementById('reviews-carousel-container');

    if (reviewTrack) {
        let reviewAutoTimer = null;
        const cardWidth = 350;

        const scrollReview = (direction) => {
            const maxScroll = reviewTrack.scrollWidth - reviewTrack.clientWidth;
            if (direction === 'next') {
                if (reviewTrack.scrollLeft >= maxScroll - 15) {
                    reviewTrack.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    reviewTrack.scrollBy({ left: cardWidth, behavior: 'smooth' });
                }
            } else {
                if (reviewTrack.scrollLeft <= 15) {
                    reviewTrack.scrollTo({ left: maxScroll, behavior: 'smooth' });
                } else {
                    reviewTrack.scrollBy({ left: -cardWidth, behavior: 'smooth' });
                }
            }
        };

        const startReviewAuto = () => {
            stopReviewAuto();
            reviewAutoTimer = setInterval(() => scrollReview('next'), 4000);
        };

        const stopReviewAuto = () => {
            if (reviewAutoTimer) {
                clearInterval(reviewAutoTimer);
                reviewAutoTimer = null;
            }
        };

        reviewNextBtn?.addEventListener('click', () => {
            scrollReview('next');
            startReviewAuto();
        });
        reviewPrevBtn?.addEventListener('click', () => {
            scrollReview('prev');
            startReviewAuto();
        });

        reviewDots.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                reviewTrack.scrollTo({ left: idx * cardWidth, behavior: 'smooth' });
                startReviewAuto();
            });
        });

        reviewContainer?.addEventListener('mouseenter', stopReviewAuto);
        reviewContainer?.addEventListener('mouseleave', startReviewAuto);

        // Update dots saat scroll
        reviewTrack.addEventListener('scroll', () => {
            const activeIdx = Math.min(
                reviewDots.length - 1,
                Math.round(reviewTrack.scrollLeft / cardWidth)
            );
            reviewDots.forEach((dot, i) => {
                if (i === activeIdx) {
                    dot.className = 'h-2.5 w-7 rounded-full bg-[#593822] transition-all duration-300';
                } else {
                    dot.className = 'h-2.5 w-2.5 rounded-full border border-[#593822] bg-transparent transition-all duration-300 hover:bg-[#593822]/40';
                }
            });
        }, { passive: true });

        startReviewAuto();
    }

    // Navigasi Breadcrumb untuk beralih antar Card agar tidak panjang ke bawah
    function initCardBreadcrumbs() {
        document.querySelectorAll('[data-card-breadcrumbs]').forEach((nav) => {
            const container = nav.closest('.card-breadcrumb-scope') || nav.parentElement;
            if (!container) return;

            const tabButtons = nav.querySelectorAll('[data-card-tab]');
            const panels = container.querySelectorAll('[data-card-panel]');

            const activateTab = (tabName) => {
                tabButtons.forEach((btn) => {
                    const isActive = btn.getAttribute('data-card-tab') === tabName;
                    btn.classList.toggle('bg-[#593822]', isActive);
                    btn.classList.toggle('text-white', isActive);
                    btn.classList.toggle('shadow-xs', isActive);
                    btn.classList.toggle('text-[#593822]', !isActive);
                    btn.classList.toggle('hover:bg-[#f7ebd5]', !isActive);

                    const numBadge = btn.querySelector('.step-num');
                    if (numBadge) {
                        numBadge.className = `step-num flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[11px] font-bold ${isActive ? 'bg-white/20 text-white' : 'bg-[#e8decb] text-[#593822]'}`;
                    }
                });

                panels.forEach((panel) => {
                    const isMatch = panel.getAttribute('data-card-panel') === tabName;
                    if (isMatch) {
                        panel.classList.remove('hidden');
                    } else {
                        panel.classList.add('hidden');
                    }
                });
            };

            // Global helper untuk tombol navigasi di dalam card
            window.switchCardBreadcrumb = (targetName) => {
                activateTab(targetName);
            };

            tabButtons.forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = btn.getAttribute('data-card-tab');
                    activateTab(target);
                });
            });

            // Tentukan card awal berdasarkan attribute data-default-tab
            const initialTab = nav.getAttribute('data-default-tab') || tabButtons[0]?.getAttribute('data-card-tab');
            if (initialTab) {
                activateTab(initialTab);
            }
        });
    }

    initCardBreadcrumbs();

    // Cegah lompat ke atas halaman saat beralih tab atau klik anchor in-page
    if (window.location.hash) {
        const targetEl = document.querySelector(window.location.hash);
        if (targetEl) {
            setTimeout(() => {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 80);
        }
    }
});
