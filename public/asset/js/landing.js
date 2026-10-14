(function () {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const scene = createMassing();

    if (window.gsap && window.ScrollTrigger) {
        gsap.registerPlugin(ScrollTrigger);
        if (!reduced) {
            bindScroll(scene);
        }
    }

    bindDemo();

    function createMassing() {
        const canvas = document.getElementById('massing');
        const fallback = document.querySelector('.massing-fallback');
        if (!canvas || !window.THREE) {
            if (fallback) fallback.hidden = false;
            return null;
        }

        let renderer;
        try {
            renderer = new THREE.WebGLRenderer({
                canvas: canvas,
                alpha: true,
                antialias: true,
                powerPreference: 'high-performance'
            });
        } catch (error) {
            if (fallback) fallback.hidden = false;
            canvas.hidden = true;
            return null;
        }

        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
        renderer.setClearColor(0x000000, 0);
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.12;
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 60);
        const group = new THREE.Group();
        scene.add(group);

        const stone = new THREE.MeshStandardMaterial({ color: 0xeeeae4, roughness: 0.86, metalness: 0 });
        const joint = new THREE.MeshStandardMaterial({ color: 0xd8d2c8, roughness: 0.8, metalness: 0 });
        const glass = new THREE.MeshStandardMaterial({ color: 0x243044, roughness: 0.28, metalness: 0.18 });
        const occupied = new THREE.MeshStandardMaterial({
            color: 0xffb088,
            emissive: 0xff5c1d,
            emissiveIntensity: 0.7,
            roughness: 0.4
        });
        const doorMat = new THREE.MeshStandardMaterial({ color: 0x2c3448, roughness: 0.62, metalness: 0.04 });

        function add(w, h, d, x, y, z, material) {
            const mesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), material);
            mesh.position.set(x, y, z);
            mesh.castShadow = true;
            mesh.receiveShadow = true;
            group.add(mesh);
            return mesh;
        }

        const width = 7.4;
        const height = 4.15;
        const depth = 3.35;
        const base = 0.16;
        const floors = [1.02, 1.95, 2.88, 3.78];
        const bays = 6;
        const bay = width / bays;

        add(width + 0.55, base, depth + 0.55, 0, base / 2, 0, joint);
        add(width, height, depth, 0, base + height / 2, 0, stone);
        add(width + 0.16, 0.1, depth + 0.16, 0, base + height + 0.05, 0, joint);

        [1.52, 2.45, 3.36].forEach(function (y) {
            add(width + 0.05, 0.035, 0.05, 0, y, depth / 2 + 0.02, joint);
            add(0.05, 0.035, depth + 0.04, width / 2 + 0.02, y, 0, joint);
        });

        floors.forEach(function (y, floor) {
            for (let column = 0; column < bays; column += 1) {
                const x = -width / 2 + bay * (column + 0.5);
                if (floor === 0 && column === 1) {
                    add(0.58, 1.22, 0.06, x, 0.78, depth / 2 + 0.03, doorMat);
                    continue;
                }
                add(0.32, 0.5, 0.035, x, y, depth / 2 + 0.012, glass);
                add(0.4, 0.03, 0.045, x, y - 0.28, depth / 2 + 0.028, joint);
                if (floor === 2 && column === 4) {
                    add(0.16, 0.22, 0.02, x, y - 0.06, depth / 2 + 0.04, occupied);
                }
            }
        });

        for (let column = 0; column <= bays; column += 1) {
            const x = -width / 2 + bay * column;
            add(0.055, height, 0.04, x, base + height / 2, depth / 2 + 0.018, joint);
        }

        floors.forEach(function (y, floor) {
            [-0.85, 0, 0.85].forEach(function (z) {
                if (floor === 0 && z === 0) {
                    return;
                }
                add(0.03, 0.42, 0.28, width / 2 + 0.015, y, z, glass);
            });
        });

        const ground = new THREE.Mesh(
            new THREE.PlaneGeometry(40, 40),
            new THREE.ShadowMaterial({ opacity: 0.28 })
        );
        ground.rotation.x = -Math.PI / 2;
        ground.position.y = 0;
        ground.receiveShadow = true;
        scene.add(ground);

        scene.add(new THREE.HemisphereLight(0xf4f7fb, 0xc8c2b8, 0.42));
        const ambient = new THREE.AmbientLight(0xffffff, 0.28);
        scene.add(ambient);

        const key = new THREE.DirectionalLight(0xfff8f2, 2.1);
        key.position.set(-8, 11, 7);
        key.castShadow = true;
        key.shadow.mapSize.set(1024, 1024);
        key.shadow.camera.near = 1;
        key.shadow.camera.far = 28;
        key.shadow.camera.left = -8;
        key.shadow.camera.right = 8;
        key.shadow.camera.top = 8;
        key.shadow.camera.bottom = -8;
        key.shadow.bias = -0.0004;
        scene.add(key);

        const fill = new THREE.DirectionalLight(0xe7eef8, 0.35);
        fill.position.set(-6, 4, -3);
        scene.add(fill);

        let progress = 0;
        let shown = 0;
        let running = true;
        let seen = true;
        const focus = new THREE.Vector3(0, 1.85, 0);

        function fitDistance() {
            group.updateMatrixWorld(true);
            const bounds = new THREE.Box3().setFromObject(group);
            const size = bounds.getSize(new THREE.Vector3());
            const span = Math.hypot(size.x, size.z) * 0.72;
            const vFov = camera.fov * Math.PI / 180;
            const hFov = 2 * Math.atan(Math.tan(vFov / 2) * Math.max(camera.aspect, 0.6));
            const distV = (size.y / 2) / Math.tan(vFov / 2);
            const distH = (span / 2) / Math.tan(hFov / 2);
            return Math.max(distV, distH) * 1.42;
        }

        function resize() {
            const widthPx = canvas.clientWidth;
            const heightPx = canvas.clientHeight;
            if (!widthPx || !heightPx) {
                return;
            }
            renderer.setSize(widthPx, heightPx, false);
            camera.aspect = widthPx / heightPx;
            camera.updateProjectionMatrix();
        }

        function frame(time) {
            if (!running) {
                return;
            }
            requestAnimationFrame(frame);
            if (!seen) {
                return;
            }
            shown += (progress - shown) * (reduced ? 1 : 0.06);
            const idle = reduced ? 0 : Math.sin(time * 0.00018) * 0.08;
            const dist = fitDistance() * (1 - shown * 0.16);
            const heading = 0.78 + idle * 0.02 - shown * 0.12;
            camera.position.set(
                focus.x + Math.sin(heading) * dist,
                focus.y + dist * 0.28,
                focus.z + Math.cos(heading) * dist
            );
            group.rotation.y = 0.08;
            camera.lookAt(focus);
            renderer.render(scene, camera);
        }

        resize();
        requestAnimationFrame(frame);
        window.addEventListener('resize', resize);

        if ('IntersectionObserver' in window) {
            const watch = new IntersectionObserver(function (entries) {
                seen = entries[0].isIntersecting;
            });
            watch.observe(canvas);
        }

        document.addEventListener('visibilitychange', function () {
            running = document.visibilityState === 'visible';
            if (running) {
                requestAnimationFrame(frame);
            }
        });

        return {
            setProgress: function (value) {
                progress = value;
            }
        };
    }

    function bindScroll(sceneApi) {
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

        if (sceneApi) {
            ScrollTrigger.create({
                trigger: '.hero',
                start: 'top 75%',
                end: 'bottom top',
                scrub: true,
                onUpdate: function (self) {
                    sceneApi.setProgress(self.progress);
                }
            });
        }

        const motion = gsap.matchMedia();
        motion.add('(min-width: 960px)', function () {
            const screens = gsap.utils.toArray('.screen');
            const steps = gsap.utils.toArray('.step');
            const panel = document.querySelector('.window');
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
