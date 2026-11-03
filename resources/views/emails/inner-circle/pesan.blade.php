{{--
    Tampilan bersama semua email WS Inner Circle (dipakai kelas turunan App\Mail\InnerCircle\PesanIc).
    Tata letak memakai tabel dan gaya inline supaya tampil sama di Gmail, Outlook, dan aplikasi email HP.
    Variabel: $judul, $sapaan, $pratinjau, $paragraf[], $tombol (teks + url) | null, $catatan[], $tes.
    Warna: satu tema dengan area anggota (lavender + navy), dari IcTampilan::warnaEmail(); tidak ada merah.
--}}
@php $w = ic_tampilan()->warnaEmail(); @endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $judul }}</title>
</head>

<body style="margin:0;padding:0;background-color:{{ $w['latar'] }};">
    {{-- Teks pratinjau yang tampil di daftar kotak masuk (disembunyikan di badan email). --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:{{ $w['latar'] }};">{{ $pratinjau }}</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:{{ $w['latar'] }};">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:560px;background-color:{{ $w['kartu'] }};border-radius:16px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
                    <tr>
                        <td
                            style="background-color:{{ $w['navy'] }};border-bottom:4px solid {{ $w['lilac'] }};padding:22px 28px;">
                            <span
                                style="font-size:13px;font-weight:bold;letter-spacing:3px;color:#ffffff;text-transform:uppercase;">{{ $merek ?? ic_program() }}</span>
                            <br>
                            <span style="font-size:12px;color:{{ $w['lilac'] }};">Whisnu Santika</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;color:{{ $w['tinta'] }};font-size:16px;line-height:1.6;">
                            @if ($tes)
                                <p
                                    style="margin:0 0 18px;padding:10px 14px;background-color:#fff4d6;border-radius:8px;font-size:13px;color:#6b5200;">
                                    Ini email percobaan dari dashboard Inner Circle. Isinya contoh, bukan permintaan
                                    sungguhan.
                                </p>
                            @endif

                            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:{{ $w['tinta'] }};">
                                {{ $judul }}</h1>
                            <p style="margin:0 0 14px;">{{ $sapaan }}</p>

                            @isset($kodeBesar)
                                <p
                                    style="margin:0 0 18px;padding:16px;background-color:#ffffff;border:1px solid {{ $w['garis'] }};border-radius:12px;text-align:center;font-size:34px;font-weight:bold;letter-spacing:10px;color:{{ $w['tautan'] }};">
                                    {{ $kodeBesar }}</p>
                            @endisset

                            @foreach ($paragraf as $baris)
                                <p style="margin:0 0 14px;">{{ $baris }}</p>
                            @endforeach

                            @if ($tombol)
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                    style="margin:22px 0;">
                                    <tr>
                                        <td style="background-color:{{ $w['navy'] }};border-radius:999px;">
                                            <a href="{{ $tombol['url'] }}"
                                                style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:bold;color:#ffffff;text-decoration:none;">{{ $tombol['teks'] }}</a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:0 0 14px;font-size:13px;color:{{ $w['redup'] }};">
                                    Tombol tidak berfungsi? Salin tautan ini ke browser:<br>
                                    <span
                                        style="word-break:break-all;color:{{ $w['tautan'] }};">{{ $tombol['url'] }}</span>
                                </p>
                            @endif

                            @foreach ($catatan as $baris)
                                <p style="margin:0 0 10px;font-size:13px;color:{{ $w['redup'] }};">
                                    {{ $baris }}</p>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="padding:18px 28px;background-color:#ffffff;border-top:1px solid {{ $w['garis'] }};font-size:12px;line-height:1.5;color:{{ $w['redup'] }};">
                            Email ini dikirim otomatis oleh {{ $merek ?? ic_program() }}. Jangan membalas email
                            ini.<br>
                            <a href="{{ route('privasi') }}" style="color:{{ $w['tautan'] }};">Kebijakan Privasi</a>
                            @if (filled(config('site.kontak_privasi')))
                                &middot; Pertanyaan: <a href="mailto:{{ config('site.kontak_privasi') }}"
                                    style="color:{{ $w['tautan'] }};">{{ config('site.kontak_privasi') }}</a>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
