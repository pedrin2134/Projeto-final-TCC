(function () {
    const banner = document.querySelector('[data-login-banner]');
    if (!banner) {
        return;
    }

    const slides = Array.from(banner.querySelectorAll('[data-banner-slide]'));
    const dots = Array.from(banner.querySelectorAll('[data-banner-go]'));
    if (slides.length < 2) {
        return;
    }

    let activeIndex = 0;
    let timer = null;

    function showSlide(index) {
        activeIndex = (index + slides.length) % slides.length;
        slides.forEach(function (slide, slideIndex) {
            const active = slideIndex === activeIndex;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });
        dots.forEach(function (dot, dotIndex) {
            const active = dotIndex === activeIndex;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-current', active ? 'true' : 'false');
        });
    }

    function stopRotation() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function startRotation() {
        stopRotation();
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            timer = window.setInterval(function () {
                showSlide(activeIndex + 1);
            }, 6500);
        }
    }

    const previous = banner.querySelector('[data-banner-previous]');
    const next = banner.querySelector('[data-banner-next]');
    if (previous) {
        previous.addEventListener('click', function () {
            showSlide(activeIndex - 1);
            startRotation();
        });
    }
    if (next) {
        next.addEventListener('click', function () {
            showSlide(activeIndex + 1);
            startRotation();
        });
    }
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            showSlide(Number(dot.dataset.bannerGo));
            startRotation();
        });
    });

    banner.addEventListener('mouseenter', stopRotation);
    banner.addEventListener('mouseleave', startRotation);
    banner.addEventListener('focusin', stopRotation);
    banner.addEventListener('focusout', function (event) {
        if (!banner.contains(event.relatedTarget)) {
            startRotation();
        }
    });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopRotation();
        } else {
            startRotation();
        }
    });

    startRotation();
})();
