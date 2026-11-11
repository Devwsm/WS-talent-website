// Editor teks kaya (Quill) untuk halaman admin: Berita, Bio, dan Kebijakan Privasi.
// Dibundel lewat Vite (bukan dari CDN) supaya versinya terkunci dan tidak bergantung pada situs luar.
// Dimuat hanya di 3 halaman itu lewat @vite('resources/js/quill-editor.js'); CSS tema "snow" ikut terbawa.
import Quill from "quill";
import "quill/dist/quill.snow.css";

// Skrip inline di view memakai `new Quill(...)` dan berjalan setelah modul ini dimuat (lihat DOMContentLoaded di view).
window.Quill = Quill;
