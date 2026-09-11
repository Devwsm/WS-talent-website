@if ($errors->any())
    <script>
        window.__swalErrors = @json($errors->all());
    </script>
@endif

@if (session('error'))
    <script>
        window.__swalError = @json(session('error'));
    </script>
@endif

@if (session('warning'))
    <script>
        window.__swalWarning = @json(session('warning'));
    </script>
@endif

@if (session('info'))
    <script>
        window.__swalInfo = @json(session('info'));
    </script>
@endif
