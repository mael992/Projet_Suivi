<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MGDS — Gestion des Mairies</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Flag Icons (drapeaux SVG fiables) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css">
    <!-- Styles personnalisés (après Bootstrap pour surcharger) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

@include('partials.navbar')

<main>
    @yield('content')
</main>

@include('partials.footer')

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
