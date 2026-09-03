@extends('layouts.app')

@php
    use App\Models\PlanningLigne;
    $joursLabels = [1 => 'LUNDI', 2 => 'MARDI', 3 => 'MERCREDI', 4 => 'JEUDI', 5 => 'VENDREDI', 6 => 'SAMEDI', 7 => 'DIMANCHE'];
@endphp

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('planning.index') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">
        ← {{ __('Retour à la liste des plannings') }}
    </a>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">🕒 {{ __('Planning') }} {{ $planning->libelle() }}</h1>
            <p class="text-muted mb-0" style="font-size:13px;">{{ $planning->periodeLabel() }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($peutGerer)
                <a href="{{ route('planning.pdf', $planning) }}" class="btn btn-outline-dark">📄 {{ __('Télécharger en PDF') }}</a>
                <button type="submit" form="formPlanning" class="btn btn-primary">{{ __('Enregistrer') }}</button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('planning.update', $planning) }}" id="formPlanning">
        @csrf @method('PUT')

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle text-center" style="font-size:12px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start" style="min-width:170px;">{{ __('EMPLOYÉ(E)') }}</th>
                            <th style="min-width:80px;">{{ __('DURÉE') }}</th>
                            @foreach($joursLabels as $numero => $label)
                                <th style="min-width:135px;">
                                    {{ $label }}<br>
                                    <span class="text-muted fw-normal">{{ $dates[$numero]->format('d/m/Y') }}</span>
                                </th>
                            @endforeach
                            <th style="min-width:70px;">{{ __('TOTAL') }}</th>
                            <th style="min-width:70px;">{{ __('VAR.') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($lignes as $ligne)
                        @php
                            $total     = $ligne->totalMinutes($dates);
                            $variation = $ligne->variationMinutes($dates);
                        @endphp
                        <tr>
                            <td class="text-start">
                                <span class="fw-semibold">{{ $ligne->user?->full_name }}</span>
                                <span class="d-block text-muted">{{ $ligne->user?->service_label }}</span>
                                @if($ligne->estSigne())
                                    <span class="badge bg-success mt-1" style="font-size:10px;">
                                        ✅ {{ __('Signé le') }} {{ $ligne->signe_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary mt-1" style="font-size:10px;">{{ __('Non signé') }}</span>
                                @endif
                            </td>

                            <td>
                                @if($peutGerer)
                                    <input type="text" class="form-control form-control-sm text-center"
                                           name="lignes[{{ $ligne->id }}][duree_contrat]"
                                           value="{{ $ligne->dureeContratSaisie() }}"
                                           placeholder="35:00" pattern="\d{1,2}:[0-5]\d">
                                @else
                                    {{ PlanningLigne::formatMinutes($ligne->duree_contrat) }}
                                @endif
                            </td>

                            @foreach($joursLabels as $numero => $label)
                                @php
                                    $journee = $ligne->journee($numero);
                                    $absence = $ligne->absencePour($dates[$numero]);
                                @endphp
                                <td class="{{ $absence ? 'table-warning' : ($journee['repos'] ? 'table-secondary' : '') }}">
                                    @if($absence)
                                        <span class="fw-bold text-uppercase" style="font-size:10px;color:#856404;">
                                            {{ $absence->motifLabel() }}
                                        </span>
                                    @else
                                        @for($i = 0; $i < PlanningLigne::CRENEAUX_PAR_JOUR; $i++)
                                            <div class="d-flex justify-content-center gap-1 mb-1">
                                                @foreach([0, 1] as $borne)
                                                    @php $valeur = $journee['creneaux'][$i][$borne] ?? ''; @endphp
                                                    @if($peutGerer)
                                                        <input type="time" class="form-control form-control-sm p-1"
                                                               style="width:78px;font-size:11px;"
                                                               name="lignes[{{ $ligne->id }}][jours][{{ $numero }}][creneaux][{{ $i }}][{{ $borne }}]"
                                                               value="{{ $valeur }}" @disabled($journee['repos'])>
                                                    @else
                                                        <span>{{ $valeur ?: '—' }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endfor

                                        @if($peutGerer)
                                            <label class="d-flex align-items-center justify-content-center gap-1 mb-0" style="font-size:10px;">
                                                <input type="checkbox" class="case-repos"
                                                       name="lignes[{{ $ligne->id }}][jours][{{ $numero }}][repos]"
                                                       value="1" @checked($journee['repos'])>
                                                {{ __('Repos') }}
                                            </label>
                                        @elseif($journee['repos'])
                                            <span class="fw-bold text-uppercase" style="font-size:10px;">{{ __('Repos') }}</span>
                                        @endif
                                    @endif

                                    <span class="d-block fw-bold" style="font-size:10px;color:#e53e3e;">
                                        {{ PlanningLigne::formatMinutes($ligne->minutesJour($numero, $dates[$numero])) }}
                                    </span>
                                </td>
                            @endforeach

                            <td class="fw-bold" style="color:#2b6cb0;">{{ PlanningLigne::formatMinutes($total) }}</td>
                            <td class="fw-bold {{ $variation !== null && $variation < 0 ? 'text-danger' : 'text-success' }}">
                                {{ PlanningLigne::formatMinutes($variation) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-muted py-4">{{ __('Aucun agent sur ce planning.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- ── Signature de l'agent ── --}}
    @php $maLigne = $lignes->firstWhere('user_id', $moi->id); @endphp
    @if($maLigne)
        <div class="card shadow-sm mt-3">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong>✍️ {{ __('Votre signature') }}</strong>
                    <div class="text-muted" style="font-size:13px;">
                        @if($maLigne->estSigne())
                            {{ __('Signé le') }} {{ $maLigne->signe_at->format('d/m/Y à H:i') }}.
                            {{ __('Toute modification du planning annule la signature.') }}
                        @else
                            {{ __('Vérifiez vos heures, puis signez votre semaine.') }}
                        @endif
                    </div>
                </div>
                @unless($maLigne->estSigne())
                    <form method="POST" action="{{ route('planning.signer', $planning) }}">
                        @csrf
                        <button class="btn btn-success">✍️ {{ __('Signer ma semaine') }}</button>
                    </form>
                @endunless
            </div>
        </div>
    @endif
</div>

@if($peutGerer)
<script>
    // Un jour de repos n'a pas d'horaires à saisir
    document.querySelectorAll('.case-repos').forEach(caseRepos => {
        const majCellule = () => caseRepos.closest('td')
            .querySelectorAll('input[type=time]')
            .forEach(champ => { champ.disabled = caseRepos.checked; });

        caseRepos.addEventListener('change', majCellule);
    });
</script>
@endif
@endsection
