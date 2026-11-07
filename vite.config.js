import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { bangunKontainer } from "./vite/ic-kontainer.js";

// Menambahkan salinan aturan responsif area anggota berbasis lebar panel ke app.css (pratinjau CMS Inner Circle).
function icKontainer() {
    const sumber = resolve("resources/css/inner-circle.css");

    return {
        name: "ic-kontainer",
        enforce: "pre",
        transform(code, id) {
            if (
                !id
                    .split("?")[0]
                    .replace(/\\/g, "/")
                    .endsWith("/resources/css/app.css")
            ) {
                return null;
            }
            this.addWatchFile(sumber);

            return {
                code:
                    code + "\n" + bangunKontainer(readFileSync(sumber, "utf8")),
                map: null,
            };
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/dashboard.js",
                "resources/css/inner-circle.css",
                "resources/js/inner-circle.js",
            ],
            refresh: true,
        }),
        icKontainer(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
