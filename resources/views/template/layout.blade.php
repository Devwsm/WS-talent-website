<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Whisnu Santika</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="{{ asset('aset/logo/Whisnu-Santika_Logo-2025-2-White.png') }}" type="image/png">
</head>

<body class="bg-[#5E0006] flex flex-col w-full sora">
    <div class="flex flex-col justify-center items-center">
        @yield('content')
    </div>
</body>

{{-- reveal opacity-0 transition-all duration-700 --}}
{{-- <script>
    document.addEventListener("DOMContentLoaded", () => {
        const elements = document.querySelectorAll(".reveal");

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("opacity-100", "translate-y-0");

                    // penting: stop observe biar tidak reset
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15
        });

        elements.forEach(el => observer.observe(el));
    });
</script> --}}

</html>
