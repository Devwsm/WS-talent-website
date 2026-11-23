<?php

/*
| Anggaran email harian seluruh situs (semua jenis email digabung, hitungan bergulir 24 jam).
| Gmail biasa sekitar 500 penerima per hari; melewatinya memblokir semua pengiriman sampai 24 jam, termasuk kode
| login admin dan atur ulang sandi. Karena itu situs berhenti sendiri sebelum batas Gmail tercapai:
|
| - harian: anggaran yang dipegang (isi EMAIL_ANGGARAN_HARIAN; Google Workspace boleh lebih besar, mis. 1800).
| - batas_rendah: pecahan anggaran. Email berprioritas RENDAH (selamat datang, peringatan error) berhenti dulu
|   begitu terpakai sebanyak ini.
| - batas_biasa: email BIASA (Kartu GL, status hadiah, status pengaduan) berhenti di pecahan ini. Kartu GL yang
|   tertahan dikirim otomatis saat anggaran longgar lagi.
| - PENTING (kode login admin, atur ulang sandi, verifikasi email) tidak pernah ditahan oleh anggaran ini.
|
| Hitungan disimpan di berkas (bukan cache) supaya tidak hilang saat deploy menjalankan optimize:clear.
*/
return [
    'harian' => (int) env('EMAIL_ANGGARAN_HARIAN', 450),
    'batas_rendah' => 0.6,
    'batas_biasa' => 0.9,
    'berkas' => null,
];