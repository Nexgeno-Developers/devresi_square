(function () {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (window.gsap && window.ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
        if (!reduced) {
            bindScroll();
        }
    }

    bindDemo();


    function bindScroll() {
        let lenis = null;
        const desktop = window.matchMedia('(min-width: 960px)').matches;

        if (desktop && window.Lenis) {
            lenis = new Lenis({ duration: 1.05, smoothWheel: true });
            lenis.on('scroll', ScrollTrigger.update);
            gsap.ticker.add(function (time) {
                lenis.raf(time * 1000);
            });
            gsap.ticker.lagSmoothing(0);
            window.__landingLenis = lenis;
        }

        const motion = gsap.matchMedia();
        motion.add('(min-width: 960px)', function () {
            const screens = gsap.utils.toArray('.screen');
            const steps = gsap.utils.toArray('.step');
            const panel = document.querySelector('.product-stage .window');
            const nav = document.querySelector('.nav');

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

            function showChapter(index) {
                screens.forEach(function (screen, i) {
                    screen.style.opacity = i === index ? '1' : '0';
                });
                steps.forEach(function (step, i) {
                    step.style.opacity = i === index ? '1' : '0';
                });
            }

            showChapter(0);

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
                    const index = Math.min(screens.length - 1, Math.floor(self.progress * screens.length));
                    showChapter(index);
                    const fill = document.querySelector('.rail-fill');
                    if (fill) {
                        fill.style.transform = 'scaleY(' + Math.max(0.08, (index + 1) / screens.length) + ')';
                    }
                }
            });

            gsap.fromTo('.day-progress', { scaleY: 0 }, {
                scaleY: 1,
                transformOrigin: 'top center',
                ease: 'none',
                scrollTrigger: {
                    trigger: '.day',
                    start: 'top 72%',
                    end: 'bottom 70%',
                    scrub: true
                }
            });
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
