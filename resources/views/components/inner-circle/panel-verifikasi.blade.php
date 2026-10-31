@php
    $anggotaVerif = auth('member')->user();
@endphp
@if ($anggotaVerif && $anggotaVerif->email_verified_at === null)
    <section class="ic-panel ic-jarak-atas" aria-labelledby="verifikasi-judul">
        <h2 class="ic-panel__judul" id="verifikasi-judul">✉ Verifikasi emailmu dulu</h2>
        <p class="ic-bantuan">Check-in, unggah kenangan, suka, dan hadiah baru bisa dipakai setelah emailmu
            terverifikasi.
            Kami mengirim tautan ke <b>{{ $anggotaVerif->email }}</b>; buka tautannya saat kamu sudah masuk. Tidak ada
            email? Cek folder spam, atau kirim ulang.
        </p>
        <form method="POST" action="{{ route('ic.verifikasi.kirim') }}" data-ic-sekali>
            @csrf
            <button type="submit" class="ic-tombol ic-tombol--kecil">Kirim ulang email verifikasi</button>
        </form>
    </section>
@endif
