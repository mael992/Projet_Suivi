@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    @include('admin.partials.onglets')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Dossiers : Réception (à traiter), Réponse, Clôturé --}}
    <ul class="nav nav-pills mb-3">
        @foreach(\App\Models\SupportDemande::STATUTS as $statut => $libelle)
            <li class="nav-item">
                <a class="nav-link {{ $dossier === $statut ? 'active' : '' }}"
                   href="{{ route('admin.messages.index', ['dossier' => $statut]) }}">
                    {{ __($libelle) }}
                    <span class="badge {{ $statut === 'reception' && $compteurs[$statut] ? 'bg-danger' : 'bg-secondary' }}">{{ $compteurs[$statut] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Demande') }}</th>
                        <th>{{ __('De') }}</th>
                        <th>{{ __('Le problème concerne') }}</th>
                        <th>{{ __('Dernière activité') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($demandes as $d)
                        <tr style="cursor:pointer;" onclick="window.location='{{ route('admin.messages.show', $d) }}'">
                            <td><a href="{{ route('admin.messages.show', $d) }}" class="text-decoration-none fw-semibold">{{ $d->reference }}</a></td>
                            <td>
                                {{ $d->libelleDemandeur() }}
                                @unless($d->avec_compte)<span class="badge bg-warning text-dark">{{ __('Sans compte') }}</span>@endunless
                            </td>
                            <td>{{ $d->concerne_label }}</td>
                            <td><small class="text-muted">{{ $d->updated_at->format('d/m/Y H:i') }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Aucune demande dans ce dossier.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $demandes->links() }}</div>

</div>
@endsection
