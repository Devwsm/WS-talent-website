<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Whisnu Santika</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#5E0006] flex flex-col w-full sora">
    <div class="flex flex-col justify-center items-center">
        @yield('content')
    </div>
</body>
</html>