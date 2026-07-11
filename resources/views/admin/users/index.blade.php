@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    @include('admin.partials.onglets')

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="h5 mb-0">{{ __('Gestion des utilisateurs (toutes mairies)') }}</h2>
        <a href="{{ route('users.create') }}" class="btn btn-primary">{{ __('+ Ajouter') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    {{-- Barre de recherche --}}
    <div class="mb-3" style="max-width:420px">
        <div class="search-input-group">
            <span class="search-icon">🔍</span>
            <input type="text" id="userSearch" class="search-input"
                   placeholder="Recherche : nom, mairie, équipe…" autocomplete="off">
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Réf</th>
                        <th>Utilisateur</th>
                        <th>{{ __('Mairie') }}</th>
                        <th>{{ __('Équipe') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody id="usersBody">
                @forelse($users as $user)
                    <tr data-search="{{ strtolower(\Illuminate\Support\Str::ascii($user->username . ' ' . $user->prenom . ' ' . $user->nom . ' ' . ($user->email ?? '') . ' ' . ($user->mairie?->nom ?? 'admin') . ' ' . $user->service_label . ' ' . $user->grade_label)) }}">
                        <td class="fw-semibold text-muted">{{ $user->reference ?? $user->id }}</td>
                        <td>
                            {{ $user->username }}
                            @if($user->id === auth()->id())
                                <span class="badge bg-secondary ms-1" style="font-size:10px">Vous</span>
                            @endif
                        </td>
                        <td>{{ $user->mairie?->nom ?? '—' }}</td>
                        <td style="font-size:13px;">{{ $user->service_label }}</td>
                        <td>
                            @if($user->isAdmin())
                                <span class="badge bg-danger">Admin</span>
                            @else
                                @php $gradeColors = [1 => 'danger', 2 => 'primary', 3 => 'info', 4 => 'secondary']; @endphp
                                <span class="badge bg-{{ $gradeColors[$user->grade] ?? 'secondary' }}">{{ $user->grade_label }}</span>
                            @endif
                        </td>
                        <td style="font-size:13px;">{{ $user->email ?? '—' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary">✏️ Modifier</a>
                                @if($user->must_change_password && ! $user->isAdmin())
                                    <a href="{{ route('users.courrier', $user) }}" class="btn btn-outline-secondary">📄 PDF</a>
                                @endif
                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                      onsubmit="return confirm('⚠️ Supprimer l\'utilisateur « {{ addslashes($user->username) }} » ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger" type="submit">🗑 Supprimer</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Aucun utilisateur.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            <div id="noResults" class="text-center text-muted py-4 d-none">{{ __('Aucun résultat.') }}</div>
        </div>
    </div>

</div>

<script>
document.getElementById('userSearch').addEventListener('input', function () {
    const q     = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    const rows  = document.querySelectorAll('#usersBody tr');
    let visible = 0;

    rows.forEach(row => {
        const data = row.dataset.search ?? '';
        const show = !q || data.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('noResults').classList.toggle('d-none', visible > 0);
});
</script>
@endsection
