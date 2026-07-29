@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4" style="max-width:1100px;">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">🆘 {{ __('Besoin d\'aide') }}</h1>
            <p class="text-muted mb-0" style="font-size:14px;">
                {{ __('Guide complet des applications MGDS, avec les exemples des e-mails que vous pouvez recevoir.') }}
            </p>
        </div>
        <a href="{{ route('aide.pdf') }}" class="btn btn-outline-dark">⬇ {{ __('Télécharger en PDF') }}</a>
    </div>

    {{-- Recherche dans le guide --}}
    <div class="mb-3" style="max-width:460px;">
        <div class="search-input-group">
            <span class="search-icon">🔍</span>
            <input type="text" id="aideSearch" class="search-input" autocomplete="off"
                   placeholder="{{ __('Rechercher dans l\'aide (ex : tâche, rappel, ticket…)') }}">
        </div>
    </div>

    <div class="row g-3">
        {{-- Sommaire --}}
        <div class="col-12 col-md-4 col-lg-3">
            <div class="card shadow-sm" style="position:sticky;top:80px;">
                <div class="card-header py-2 text-white fw-semibold" style="background:var(--brand-dark);font-size:14px;">
                    {{ __('Sommaire') }}
                </div>
                <div class="list-group list-group-flush">
                    @foreach($sections as $cle => $section)
                        <a href="#aide-{{ $cle }}" class="list-group-item list-group-item-action" style="font-size:14px;">
                            {{ $section['icone'] }} {{ __($section['titre']) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Contenu --}}
        <div class="col-12 col-md-8 col-lg-9">
            @foreach($sections as $cle => $section)
                <div class="card shadow-sm mb-3 aide-section" id="aide-{{ $cle }}"
                     data-recherche="{{ strtolower(\Illuminate\Support\Str::ascii($section['titre'] . ' ' . $section['intro'] . ' ' . implode(' ', $section['etapes']) . ' ' . implode(' ', $section['astuces']))) }}">
                    <div class="card-body">
                        <h2 class="h5 mb-2" style="color:var(--brand);border-bottom:2px solid var(--gold);padding-bottom:8px;">
                            {{ $section['icone'] }} {{ __($section['titre']) }}
                        </h2>
                        <p style="font-size:14px;">{{ $section['intro'] }}</p>

                        <h3 class="h6 mt-3 mb-2">📋 {{ __('Comment faire') }}</h3>
                        <ol style="font-size:14px;">
                            @foreach($section['etapes'] as $etape)
                                <li class="mb-1">{{ $etape }}</li>
                            @endforeach
                        </ol>

                        @if($section['astuces'])
                            <div class="rounded p-2 mt-2" style="background:#f3ead8;border-left:4px solid var(--gold);font-size:13px;">
                                <strong>💡 {{ __('Bon à savoir') }}</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach($section['astuces'] as $astuce)
                                        <li>{{ $astuce }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($section['emails'])
                            <h3 class="h6 mt-3 mb-2">✉️ {{ __('E-mails que vous pouvez recevoir') }}</h3>
                            @foreach($section['emails'] as $email)
                                <div class="border rounded mb-2" style="overflow:hidden;">
                                    <div class="px-2 py-1 text-muted" style="background:#f1f3f6;font-size:12px;">
                                        {{ __('Quand') }} : {{ $email['quand'] }}
                                    </div>
                                    <div class="p-2" style="font-size:13px;">
                                        <div class="fw-semibold mb-1">{{ __('Objet') }} : {{ $email['objet'] }}</div>
                                        <div class="rounded p-2" style="background:#fbfbfb;border:1px solid #eee;white-space:pre-wrap;font-family:'Segoe UI',sans-serif;">{{ $email['corps'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach

            <div id="aideNoResult" class="text-center text-muted py-4 d-none">{{ __('Aucune rubrique ne correspond à la recherche.') }}</div>
        </div>
    </div>
</div>

<script>
document.getElementById('aideSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    let visibles = 0;
    document.querySelectorAll('.aide-section').forEach(s => {
        const show = !q || (s.dataset.recherche ?? '').includes(q);
        s.classList.toggle('d-none', !show);
        if (show) visibles++;
    });
    document.getElementById('aideNoResult').classList.toggle('d-none', visibles > 0);
});
</script>
@endsection
