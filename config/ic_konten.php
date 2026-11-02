<?php

/*
|--------------------------------------------------------------------------
| Daftar konten Inner Circle yang bisa diubah dari CMS (tab Konten)
|--------------------------------------------------------------------------
| Satu-satunya sumber kebenaran untuk teks yang bisa diubah. Tiap kunci punya:
| - grup        : bagian di tab Konten (kunci di `grup` di bawah)
| - label       : nama isian di form
| - bawaan      : teks bawaan, dipakai selama tidak ada yang mengubahnya (isinya persis seperti yang tampil sekarang)
| - maks        : batas panjang (karakter)
| - baris       : true bila isian beberapa baris (textarea)
| - placeholder : placeholder tambahan yang boleh dipakai, mis. ['nama']. `{program}` selalu boleh.
|
| Kunci hanya boleh ada di sini bila SUDAH dipakai view lewat ic_teks('kunci'): ada tes yang menjaga agar tidak ada
| isian CMS yang tidak berpengaruh apa-apa. Tambah kunci baru = tambah di sini lalu pakai di view.
| Tanda < dan > tidak boleh dipakai (teks biasa saja, selalu di-escape saat ditampilkan).
*/

return [

    /* Nama program; dipakai lewat placeholder {program}. */
    'program' => 'WS Inner Circle',

    'grup' => [
        'checkin' => [
            'label' => 'Halaman Check-in',
            'ikon' => 'bi-qr-code-scan',
            'petunjuk' => 'Judul dan penjelasan di halaman Check-in anggota.',
            'halaman' => 'ic.checkin',
        ],
    ],

    'kunci' => [
        'checkin.eyebrow' => [
            'grup' => 'checkin',
            'label' => 'Teks kecil di atas judul',
            'bawaan' => 'Datang. Kumpulkan stempel.',
            'maks' => 60,
        ],
        'checkin.deskripsi' => [
            'grup' => 'checkin',
            'label' => 'Penjelasan di bawah judul',
            'bawaan' => 'Jadilah bagian dari perjalanan ini. Temukan kegiatan Inner Circle dan kumpulkan stempel ketika hadir.',
            'maks' => 220,
            'baris' => true,
        ],
        'checkin.jadwal.judul' => [
            'grup' => 'checkin',
            'label' => 'Judul kartu jadwal',
            'bawaan' => 'Jadwal Pertunjukan WS',
            'maks' => 80,
        ],
        'checkin.jadwal.deskripsi' => [
            'grup' => 'checkin',
            'label' => 'Penjelasan kartu jadwal',
            'bawaan' => 'Jadwal berdasarkan rencana WSM dan bersifat tentatif. Tidak semua acara otomatis memberi stempel.',
            'maks' => 220,
            'baris' => true,
        ],
        'checkin.lokasi.judul' => [
            'grup' => 'checkin',
            'label' => 'Judul kotak "sudah di lokasi"',
            'bawaan' => 'Sudah di lokasi?',
            'maks' => 60,
        ],
        'checkin.lokasi.deskripsi' => [
            'grup' => 'checkin',
            'label' => 'Penjelasan kotak "sudah di lokasi"',
            'bawaan' => 'Pilih acara di atas, atau langsung pindai QR dari panitia. Acaranya terpilih otomatis.',
            'maks' => 220,
            'baris' => true,
        ],
    ],

];