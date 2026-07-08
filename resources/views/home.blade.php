@extends('layouts.app')

@section('content')
{{-- Photo en fond : calque fixe plein écran derrière toute la page --}}
<div style="position:fixed;inset:0;z-index:-1;background:url('{{ asset('images/logo-mairie.jpg') }}') center/cover no-repeat;">
    {{-- voile pour la lisibilité du texte --}}
    <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(15,32,58,.55) 0%, rgba(15,32,58,.25) 45%, rgba(15,32,58,.55) 100%);"></div>
</div>

<div style="min-height:calc(100vh - 110px);"></div>

{{-- ── Fenêtre de présentation : réapparaît à chaque passage sur l'accueil ── --}}
<div id="mgdsWelcome" style="display:flex;position:fixed;inset:0;background:rgba(15,32,58,.45);z-index:1050;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;max-width:520px;width:92%;padding:28px 32px;position:relative;box-shadow:0 12px 48px rgba(0,0,0,.35);">
        <button type="button" onclick="fermerWelcome()"
                style="position:absolute;top:10px;right:14px;border:none;background:none;font-size:22px;color:#888;" title="Fermer">✕</button>

        <div class="text-center mb-3">
            <img src="{{ asset('images/logo-mgds.png') }}" alt="MGDS" style="height:80px;">
        </div>

        <h2 class="text-center mb-3" style="font-size:1.25rem;">
            <strong>M</strong><em>airie</em> - <strong>G</strong><em>estion</em> <strong>D</strong><em>es</em> <strong>S</strong><em>ervices</em>
        </h2>

        <p style="font-size:14px;">MGDS accompagne les Mairies dans le suivi quotidien des tâches de leurs services :</p>
        <ul style="font-size:14px;line-height:1.8;">
            <li>création et affectation des travaux</li>
            <li>photos avant/après</li>
            <li>clôtures automatiques</li>
            <li>annuaire des services</li>
            <li>suivi de la charge de travail</li>
        </ul>
        <p class="text-muted mb-0" style="font-size:13px;">Le tout dans un espace sécurisé propre à chaque service.</p>
    </div>
</div>

<script>
function fermerWelcome() {
    document.getElementById('mgdsWelcome').style.display = 'none';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') fermerWelcome(); });
</script>
@endsection
