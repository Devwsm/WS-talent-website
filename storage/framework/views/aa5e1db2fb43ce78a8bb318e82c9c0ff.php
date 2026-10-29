<div class="cover bg-white flex flex-col w-full py-16 justify-center">
    <section class="w-full px-4" aria-labelledby="tour-heading">
        <h2 id="tour-heading" class="text-center text-2xl md:text-4xl font-bold uppercase tracking-tight mb-8">
            Tour
        </h2>

        <div class="max-w-3xl mx-auto flex flex-col items-center text-center">
            
            <div class="w-full" style="min-height: 12rem">
                <a class="bit-widget-initializer" data-artist-name="id_6784724"
                    data-app-id="2380980eaea24e40832c0c2abc88643d" data-display-limit="5" data-display-logo="false"
                    data-font="Sora" data-text-color="#000000" data-background-color="#ffffff"
                    data-separator-color="#e5e5e5"></a>
            </div>

            <a href="https://www.bandsintown.com/a/6784724" target="_blank" rel="noopener noreferrer"
                class="mt-8 inline-flex items-center px-5 py-1.5 rounded-sm border border-black bg-white text-black text-sm uppercase hover:bg-black hover:text-white transition-colors">
                Lihat semua jadwal di Bandsintown
            </a>
        </div>
    </section>
</div>

<script>
    (function() {
        const bagian = document.getElementById("tour");
        if (!bagian) return;

        // Tautan buatan widget yang tidak punya teks diberi nama supaya terbaca screen reader.
        function beriNama() {
            bagian.querySelectorAll("a.bit-details:not([aria-label])").forEach((a) => {
                if (!a.textContent.trim()) a.setAttribute("aria-label", "Detail acara");
            });
            bagian.querySelectorAll("a.bit-logo-link:not([aria-label])").forEach((a) => {
                if (!a.textContent.trim()) a.setAttribute("aria-label", "Bandsintown");
            });
        }

        let sudahMuat = false;

        function muatWidget() {
            if (sudahMuat) return;
            sudahMuat = true;

            const pengamat = new MutationObserver(beriNama);
            pengamat.observe(bagian, {
                childList: true,
                subtree: true
            });
            setTimeout(() => pengamat.disconnect(), 20000);

            const skrip = document.createElement("script");
            skrip.charset = "utf-8";
            skrip.async = true;
            skrip.src = "https://widgetv3.bandsintown.com/main.min.js";
            document.body.appendChild(skrip);
        }

        if ("IntersectionObserver" in window) {
            const io = new IntersectionObserver((entri) => {
                if (entri.some((e) => e.isIntersecting)) {
                    io.disconnect();
                    muatWidget();
                }
            }, {
                rootMargin: "600px 0px"
            });
            io.observe(bagian);
        } else {
            window.addEventListener("load", muatWidget, {
                once: true
            });
        }
    })();
</script>
<?php /**PATH C:\Users\anugr\Documents\Whisnu Santika\Github\WS-talent-website\resources\views/components/tour.blade.php ENDPATH**/ ?>