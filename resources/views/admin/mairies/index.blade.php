@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    @include('admin.partials.onglets')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="h5 mb-0">{{ __('Gestion des accès mairie') }}</h2>
        <a href="{{ route('mairies.create') }}" class="btn btn-primary">+ Ajouter une mairie</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Nom de la mairie</th>
                        <th>Adresse email</th>
                        <th>{{ __('Téléphone') }}</th>
                        <th>Fin d'abonnement</th>
                        <th class="text-center">Utilisateurs</th>
                        <th class="text-center">Observateurs</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($mairies as $mairie)
                    <tr>
                        <td class="fw-semibold">{{ $mairie->nom }}</td>
                        <td>{{ $mairie->email }}</td>
                        <td>{{ $mairie->telephone ? '(' . $mairie->telephone_indicatif . ') ' . $mairie->telephone : '—' }}</td>
                        <td>
                            @if($mairie->date_fin_abonnement)
                                {{ $mairie->date_fin_abonnement->format('d/m/Y') }}
                                @if($mairie->abonnementExpire())
                                    <span class="badge bg-danger ms-1">Expiré</span>
                                @elseif($mairie->date_fin_abonnement->diffInDays(now()) < 30)
                                    <span class="badge bg-warning text-dark ms-1">Bientôt</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-center">{{ $mairie->users_count }}</td>
                        <td class="text-center">{{ $mairie->observateurs_count }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('mairies.edit', $mairie) }}" class="btn btn-outline-primary">✏️ Modifier</a>
                                <form action="{{ route('mairies.destroy', $mairie) }}" method="POST"
                                      onsubmit="return confirm('⚠️ Supprimer la mairie « {{ addslashes($mairie->nom) }} » ?\n\nTous ses utilisateurs et toutes ses tâches seront supprimés définitivement.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger" type="submit">🗑 Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune mairie enregistrée.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
