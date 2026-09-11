@if (session('success'))
    <script>
        window.__swalSuccess = @json(session('success'));
    </script>
@endif
