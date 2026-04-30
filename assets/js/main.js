// CYNA — Scripts principaux

document.addEventListener('DOMContentLoaded', () => {

    // Auto-hide des alertes après 5 secondes
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // Burger menu mobile
    const burger    = document.getElementById('navBurger');
    const mobileNav = document.getElementById('navMobile');
    if (burger && mobileNav) {
        burger.addEventListener('click', () => {
            const isOpen = mobileNav.classList.toggle('open');
            burger.classList.toggle('open', isOpen);
            burger.setAttribute('aria-expanded', isOpen);
            mobileNav.setAttribute('aria-hidden', !isOpen);
        });
    }

    // Filtre prix slider ↔ input synchronisation
    const slider    = document.getElementById('priceSlider');
    const priceMax  = document.getElementById('priceMax');
    const priceDisp = document.getElementById('price_display');

    if (slider && priceMax) {
        slider.addEventListener('input', () => {
            priceMax.value = slider.value;
            if (priceDisp) priceDisp.textContent = parseInt(slider.value).toLocaleString('fr-FR') + ' €';
        });
        priceMax.addEventListener('input', () => {
            slider.value = priceMax.value;
            if (priceDisp) priceDisp.textContent = parseInt(priceMax.value).toLocaleString('fr-FR') + ' €';
        });
    }

    // Rétrocompat : price_max sans slider
    const priceRange   = document.getElementById('price_max');
    const priceDisplay = document.getElementById('price_display');
    if (priceRange && priceDisplay && !slider) {
        priceRange.addEventListener('input', () => {
            priceDisplay.textContent = parseInt(priceRange.value).toLocaleString('fr-FR') + ' €';
        });
    }

    // ---- Carousel homepage ----
    const slides  = document.querySelectorAll('.carousel-slide');
    const dots    = document.querySelectorAll('.carousel-dot');
    const prevBtn = document.getElementById('carouselPrev');
    const nextBtn = document.getElementById('carouselNext');

    if (slides.length > 1) {
        let current = 0;
        let timer   = null;

        function goTo(idx) {
            slides[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');
            current = (idx + slides.length) % slides.length;
            slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');
        }

        function startAuto() { timer = setInterval(() => goTo(current + 1), 5000); }
        function resetAuto() { clearInterval(timer); startAuto(); }

        if (prevBtn) prevBtn.addEventListener('click', () => { goTo(current - 1); resetAuto(); });
        if (nextBtn) nextBtn.addEventListener('click', () => { goTo(current + 1); resetAuto(); });
        dots.forEach((d, i) => d.addEventListener('click', () => { goTo(i); resetAuto(); }));

        // Swipe tactile
        const carousel = document.getElementById('carousel');
        if (carousel) {
            let startX = 0;
            carousel.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
            carousel.addEventListener('touchend',   e => {
                const diff = startX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) { goTo(diff > 0 ? current + 1 : current - 1); resetAuto(); }
            });
        }

        startAuto();
    }

    // ---- Quantité panier +/- ----
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.closest('.qty-wrap').querySelector('.qty-input');
            const delta = btn.dataset.delta === '+' ? 1 : -1;
            const val   = Math.max(1, (parseInt(input.value) || 1) + delta);
            input.value = val;
            btn.closest('form').submit();
        });
    });

    // ---- Scroll reveal ----
    const revealEls = document.querySelectorAll('[data-reveal]');
    if (revealEls.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach(el => observer.observe(el));
    }

    // ---- Animated counters ----
    const counterEls = document.querySelectorAll('[data-count]');
    if (counterEls.length) {
        const countObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el     = entry.target;
                const target = parseFloat(el.dataset.count);
                const suffix = el.dataset.suffix || '';
                const prefix = el.dataset.prefix || '';
                const dec    = el.dataset.dec || 0;
                const dur    = 1400;
                const start  = performance.now();
                function step(now) {
                    const pct = Math.min((now - start) / dur, 1);
                    const ease = 1 - Math.pow(1 - pct, 3);
                    el.textContent = prefix + (target * ease).toFixed(dec) + suffix;
                    if (pct < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
                countObserver.unobserve(el);
            });
        }, { threshold: 0.5 });
        counterEls.forEach(el => countObserver.observe(el));
    }

});
