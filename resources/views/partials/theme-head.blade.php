{{-- Apply the saved theme before paint, including after Turbo navigation. --}}
<script src="{{ asset('js/theme.js') }}" data-turbo-eval="false"></script>
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
@include('partials.pwa-head')
