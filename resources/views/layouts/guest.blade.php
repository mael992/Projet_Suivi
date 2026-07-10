<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MGDS — Connexion</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Styles MGDS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">

    <style>
        body.guest-body {
            min-height: 100vh;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .guest-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            overflow: hidden;
        }

        .guest-card-header {
            background: var(--dark);
            padding: 28px 32px 20px;
            text-align: center;
        }

        .guest-card-header img {
            height: 88px;
            object-fit: contain;
        }

        .guest-card-header .guest-subtitle {
            color: rgba(255,255,255,.55);
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .guest-card-body {
            padding: 28px 32px 32px;
        }

        @media (max-width: 480px) {
            .guest-card { border-radius: 0; min-height: 100vh; }
            body.guest-body { align-items: flex-start; }
        }
    </style>
</head>
<body class="guest-body">

    <div class="guest-card">
        <div class="guest-card-header">
            <a href="{{ route('home') }}">
                <img src="{{ asset('images/logo-mgds.png') }}" alt="MGDS">
            </a>
            <p class="guest-subtitle">MGDS — Gestion des services de la mairie</p>
        </div>
        <div class="guest-card-body">
            {{ $slot }}
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
