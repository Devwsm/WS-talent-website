import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/dashboard.js",
                "resources/js/quill-editor.js",
                "resources/css/inner-circle.css",
                "resources/js/inner-circle.js",
                "resources/js/atur-acara.js",
                "resources/js/izin-perangkat.js",
                "resources/js/kartu-gl-pdf.js",
                "resources/css/game-mambo.css",
                "resources/js/game-mambo.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
