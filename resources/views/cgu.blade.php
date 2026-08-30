@extends('layouts.app')

@section('content')
@php $user = auth()->user(); @endphp

<div class="container py-4" style="max-width:860px;">

    <h1 class="h3 mb-1">📜 {{ __('Conditions générales d\'utilisation') }}</h1>
    <p class="text-muted mb-3" style="font-size:13px;">
        MGDS — Mairie Gestion Des Services · {{ __('Version du') }} {{ config('mgds.cgu_version', '01/08/2026') }}
    </p>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body" style="font-size:14px;line-height:1.6;max-height:60vh;overflow-y:auto;">

            <h2 class="h6">1. Objet</h2>
            <p>MGDS est une plateforme de gestion des services municipaux mise à disposition des mairies abonnées.
            Elle regroupe le suivi des tâches, la messagerie avec les habitants, l'annuaire interne, la gestion des
            marchés et des outils personnels (pense-bête, entraide entre mairies).</p>

            <h2 class="h6">2. Accès et comptes</h2>
            <p>L'accès est strictement réservé aux agents désignés par leur mairie. Chaque compte est nominatif :
            l'identifiant et le mot de passe sont personnels et ne doivent jamais être partagés. Le mot de passe
            provisoire remis à la création doit être changé dès la première connexion (validité 48 heures).</p>

            <h2 class="h6">3. Usage professionnel</h2>
            <p>La plateforme est réservée à un usage professionnel dans le cadre des missions de la mairie.
            Tout contenu illicite, injurieux, discriminatoire ou étranger au service est proscrit.</p>

            <h2 class="h6">4. Confidentialité et secret professionnel</h2>
            <p>Les informations consultées (annuaire, demandes des habitants, tâches) sont couvertes par le secret
            professionnel. Elles ne doivent être ni diffusées ni exploitées hors du cadre du service.
            Les éléments marqués « confidentiels » ne sont accessibles qu'aux personnes expressément désignées.</p>

            <h2 class="h6">5. Données personnelles (RGPD)</h2>
            <p>Les données sont hébergées pour le compte de la mairie, responsable de traitement. Les journaux
            d'activité sont conservés six mois. Les conversations clôturées restent consultables six mois.
            Chaque mairie peut demander l'export ou la suppression définitive de ses données ; une attestation
            lui est alors remise.</p>

            <h2 class="h6">6. Traçabilité</h2>
            <p>Les actions réalisées (création, modification, suppression, consultation de documents sensibles)
            sont enregistrées à des fins de sécurité et de preuve.</p>

            <h2 class="h6">7. Disponibilité</h2>
            <p>MGDS s'efforce d'assurer la continuité du service. Des interruptions peuvent survenir pour
            maintenance ou pour des raisons indépendantes de sa volonté. L'accès cesse à l'échéance de
            l'abonnement de la mairie, la date de fin étant incluse.</p>

            <h2 class="h6">8. Responsabilités</h2>
            <p>L'utilisateur est responsable des contenus qu'il saisit et des actions réalisées depuis son compte.
            Toute anomalie ou suspicion d'accès frauduleux doit être signalée sans délai au support.</p>

            <h2 class="h6">9. Évolution des conditions</h2>
            <p>Ces conditions peuvent évoluer. Une nouvelle acceptation sera demandée en cas de modification
            substantielle.</p>

            <h2 class="h6">10. Contact</h2>
            <p>Pour toute question : rubrique « Contacter le Support technique » depuis votre espace connecté.</p>
        </div>
    </div>

    @if($user && ! $user->cgu_acceptees_at)
        <form method="POST" action="{{ route('cgu.accepter') }}" class="card shadow-sm">
            @csrf
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input @error('cgu') is-invalid @enderror" type="checkbox"
                           name="cgu" value="1" id="cgu" required>
                    <label class="form-check-label fw-semibold" for="cgu">
                        {{ __('J\'ai pris connaissance des conditions générales d\'utilisation') }}
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Valider et accéder à MGDS') }}</button>
            </div>
        </form>
    @elseif($user)
        <div class="alert alert-success">
            ✅ {{ __('Vous avez accepté ces conditions le') }} {{ $user->cgu_acceptees_at->format('d/m/Y à H:i') }}.
        </div>
    @endif
</div>
@endsection
