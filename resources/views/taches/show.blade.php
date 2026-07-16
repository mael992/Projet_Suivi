@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:820px;">

    @php
        $user          = auth()->user();
        $estCreateur   = $user->isAdmin() || $tache->created_by === $user->id;
        $estResponsable = $tache->user_id === $user->id;
        $doitChoisir   = $estResponsable && $tache->enAttentePriseEnCharge();
        $peutCloturer  = $tache->peutEtreClotureePar($user);
    @endphp

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Tâche {{ $tache->reference }}</h1>
        <div class="d-flex gap-2">
            @if($estCreateur)
                <a href="{{ route('taches.edit', $tache) }}" class="btn btn-sm btn-outline-primary">✏️ Modifier</a>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">← Retour</a>
        </div>
    </div>

    {{-- ── Prise en charge (responsable, tâche en attente) ── --}}
    @if($doitChoisir)
    <div class="card shadow-sm mb-3 border-warning" style="border-width:2px;">
        <div class="card-body">
            <h2 class="h6 mb-2">📌 {{ __('Cette tâche vous a été affectée : que souhaitez-vous faire ?') }}</h2>
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" action="{{ route('taches.prise-en-charge', $tache) }}">
                    @csrf
                    <input type="hidden" name="mode" value="responsable">
                    <button type="submit" class="btn btn-primary">✅ {{ __('Je prends en charge cette tâche') }}</button>
                </form>
                <button type="button" class="btn btn-outline-primary"
                        onclick="document.getElementById('blocSubstitution').classList.toggle('d-none')">
                    🔄 {{ __('Je substitue à un employé') }}
                </button>
            </div>

            {{-- Substitution en cours --}}
            <div id="blocSubstitution" class="d-none mt-3 border-top pt-3">
                <form method="POST" action="{{ route('taches.prise-en-charge', $tache) }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="mode" value="substitution">
                    <div class="col-md-7">
                        <label class="form-label mb-1" style="font-size:13px;">{{ __('Substitution en cours — veuillez renseigner une personne « employé »') }}</label>
                        <select name="substitut_id" class="form-select form-select-sm" required>
                            <option value="">— {{ __('Sélectionnez') }} —</option>
                            @foreach($employes as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->username }} ({{ $emp->fonction ?: $emp->grade_label }})</option>
                            @endforeach
                        </select>
                        @if($employes->isEmpty())
                            <small class="text-danger">{{ __('Aucun employé disponible dans ce service.') }}</small>
                        @endif
                    </div>
                    <div class="col-md-5">
                        <button type="submit" class="btn btn-sm btn-dark" @disabled($employes->isEmpty())>
                            {{ __('Substituer et prévenir par email') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3" style="font-size:14px;">
                <div class="col-md-6"><strong>Mairie :</strong> {{ $tache->mairie?->nom ?? '—' }}</div>
                <div class="col-md-6"><strong>Équipe :</strong> {{ $tache->service_label }}</div>
                <div class="col-md-6"><strong>Créée le :</strong> {{ $tache->created_at->format('d/m/Y H:i') }}</div>
                <div class="col-md-6"><strong>Créée par :</strong> {{ $tache->createur?->username ?? '—' }}</div>
                <div class="col-md-6">
                    <strong>{{ __('Responsable') }} :</strong> {{ $tache->assigne?->username ?? '—' }}
                    @if($tache->enAttentePriseEnCharge())
                        <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">{{ __('En attente de prise en charge') }}</span>
                    @elseif($tache->prise_en_charge === 'responsable')
                        <span class="badge bg-success ms-1" style="font-size:10px;">{{ __('Prise en charge') }}</span>
                    @endif
                </div>
                @if($tache->prise_en_charge === 'substitution')
                    <div class="col-md-6">
                        <strong>🔄 {{ __('Substituée à') }} :</strong> {{ $tache->substitut?->username ?? '—' }}
                    </div>
                @endif
                <div class="col-md-6">
                    <strong>Statut :</strong>
                    @php $couleurs = ['ouvert' => 'secondary', 'en_cours' => 'warning', 'fait' => 'success']; @endphp
                    <span class="badge bg-{{ $couleurs[$tache->statut] ?? 'secondary' }}">{{ $tache->statut_label }}</span>
                </div>
                <div class="col-md-6"><strong>Clôture prévue :</strong> {{ $tache->date_butoir->format('d/m/Y') }}</div>
                <div class="col-md-6"><strong>Clôturée le :</strong> {{ $tache->date_cloture?->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2" style="font-size:13px;font-weight:600;">{{ __('Photo de la tâche à faire') }}</div>
                <div class="card-body text-center">
                    @if($tache->photo_avant)
                        <a href="{{ asset('storage/' . $tache->photo_avant) }}" target="_blank">
                            <img src="{{ asset('storage/' . $tache->photo_avant) }}" class="img-fluid" style="max-height:260px;border-radius:8px;">
                        </a>
                    @else
                        <span class="text-muted">Aucune photo</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2" style="font-size:13px;font-weight:600;">{{ __('Photo de la tâche une fois finie') }}</div>
                <div class="card-body text-center">
                    @if($tache->photo_apres)
                        <a href="{{ asset('storage/' . $tache->photo_apres) }}" target="_blank">
                            <img src="{{ asset('storage/' . $tache->photo_apres) }}" class="img-fluid" style="max-height:260px;border-radius:8px;">
                        </a>
                    @else
                        <span class="text-muted">Aucune photo</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($tache->description_instruction)
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2" style="font-size:13px;font-weight:600;">Description & remarques (d'instruction)</div>
            <div class="card-body" style="font-size:14px;white-space:pre-wrap;">{{ $tache->description_instruction }}</div>
        </div>
    @endif

    @if($tache->description_cloture)
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2" style="font-size:13px;font-weight:600;">Description & remarques (de clôture)</div>
            <div class="card-body" style="font-size:14px;white-space:pre-wrap;">{{ $tache->description_cloture }}</div>
        </div>
    @endif

    {{-- ── Clôture (responsable ayant pris en charge, ou substitut) ── --}}
    @if($peutCloturer)
    <div class="card shadow-sm mb-3 border-success" style="border-width:2px;">
        <div class="card-body">
            <h2 class="h6 mb-2">🏁 {{ __('Clôturer la tâche') }}</h2>
            <form method="POST" action="{{ route('taches.cloturer', $tache) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-2">
                    <label class="form-label mb-1" style="font-size:13px;">
                        {{ __('Photo de la tâche une fois finie') }}
                        @if($tache->photo_avant && ! $tache->photo_apres) <span class="text-danger">*</span> @endif
                    </label>
                    <input type="file" name="photo_apres" class="form-control form-control-sm" accept="image/*"
                           @if($tache->photo_avant && ! $tache->photo_apres) required @endif>
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1" style="font-size:13px;">{{ __('Commentaire de clôture') }} <span class="text-danger">*</span></label>
                    <textarea name="description_cloture" class="form-control form-control-sm" rows="3" required
                              placeholder="{{ __('Décrivez ce qui a été fait…') }}">{{ old('description_cloture') }}</textarea>
                </div>
                <button type="submit" class="btn btn-success"
                        onclick="return confirm('{{ __('Clôturer définitivement cette tâche ?') }}')">
                    🏁 {{ __('Clôturer la tâche') }}
                </button>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
