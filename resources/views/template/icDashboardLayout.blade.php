<!DOCTYPE html>
<html lang="id">

{{-- Kerangka CMS Inner Circle. Sama dengan dashboardLayout (CMS Artis); yang beda hanya class `cms-ic` di <body>,
     yang mengganti tema warna (lavender, lihat resources/css/app.css). --}}

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>CMS Inner Circle · Whisnu Santika</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
    @stack('head')
</head>

<body class="cms-ic bg-black text-white flex flex-col w-full sora">
    <div class="flex flex-col justify-center items-center">
        @yield('content')
    </div>
    @stack('scripts')
</body>

</html>
