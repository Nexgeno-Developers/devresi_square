(function () {
    if (window.gsap && window.ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
        gsap.defaults({ ease: 'power2.out', duration: 0.65 });
        bindScroll();
    }

    bindNav();
    bindDemo();


    function bindNav() {
        const nav = document.querySelector('.nav');
        if (!nav) {
            return;
        }
        const paint = function () {
            nav.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        paint();
        window.addEventListener('scroll', paint, { passive: true });
    }

    function reveal(targets, trigger, stagger) {
        gsap.from(targets, {
            y: 22,
            autoAlpha: 0,
            duration: 0.6,
            stagger: stagger || 0,
            clearProps: 'transform,opacity,visibility',
            scrollTrigger: {
                trigger: trigger,
                start: 'top 86%',
                once: true
            }
        });
    }

    function bindScroll() {
        let lenis = null;
        const motion = gsap.matchMedia();

        motion.add({
            allow: '(prefers-reduced-motion: no-preference)',
            desktop: '(min-width: 960px)'
        }, function (context) {
            if (!context.conditions.allow) {
                return;
            }

            const desktop = context.conditions.desktop;
            let tick = null;
            if (desktop && window.Lenis) {
                lenis = new Lenis({ duration: 1.05, smoothWheel: true });
                lenis.on('scroll', ScrollTrigger.update);
                tick = function (time) {
                    lenis.raf(time * 1000);
                };
                gsap.ticker.add(tick);
                gsap.ticker.lagSmoothing(0);
                window.__landingLenis = lenis;
            }

            const intro = gsap.timeline({ defaults: { ease: 'power3.out' } });
            intro
                .from('.nav-inner > *', { y: -12, autoAlpha: 0, duration: 0.45, stagger: 0.06, clearProps: 'transform,opacity,visibility' }, 0)
                .from('.hero-copy .eyebrow', { y: 14, autoAlpha: 0, duration: 0.45, clearProps: 'transform,opacity,visibility' }, 0.08)
                .from('.hero-copy h1', { y: 26, autoAlpha: 0, duration: 0.75, clearProps: 'transform,opacity,visibility' }, 0.14)
                .from('.hero-copy .lede', { y: 16, autoAlpha: 0, duration: 0.55, clearProps: 'transform,opacity,visibility' }, 0.28)
                .from('.hero-copy .hero-actions .btn', { y: 12, autoAlpha: 0, duration: 0.4, stagger: 0.08, clearProps: 'transform,opacity,visibility' }, 0.4)
                .from('.hero-copy .fine', { autoAlpha: 0, duration: 0.4, clearProps: 'opacity,visibility' }, 0.52);

            reveal('.spec li', '.spec', 0.07);
            reveal('.cost .section-head', '.cost', 0);
            reveal('.cost-list li', '.cost-list', 0.1);
            reveal('.day .section-head', '.day', 0);
            reveal('.day-list li', '.day-list', 0.12);
            reveal('.close-grid > *', '.close', 0.08);

            gsap.fromTo('.day-progress', { scaleY: 0 }, {
                scaleY: 1,
                transformOrigin: 'top center',
                ease: 'none',
                scrollTrigger: {
                    trigger: '.day',
                    start: 'top 75%',
                    end: 'bottom 70%',
                    scrub: 0.4
                }
            });

            if (!desktop) {
                return function () {
                    if (tick) {
                        gsap.ticker.remove(tick);
                    }
                    if (lenis) {
                        lenis.destroy();
                        lenis = null;
                        window.__landingLenis = null;
                    }
                };
            }

            const screens = gsap.utils.toArray('.screen');
            const steps = gsap.utils.toArray('.step');
            const panel = document.querySelector('.product-stage .window');
            const nav = document.querySelector('.nav');
            const fill = document.querySelector('.rail-fill');
            let chapter = 0;

            gsap.set(screens.slice(1).concat(steps.slice(1)), { autoAlpha: 0, y: 12 });
            gsap.set([screens[0], steps[0]], { autoAlpha: 1, y: 0 });

            function showChapter(index) {
                if (index === chapter || !screens[index]) {
                    return;
                }
                const previous = chapter;
                chapter = index;
                gsap.to([screens[previous], steps[previous]], {
                    autoAlpha: 0,
                    y: -10,
                    duration: 0.32,
                    ease: 'power2.out',
                    overwrite: true
                });
                gsap.fromTo([screens[index], steps[index]], { autoAlpha: 0, y: 16 }, {
                    autoAlpha: 1,
                    y: 0,
                    duration: 0.45,
                    ease: 'power2.out',
                    overwrite: true
                });
                if (fill) {
                    gsap.to(fill, {
                        scaleY: Math.max(0.08, (index + 1) / screens.length),
                        transformOrigin: 'top center',
                        duration: 0.45,
                        ease: 'power2.out',
                        overwrite: true
                    });
                }
            }

            if (panel) {
                gsap.set(panel, { transformPerspective: 1600, rotateY: -11, rotateX: 4 });
                gsap.to(panel, {
                    rotateY: 0,
                    rotateX: 0,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: '.product-stage',
                        start: 'top bottom',
                        end: 'top 72px',
                        scrub: true
                    }
                });
            }

            ScrollTrigger.create({
                trigger: '.product-stage',
                start: function () {
                    return 'top ' + (nav ? nav.offsetHeight : 72) + 'px';
                },
                end: '+=360%',
                pin: true,
                anticipatePin: 1,
                invalidateOnRefresh: true,
                onUpdate: function (self) {
                    showChapter(Math.min(screens.length - 1, Math.floor(self.progress * screens.length)));
                }
            });

            return function () {
                if (tick) {
                    gsap.ticker.remove(tick);
                }
                if (lenis) {
                    lenis.destroy();
                    lenis = null;
                    window.__landingLenis = null;
                }
            };
        });

        document.querySelectorAll('a[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                const id = link.getAttribute('href');
                const target = id && id.length > 1 ? document.querySelector(id) : null;
                if (!target) {
                    return;
                }
                event.preventDefault();
                if (lenis) {
                    lenis.scrollTo(target, { offset: -84 });
                } else {
                    target.scrollIntoView();
                }
            });
        });

        window.addEventListener('load', function () {
            ScrollTrigger.refresh();
        });
    }

    function bindDemo() {
        const dialog = document.getElementById('demo-dialog');
        if (!dialog) {
            return;
        }
        const form = dialog.querySelector('form');
        const error = form.querySelector('.form-error');
        const success = form.querySelector('.form-success');
        const date = form.querySelector('[name="demo_date"]');
        if (date) {
            const now = new Date();
            date.min = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        }

        document.querySelectorAll('[data-demo-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (window.__landingLenis) {
                    window.__landingLenis.stop();
                }
                dialog.showModal();
            });
        });

        dialog.querySelector('[data-demo-close]').addEventListener('click', function () {
            dialog.close();
        });

        dialog.addEventListener('close', function () {
            if (window.__landingLenis) {
                window.__landingLenis.start();
            }
        });

        dialog.addEventListener('click', function (event) {
            const rect = dialog.getBoundingClientRect();
            const inside = event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom;
            if (!inside) {
                dialog.close();
            }
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            error.hidden = true;
            success.hidden = true;
            const submit = form.querySelector('[type="submit"]');
            submit.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: new FormData(form)
                });
                const data = await response.json().catch(function () { return {}; });
                if (!response.ok) {
                    const messages = data.errors ? Object.values(data.errors).flat() : ['Please check the form and try again.'];
                    error.textContent = messages.join(' ');
                    error.hidden = false;
                    return;
                }
                form.reset();
                success.hidden = false;
            } catch (requestError) {
                error.textContent = 'The request did not go through. Please try again.';
                error.hidden = false;
            } finally {
                submit.disabled = false;
            }
        });
    }
})();
