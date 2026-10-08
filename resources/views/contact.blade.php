@extends('layouts.app')

@section('content')
@php
    $connecte = auth()->check();

    // Réponses toutes faites de l'assistant (personne sans compte) : si elles
    // ne suffisent pas, on passe la main à un vrai conseiller.
    $aides = [
        'connexion'       => __('Vérifiez l\'identifiant qui vous a été remis par votre mairie, puis utilisez « Mot de passe oublié ? » sur la page de connexion. Si cela ne suffit pas, le responsable MGDS de votre mairie peut aussi vous aider.'),
        'trouver_mairie'  => __('Seules les mairies inscrites sur MGDS et qui reçoivent des demandes en ligne apparaissent dans la liste. Si la vôtre n\'y est pas, elle n\'utilise peut-être pas encore MGDS : contactez-la directement (téléphone, accueil).'),
        'question_mairie' => __('Pour écrire à votre mairie, utilisez la page « Contacter votre Mairie » : votre demande arrive directement chez elle.'),
    ];
@endphp

<style>
    .bulle-assistant { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:10px 14px; max-width:85%; font-size:14px; }
    .bulle-avatar { width:34px; height:34px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:var(--brand); color:#fff; font-size:16px; }
    .js .etape-suivante { display:none; }
</style>

<div class="container py-4" style="max-width:760px;">

    <h1 class="h3 mb-3">✉️ {{ __('Contacter le Support technique') }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    {{-- Demandes déjà envoyées par l'agent connecté --}}
    @if($demandes->isNotEmpty())
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">{{ __('Mes demandes au support') }}</div>
            <ul class="list-group list-group-flush">
                @foreach($demandes as $d)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('support.suivi', $d->jeton) }}" class="text-decoration-none">
                            {{ $d->reference }} — {{ $d->concerne_label }}
                            <small class="text-muted">· {{ $d->created_at->format('d/m/Y') }}</small>
                        </a>
                        <span class="badge {{ $d->statut === 'reponse' ? 'bg-success' : ($d->estCloture() ? 'bg-secondary' : 'bg-warning text-dark') }}">
                            {{ $d->statut_label }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('contact.store') }}" id="assistantSupport" class="card shadow-sm">
        @csrf
        <x-anti-robot />
        <div class="card-body d-flex flex-column gap-3" style="background:#f4f6f9;">

            {{-- Présentation de l'assistant --}}
            <div class="d-flex gap-2">
                <div class="bulle-avatar">🤖</div>
                <div class="bulle-assistant">
                    <div class="text-muted" style="font-size:11px;">{{ __('Assistant') }}</div>
                    {{ __('Bonjour, je suis l\'assistant MGDS. Je suis là pour vous accompagner.') }}
                    @unless($connecte)
                        <div class="mt-1 text-muted" style="font-size:13px;">
                            {{ __('Vous n\'êtes pas connecté : votre demande sera transmise comme venant d\'une personne sans compte MGDS.') }}
                            <a href="{{ route('login') }}">{{ __('Vous avez un compte ? Connectez-vous d\'abord.') }}</a>
                        </div>
                    @endunless
                </div>
            </div>

            {{-- Question 1 --}}
            <div class="d-flex gap-2">
                <div class="bulle-avatar">🤖</div>
                <div class="bulle-assistant">
                    <div class="text-muted" style="font-size:11px;">{{ __('Assistant') }}</div>
                    {{ $connecte ? __('Vous nous contactez car vous rencontrez un problème. Il concerne :') : __('Quel est votre problème ?') }}
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach($choix as $cle => $libelle)
                            <input type="radio" class="btn-check" name="concerne" id="concerne_{{ $cle }}" value="{{ $cle }}"
                                   autocomplete="off" required @checked(old('concerne') === $cle)>
                            <label class="btn btn-outline-primary btn-sm" for="concerne_{{ $cle }}">{{ __($libelle) }}</label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Précision (personne, personnes, mairie, commune) --}}
            <div class="d-flex gap-2 etape-suivante" id="etapePrecision">
                <div class="bulle-avatar">🤖</div>
                <div class="bulle-assistant w-100">
                    <div class="text-muted" style="font-size:11px;">{{ __('Assistant') }}</div>
                    @foreach($precisions as $cle => $question)
                        @if(array_key_exists($cle, $choix))
                            <div class="question-precision" data-pour="{{ $cle }}">{{ __($question) }}</div>
                        @endif
                    @endforeach
                    <textarea name="precision" id="champPrecision" class="form-control form-control-sm mt-2" rows="2"
                              maxlength="1000">{{ old('precision') }}</textarea>
                    <small class="text-muted">{{ __('Seulement si l\'assistant vous le demande.') }}</small>
                </div>
            </div>

            {{-- Réponses toutes faites (personne sans compte) --}}
            @unless($connecte)
                <div class="d-flex gap-2 etape-suivante" id="etapeAide">
                    <div class="bulle-avatar">🤖</div>
                    <div class="bulle-assistant">
                        <div class="text-muted" style="font-size:11px;">{{ __('Assistant') }}</div>
                        @foreach($aides as $cle => $texte)
                            <div class="reponse-aide" data-pour="{{ $cle }}">
                                {{ $texte }}
                                @if($cle === 'connexion')
                                    <div class="mt-2"><a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">{{ __('Page de connexion') }}</a></div>
                                @elseif($cle === 'question_mairie')
                                    <div class="mt-2"><a href="{{ route('contact.mairie') }}" class="btn btn-sm btn-outline-primary">{{ __('Contacter votre Mairie') }}</a></div>
                                @endif
                            </div>
                        @endforeach
                        <div class="mt-2">
                            {{ __('Votre problème n\'est pas résolu ?') }}
                            <button type="button" class="btn btn-sm btn-primary ms-1" id="btnConseiller">👤 {{ __('Parler à un conseiller') }}</button>
                        </div>
                    </div>
                </div>
            @endunless

            {{-- Description du problème → envoyée au conseiller --}}
            <div class="d-flex gap-2 etape-suivante" id="etapeDescription">
                <div class="bulle-avatar">🤖</div>
                <div class="bulle-assistant w-100">
                    <div class="text-muted" style="font-size:11px;">{{ __('Assistant') }}</div>
                    {{ __('Veuillez indiquer votre problème le plus précisément possible pour qu\'un conseiller puisse vous répondre au plus vite.') }}

                    @unless($connecte)
                        <div class="row g-2 mt-1">
                            <div class="col-md-6">
                                <input type="text" name="nom" class="form-control form-control-sm" placeholder="{{ __('Nom') }} *"
                                       value="{{ old('nom') }}" required minlength="2" maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="prenom" class="form-control form-control-sm" placeholder="{{ __('Prénom') }} *"
                                       value="{{ old('prenom') }}" required minlength="2" maxlength="100">
                            </div>
                            <div class="col-12">
                                <input type="email" name="email" class="form-control form-control-sm" placeholder="{{ __('E-mail') }} *"
                                       value="{{ old('email') }}" required maxlength="255">
                                <small class="text-muted">{{ __('Vous recevrez à cette adresse le lien pour suivre votre demande.') }}</small>
                            </div>
                        </div>
                    @endunless

                    <textarea name="message" class="form-control mt-2" rows="5" required minlength="5" maxlength="5000"
                              placeholder="{{ __('Décrivez votre problème…') }}">{{ old('message') }}</textarea>
                    <div class="text-end mt-2">
                        <button type="submit" class="btn btn-primary">📨 {{ __('Envoyer au support') }}</button>
                    </div>
                </div>
            </div>

        </div>
    </form>

    <p class="text-muted text-center mt-3" style="font-size:13px;">
        🔒 {{ __('Votre demande reste confidentielle : seule l\'équipe MGDS la lit.') }}
    </p>
</div>

<script>
(function () {
    const form = document.getElementById('assistantSupport');
    form.classList.add('js');

    const precisions = @json(array_keys($precisions));
    const aides      = @json(array_keys($aides));
    const etape      = id => document.getElementById(id);
    const afficher   = (el, oui) => { if (el) el.style.display = oui ? 'flex' : 'none'; };

    function suivre(choix, versConseiller) {
        const avecPrecision = precisions.includes(choix);
        const avecAide      = ! versConseiller && etape('etapeAide') && aides.includes(choix);

        afficher(etape('etapePrecision'), avecPrecision);
        etape('champPrecision').required = avecPrecision;
        document.querySelectorAll('.question-precision').forEach(q => q.style.display = q.dataset.pour === choix ? '' : 'none');

        afficher(etape('etapeAide'), avecAide);
        document.querySelectorAll('.reponse-aide').forEach(r => r.style.display = r.dataset.pour === choix ? '' : 'none');

        // Sans réponse toute faite, on passe directement au conseiller
        afficher(etape('etapeDescription'), ! avecAide);
    }

    form.querySelectorAll('input[name="concerne"]').forEach(radio => {
        radio.addEventListener('change', () => suivre(radio.value, false));
    });

    const btn = etape('btnConseiller');
    if (btn) {
        btn.addEventListener('click', () => {
            const choix = form.querySelector('input[name="concerne"]:checked');
            suivre(choix ? choix.value : '', true);
            etape('etapeDescription').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    // Retour après une erreur : on rouvre directement l'étape de description
    const coche = form.querySelector('input[name="concerne"]:checked');
    if (coche) {
        suivre(coche.value, true);
    }
})();
</script>
@endsection
