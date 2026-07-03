@extends('layouts.app')

@section('content')
<div class="container py-5 text-center">
    <img src="{{ asset('images/logo-mairie.jpg') }}" alt="MGDS" style="height:96px;border-radius:12px;" class="mb-4">
    <h1 class="h3 mb-2">{{ __('mgds.welcome_title') }}</h1>
    <p class="text-muted mb-4">{{ __('mgds.welcome_text') }}</p>

    @guest
        <a href="{{ route('login') }}" class="btn btn-dark px-4">{{ __('mgds.nav_login') }}</a>
    @endguest
    @auth
        <a href="{{ route('dashboard') }}" class="btn btn-dark px-4">{{ __('mgds.nav_dashboard') }}</a>
    @endauth
</div>
@endsection
