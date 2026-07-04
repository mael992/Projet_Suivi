@extends('layouts.app')

@section('content')
<div style="position:relative;min-height:calc(100vh - 120px);background:url('{{ asset('images/logo-mairie.jpg') }}') center/cover no-repeat;">
    {{-- voile pour la lisibilité du texte --}}
    <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(15,32,58,.55) 0%, rgba(15,32,58,.25) 45%, rgba(15,32,58,.55) 100%);"></div>

    <div class="container position-relative py-5 text-center" style="z-index:1;">
        <div class="mx-auto mt-5" style="max-width:640px;background:rgba(255,255,255,.92);border-radius:14px;padding:32px 36px;box-shadow:0 8px 32px rgba(0,0,0,.25);">
            <img src="{{ asset('images/logo-mgds.png') }}" alt="MGDS" style="height:110px;" class="mb-3">
            <h1 class="h4 mb-3">{{ __('mgds.welcome_title') }}</h1>
            <p class="text-muted mb-0" style="font-size:15px;line-height:1.7;">
                {{ __('mgds.welcome_presentation') }}
            </p>
        </div>
    </div>
</div>
@endsection
