{{-- Fase 2 — motion. Inline (bukan bagian bundle Vite / file public) supaya jalan segera dan
     nggak bergantung ke npm run build atau path asset di hosting. Di-include di akhir <body>. --}}
<script>
    @verbatim
        /**
         * Fase 2 — Motion (rasa framer-motion, versi Laravel).
         *
         * Prinsip desain (belajar dari versi sebelumnya yang bikin halaman "ketahan"):
         *  - Konten SELALU terlihat secara default. Nggak ada CSS yang menyembunyikan apa pun.
         *  - Script ini sendiri yang menyembunyikan elemen yang masih di bawah layar, tepat
         *    sebelum ia memunculkannya lagi pakai animasi. Kalau script gagal/telat, halaman
         *    tampil biasa — tanpa lag, tanpa timeout.
         *  - Gerakan memakai SPRING (kaku/redaman seperti framer-motion), bukan easing biasa.
         *
         * Atribut:
         *   data-motion="fade-up|fade-down|fade-left|fade-right|fade-in|zoom|pop|blur-up|clip-up"
         *   data-motion-delay="0.2"      detik (opsional)
         *   data-motion-stagger="0.08"   di parent: anak muncul berurutan
         *   data-motion-children="pop"   variant untuk anak parent stagger
         *   data-split                   judul muncul kata per kata
         *   data-count                   angka naik dari 0
         *   data-tilt="8" | data-magnetic | .spotlight-card   efek kursor (desktop saja)
         */
        (function() {
            "use strict";

            window.__motionReady = true;

            var root = document.documentElement;
            if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
            if (!("IntersectionObserver" in window) || !Element.prototype.animate) return;

            var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
            var K = window.innerWidth < 768 ? 0.65 : 1; // gerak lebih kecil di HP/tablet
            var vh = window.innerHeight;
            var flushers = []; // dipanggil saat user sudah di dasar halaman (lihat initScroll)

            /* ---------------- spring ---------------- */
            var PRESETS = {
                soft: {
                    k: 110,
                    c: 16,
                    m: 1
                },
                pop: {
                    k: 190,
                    c: 13,
                    m: 1
                },
            };
            var springCache = {};

            function spring(name) {
                if (springCache[name]) return springCache[name];
                var p = PRESETS[name] || PRESETS.soft;
                var dt = 1 / 60,
                    x = 0,
                    v = 0,
                    t = 0,
                    out = [0];
                while (t < 2.2) {
                    var a = (-p.k * (x - 1) - p.c * v) / p.m;
                    v += a * dt;
                    x += v * dt;
                    t += dt;
                    out.push(x);
                    if (t > 0.25 && Math.abs(x - 1) < 0.002 && Math.abs(v) < 0.02) break;
                }
                out[out.length - 1] = 1;
                return (springCache[name] = {
                    out: out,
                    ms: Math.round(t * 1000)
                });
            }

            var VARIANTS = {
                "fade-up": {
                    y: 40
                },
                "fade-down": {
                    y: -40
                },
                "fade-left": {
                    x: -50
                },
                "fade-right": {
                    x: 50
                },
                "fade-in": {},
                zoom: {
                    s: 0.9
                },
                pop: {
                    s: 0.7,
                    sp: "pop"
                },
                "blur-up": {
                    y: 32,
                    blur: 14
                },
                "clip-up": {
                    y: 20,
                    clip: true
                },
            };

            function build(v) {
                var s = spring(v.sp || "soft");
                var n = s.out.length;
                var f = [];
                for (var i = 0; i < n; i++) {
                    var p = s.out[i];
                    var inv = 1 - p;
                    var fr = {
                        offset: i / (n - 1),
                        opacity: Math.min(1, Math.max(0, p * 1.6)),
                        transform: "translate3d(" + ((v.x || 0) * K * inv).toFixed(2) + "px," +
                            ((v.y || 0) * K * inv).toFixed(2) + "px,0) scale(" +
                            (1 + ((v.s || 1) - 1) * inv).toFixed(4) + ")",
                    };
                    if (v.blur) fr.filter = "blur(" + Math.max(0, v.blur * inv).toFixed(2) + "px)";
                    if (v.clip) fr.clipPath = "inset(" + Math.min(100, Math.max(0, inv * 100)).toFixed(2) +
                        "% 0 0 0)";
                    f.push(fr);
                }
                return {
                    frames: f,
                    ms: s.ms
                };
            }

            function play(el, variant, delaySec) {
                el.style.opacity = "";
                var d = build(VARIANTS[variant] || VARIANTS["fade-up"]);
                var a = el.animate(d.frames, {
                    duration: d.ms,
                    delay: Math.round(delaySec * 1000),
                    easing: "linear",
                    fill: "both",
                });
                // Setelah selesai lepas animasinya supaya hover/tilt milik elemen nggak ketimpa.
                a.onfinish = function() {
                    try {
                        a.cancel();
                    } catch (e) {}
                };
            }

            /* ---------------- 1. reveal on scroll ---------------- */
            function variantOf(el) {
                var parent = el.parentElement;
                return el.dataset.motion || (parent && parent.dataset.motionChildren) || "fade-up";
            }

            function revealBatch(list) {
                var counts = new Map();
                list.forEach(function(el) {
                    var parent = el.parentElement;
                    var staggered = parent && parent.hasAttribute("data-motion-stagger");
                    var step = staggered ? parseFloat(parent.dataset.motionStagger) || 0.08 : 0;
                    var idx = counts.get(parent) || 0;
                    counts.set(parent, idx + 1);

                    var explicit = parseFloat(el.dataset.motionDelay);
                    var delay = isFinite(explicit) ? explicit : Math.min(idx * step, 0.6);
                    play(el, variantOf(el), delay);
                });
            }

            function initReveal() {
                var els = Array.prototype.slice.call(
                    document.querySelectorAll("[data-motion], [data-motion-stagger] > *"),
                ).filter(function(el) {
                    return !el.hasAttribute("data-split");
                });
                if (!els.length) return;

                var pending = new Set();

                var io = new IntersectionObserver(
                    function(entries) {
                        var batch = [];
                        entries.forEach(function(e) {
                            if (e.isIntersecting) {
                                batch.push(e.target);
                                pending.delete(e.target);
                                io.unobserve(e.target);
                            } else if (e.boundingClientRect.height > 0 && e.boundingClientRect.top <
                                0) {
                                // Sudah terlewat (mis. refresh di tengah halaman) — tampilkan tanpa animasi.
                                e.target.style.opacity = "";
                                pending.delete(e.target);
                                io.unobserve(e.target);
                            }
                        });
                        if (batch.length) revealBatch(batch);
                    }, {
                        threshold: 0,
                        rootMargin: "0px 0px -6% 0px"
                    },
                );

                // Safety net: elemen yang tertahan di zona paling bawah layar (mis. baris copyright di
                // footer, yang nggak bisa di-scroll lebih tinggi lagi) tetap harus muncul.
                flushers.push(function() {
                    var batch = [];
                    pending.forEach(function(el) {
                        if (el.getBoundingClientRect().height > 0) batch.push(el);
                    });
                    batch.forEach(function(el) {
                        pending.delete(el);
                        io.unobserve(el);
                    });
                    if (batch.length) revealBatch(batch);
                });

                var initial = [];
                els.forEach(function(el) {
                    var r = el.getBoundingClientRect();
                    var hidden = r.width === 0 && r.height === 0; // display:none (varian desktop/mobile)
                    if (!hidden && r.bottom <= 0) return; // sudah di atas layar: biarkan terlihat
                    if (!hidden && r.top < vh * 0.92) {
                        initial.push(el); // sudah kelihatan saat load: animasikan langsung
                    } else {
                        el.style.opacity = "0"; // masih di bawah: sembunyikan, tunggu di-scroll
                        pending.add(el);
                        io.observe(el);
                    }
                });
                revealBatch(initial);
            }

            /* ---------------- 2. judul kata-per-kata ---------------- */
            function playSplit(el) {
                var s = spring("soft");
                var n = s.out.length;
                (el.__inners || []).forEach(function(w, i) {
                    var f = [];
                    for (var k = 0; k < n; k++) {
                        f.push({
                            offset: k / (n - 1),
                            transform: "translate3d(0," + (110 * (1 - s.out[k])).toFixed(2) +
                                "%,0)",
                        });
                    }
                    w.style.transform = "";
                    var a = w.animate(f, {
                        duration: s.ms,
                        delay: i * 70,
                        easing: "linear",
                        fill: "both"
                    });
                    a.onfinish = function() {
                        try {
                            a.cancel();
                        } catch (e) {}
                    };
                });
            }

            function initSplit() {
                var els = Array.prototype.slice.call(document.querySelectorAll("[data-split]"));
                if (!els.length) return;

                var io = new IntersectionObserver(
                    function(entries) {
                        entries.forEach(function(e) {
                            if (e.isIntersecting) {
                                playSplit(e.target);
                                io.unobserve(e.target);
                            } else if (e.boundingClientRect.height > 0 && e.boundingClientRect.top <
                                0) {
                                (e.target.__inners || []).forEach(function(w) {
                                    w.style.transform = "";
                                });
                                io.unobserve(e.target);
                            }
                        });
                    }, {
                        threshold: 0,
                        rootMargin: "0px 0px -6% 0px"
                    },
                );

                els.forEach(function(el) {
                    var text = el.textContent.replace(/\s+/g, " ").trim();
                    if (!text) return;

                    el.setAttribute("aria-label", text); // screen reader tetap baca utuh
                    el.textContent = "";
                    el.__inners = [];

                    var words = text.split(" ");
                    words.forEach(function(word, i) {
                        var wrap = document.createElement("span");
                        wrap.className = "split-word";
                        wrap.setAttribute("aria-hidden", "true");
                        var inner = document.createElement("span");
                        inner.className = "split-inner";
                        inner.textContent = word;
                        wrap.appendChild(inner);
                        el.appendChild(wrap);
                        el.__inners.push(inner);
                        if (i < words.length - 1) el.appendChild(document.createTextNode(" "));
                    });

                    var r = el.getBoundingClientRect();
                    var hidden = r.width === 0 && r.height === 0;
                    if (!hidden && r.bottom <= 0) return;
                    if (!hidden && r.top < vh * 0.92) {
                        playSplit(el);
                    } else {
                        el.__inners.forEach(function(w) {
                            w.style.transform = "translate3d(0,110%,0)";
                        });
                        io.observe(el);
                    }
                });
            }

            /* ---------------- 3. angka naik ---------------- */
            function parseCount(text) {
                var m = text.match(/^(\D*?)(\d[\d.,]*)([\s\S]*)$/);
                if (!m) return null;
                var prefix = m[1],
                    num = m[2],
                    suffix = m[3];

                if (/^\d{1,3}([.,]\d{3})+$/.test(num)) {
                    return {
                        prefix: prefix,
                        suffix: suffix,
                        value: parseInt(num.replace(/[.,]/g, ""), 10),
                        decimals: 0,
                        dsep: ".",
                        sep: num.match(/[.,]/)[0]
                    };
                }
                if (/^\d+[.,]\d+$/.test(num)) {
                    var dot = num.match(/[.,]/)[0];
                    return {
                        prefix: prefix,
                        suffix: suffix,
                        value: parseFloat(num.replace(dot, ".")),
                        decimals: num.split(dot)[1].length,
                        dsep: dot,
                        sep: ""
                    };
                }
                var v = parseInt(num, 10);
                if (!isFinite(v)) return null;
                return {
                    prefix: prefix,
                    suffix: suffix,
                    value: v,
                    decimals: 0,
                    dsep: ".",
                    sep: ""
                };
            }

            function fmt(n, c) {
                if (c.decimals) return n.toFixed(c.decimals).replace(".", c.dsep);
                var s = Math.round(n).toString();
                return c.sep ? s.replace(/\B(?=(\d{3})+(?!\d))/g, c.sep) : s;
            }

            function initCounters() {
                var els = Array.prototype.slice.call(document.querySelectorAll("[data-count]"));
                if (!els.length) return;

                var io = new IntersectionObserver(
                    function(entries) {
                        entries.forEach(function(e) {
                            if (!e.isIntersecting) return;
                            var el = e.target;
                            io.unobserve(el);

                            var original = el.textContent;
                            var c = parseCount(original.trim());
                            if (!c) return;

                            var start = performance.now();
                            (function tick(now) {
                                var t = Math.min((now - start) / 1500, 1);
                                var eased = t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
                                el.textContent = c.prefix + fmt(c.value * eased, c) + c.suffix;
                                if (t < 1) requestAnimationFrame(tick);
                                else el.textContent = original;
                            })(start);
                        });
                    }, {
                        threshold: 0.6
                    },
                );
                els.forEach(function(el) {
                    io.observe(el);
                });
            }

            /* ---------------- 4. hero: masuk tiap ganti slide ---------------- */
            // Slide pertama dianimasikan lewat CSS (.videosSwiper .content > *) saat first paint.
            function initHero() {
                var el = document.querySelector(".videosSwiper");
                if (!el) return;

                function enter(slide) {
                    if (!slide) return;
                    var s = spring("soft");
                    var n = s.out.length;
                    Array.prototype.forEach.call(slide.querySelectorAll(".content > *"), function(node, i) {
                        var f = [];
                        for (var k = 0; k < n; k++) {
                            var inv = 1 - s.out[k];
                            f.push({
                                offset: k / (n - 1),
                                opacity: Math.min(1, Math.max(0, s.out[k] * 1.6)),
                                transform: "translate3d(0," + (30 * K * inv).toFixed(2) + "px,0)",
                                filter: "blur(" + Math.max(0, 8 * inv).toFixed(2) + "px)",
                            });
                        }
                        node.animate(f, {
                            duration: s.ms,
                            delay: 120 + i * 110,
                            easing: "linear",
                            fill: "backwards"
                        });
                    });
                }

                var tries = 0;
                var timer = setInterval(function() {
                    var sw = el.swiper;
                    if (sw) {
                        clearInterval(timer);
                        sw.on("slideChangeTransitionStart", function() {
                            enter(sw.slides[sw.activeIndex]);
                        });
                    } else if (++tries > 100) {
                        clearInterval(timer);
                    }
                }, 100);
            }

            /* ---------------- 5. scroll: progress, parallax hero, navbar ---------------- */
            function initScroll() {
                var bar = document.getElementById("scrollProgress");
                var nav = document.getElementById("navbar");
                var hero = document.querySelector(".videosSwiper");
                if (!bar && !nav && !hero) return;

                var maxScroll = 0;

                function measure() {
                    maxScroll = Math.max(0, root.scrollHeight - window.innerHeight);
                }

                function atBottom() {
                    return maxScroll - window.scrollY <= 4;
                }

                function flushIfBottom() {
                    if (atBottom()) flushers.forEach(function(f) {
                        f();
                    });
                }
                measure();
                window.addEventListener("load", function() {
                    measure();
                    flushIfBottom();
                });
                window.addEventListener("resize", function() {
                    vh = window.innerHeight;
                    measure();
                }, {
                    passive: true
                });
                if (window.ResizeObserver) new ResizeObserver(measure).observe(document.body);

                var lastY = window.scrollY;
                var ticking = false;

                function frame(fromScroll) {
                    ticking = false;
                    if (document.body.style.position === "fixed") return; // drawer mobile terbuka
                    var y = window.scrollY;
                    if (fromScroll === true) flushIfBottom();

                    if (bar) bar.style.transform = "scaleX(" + (maxScroll > 0 ? Math.min(y / maxScroll, 1) : 0) +
                        ")";

                    if (hero) {
                        var h = hero.offsetHeight || vh;
                        if (y < h * 1.1) {
                            var shift = "translate3d(0," + (y * 0.16).toFixed(1) + "px,0) scale(1.08)";
                            Array.prototype.forEach.call(hero.querySelectorAll(".hero-bg"), function(b) {
                                b.style.transform = shift;
                            });
                        }
                    }

                    if (nav) {
                        var dy = y - lastY;
                        nav.classList.toggle("is-scrolled", y > 8);
                        if (y > 160 && dy > 6) nav.style.transform = "translateY(-100%)";
                        else if (dy < -6 || y <= 160) nav.style.transform = "";
                    }
                    lastY = y;
                }

                window.addEventListener("scroll", function() {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(function() {
                        frame(true);
                    });
                }, {
                    passive: true
                });

                if (nav) nav.addEventListener("focusin", function() {
                    nav.style.transform = "";
                });
                frame();
            }

            /* ---------------- 6. efek kursor (desktop saja) ---------------- */
            function initPointerFx() {
                if (!finePointer) return;

                Array.prototype.forEach.call(document.querySelectorAll("[data-tilt]"), function(el) {
                    var max = parseFloat(el.dataset.tilt) || 8;
                    var raf = 0;
                    el.addEventListener("pointermove", function(e) {
                        if (e.pointerType !== "mouse") return;
                        var r = el.getBoundingClientRect();
                        var px = (e.clientX - r.left) / r.width - 0.5;
                        var py = (e.clientY - r.top) / r.height - 0.5;
                        cancelAnimationFrame(raf);
                        raf = requestAnimationFrame(function() {
                            el.style.transform = "perspective(900px) rotateX(" + (-py * max)
                                .toFixed(2) + "deg) rotateY(" + (px * max).toFixed(2) +
                                "deg) scale3d(1.02,1.02,1.02)";
                        });
                    });
                    el.addEventListener("pointerleave", function() {
                        cancelAnimationFrame(raf);
                        el.style.transform = "";
                    });
                });

                Array.prototype.forEach.call(document.querySelectorAll("[data-magnetic]"), function(el) {
                    el.addEventListener("pointermove", function(e) {
                        if (e.pointerType !== "mouse") return;
                        var r = el.getBoundingClientRect();
                        var dx = (e.clientX - (r.left + r.width / 2)) * 0.25;
                        var dy = (e.clientY - (r.top + r.height / 2)) * 0.35;
                        el.style.translate = dx.toFixed(1) + "px " + dy.toFixed(1) + "px";
                    });
                    el.addEventListener("pointerleave", function() {
                        el.style.translate = "";
                    });
                });

                Array.prototype.forEach.call(document.querySelectorAll(".spotlight-card"), function(el) {
                    el.addEventListener("pointermove", function(e) {
                        var r = el.getBoundingClientRect();
                        el.style.setProperty("--sx", e.clientX - r.left + "px");
                        el.style.setProperty("--sy", e.clientY - r.top + "px");
                    });
                });
            }

            [initSplit, initReveal, initCounters, initHero, initScroll, initPointerFx].forEach(function(fn) {
                try {
                    fn();
                } catch (err) {
                    console.error("[motion]", fn.name, err);
                }
            });
        })();
    @endverbatim
</script>
