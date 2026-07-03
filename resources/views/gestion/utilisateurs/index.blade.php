@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <h1 class="h3 mb-3">Gestion de la Mairie — {{ $mairie->nom }}</h1>

    @include('gestion.partials.onglets')

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        {{-- Recherche service ou nom prénom --}}
        <div style="max-width:400px;flex:1;">
            <div class="search-input-group">
                <span class="search-icon">🔍</span>
                <input type="text" id="userSearch" class="search-input"
                       placeholder="Recherche service ou nom prénom…" autocomplete="off">
            </div>
        </div>
        <a href="{{ route('gestion.utilisateurs.create') }}" class="btn btn-primary">+ Ajouter</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Réf</th>
                        <th>Service</th>
                        <th>Utilisateur</th>
                        <th>Statut</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody id="usersBody">
                @forelse($users as $user)
                    <tr data-search="{{ strtolower(\Illuminate\Support\Str::ascii($user->service_label . ' ' . $user->prenom . ' ' . $user->nom . ' ' . $user->username . ' ' . $user->grade_label)) }}">
                        <td class="fw-semibold text-muted">{{ $user->reference }}</td>
                        <td style="font-size:13px;">{{ $user->service_label }}</td>
                        <td>
                            {{ $user->username }}
                            @if($user->id === auth()->id())
                                <span class="badge bg-secondary ms-1" style="font-size:10px">Vous</span>
                            @endif
                        </td>
                        <td>
                            @php $gradeColors = [1 => 'danger', 2 => 'primary', 3 => 'info', 4 => 'warning', 5 => 'secondary']; @endphp
                            <span class="badge bg-{{ $gradeColors[$user->grade] ?? 'secondary' }}">{{ $user->grade_label }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('gestion.utilisateurs.edit', $user) }}" class="btn btn-outline-primary">✏️ Modifier</a>
                                @if($user->must_change_password)
                                    <a href="{{ route('gestion.utilisateurs.courrier', $user) }}" class="btn btn-outline-secondary" title="Courrier identifiants">📄 PDF</a>
                                @endif
                                @if($user->id !== auth()->id())
                                <form action="{{ route('gestion.utilisateurs.destroy', $user) }}" method="POST"
                                      onsubmit="return confirm('⚠️ Supprimer l\'utilisateur « {{ addslashes($user->username) }} » ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger" type="submit">🗑 Supprimer</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div id="noResults" class="text-center text-muted py-4 d-none">Aucun résultat.</div>
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
