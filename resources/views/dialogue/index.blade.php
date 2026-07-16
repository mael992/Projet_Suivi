@extends('layouts.app')

@php use App\Models\DialogueQuestion; @endphp

@section('content')
@php
    $sectionActive = request('section');
    $sectionActive = array_key_exists($sectionActive, DialogueQuestion::SECTIONS) ? $sectionActive : 'accueil';
@endphp

<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>
    <h1 class="h3 mb-3">💬 {{ __('Boîte de dialogue') }}</h1>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3">

        {{-- ── Menu de gauche ── --}}
        <div class="col-12 col-md-4 col-lg-3">
            <div class="card shadow-sm">
                <div class="card-header py-2 text-white fw-semibold" style="background:var(--brand-dark);font-size:14px;">
                    {{ __('Navigation') }}
                </div>
                <div class="list-group list-group-flush" id="dialogueMenu">
                    <button type="button" class="list-group-item list-group-item-action dlg-item {{ $sectionActive === 'accueil' ? 'active' : '' }}"
                            data-section="accueil" onclick="montrerSection('accueil', this)" style="font-size:14px;">
                        💬 {{ __('Boîte de dialogue') }}
                    </button>
                    @foreach(DialogueQuestion::SECTIONS as $cle => $label)
                        <button type="button" class="list-group-item list-group-item-action dlg-item {{ $sectionActive === $cle ? 'active' : '' }}"
                                data-section="{{ $cle }}" onclick="montrerSection('{{ $cle }}', this)" style="font-size:14px;">
                            {{ __($label) }}
                            @php $nb = ($questions[$cle] ?? collect())->count(); @endphp
                            @if($nb > 0)<span class="badge bg-secondary bg-opacity-25 text-secondary float-end">{{ $nb }}</span>@endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Contenu ── --}}
        <div class="col-12 col-md-8 col-lg-9">
            <div class="card shadow-sm" style="min-height:420px;">
                <div class="card-body position-relative d-flex flex-column">

                    {{-- Accueil --}}
                    <div class="dlg-section {{ $sectionActive === 'accueil' ? '' : 'd-none' }}" data-section="accueil">
                        <h2 class="h5 mb-3" style="color:var(--brand);border-bottom:2px solid var(--gold);padding-bottom:8px;">
                            {{ __('Bienvenue sur la boîte de dialogue') }}
                        </h2>
                        <p style="font-size:15px;line-height:1.6;" class="text-muted">
                            {{ __('Bienvenue sur l\'espace d\'entraide entre mairies. Ici, vous retrouverez une assistance collaborative pour chaque application à laquelle vous avez accès sur la plateforme') }}
                            <strong>M-GDS</strong>.
                        </p>
                    </div>

                    {{-- Sections par application --}}
                    @foreach(DialogueQuestion::SECTIONS as $cle => $label)
                        <div class="dlg-section {{ $sectionActive === $cle ? '' : 'd-none' }} d-flex flex-column flex-grow-1" data-section="{{ $cle }}">
                            <h2 class="h5 mb-3" style="color:var(--brand);border-bottom:2px solid var(--gold);padding-bottom:8px;">
                                {{ __($label) }} — ❓ {{ __('Questions & Entraide') }}
                            </h2>

                            @forelse($questions[$cle] ?? [] as $question)
                                <div class="border rounded p-3 mb-3" style="background:#f8fafc;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="text-muted mb-1" style="font-size:12px;">
                                            {{ __('Posée par') }} <strong>{{ $question->auteur?->username ?? '—' }}</strong>
                                            @if($question->auteur?->mairie) ({{ $question->auteur->mairie->nom }}) @endif
                                            — {{ $question->created_at->format('d/m/Y H:i') }}
                                        </div>
                                        @if(auth()->user()->isAdmin() || $question->user_id === auth()->id())
                                            <form action="{{ route('dialogue.questions.destroy', $question) }}" method="POST"
                                                  onsubmit="return confirm('{{ __('Supprimer cette question et ses réponses ?') }}')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:11px;">🗑</button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="fw-semibold mb-2" style="font-size:14px;white-space:pre-wrap;">{{ $question->texte }}</div>

                                    <div class="ps-3 border-start border-2 d-flex flex-column gap-2">
                                        @foreach($question->reponses as $reponse)
                                            <div class="rounded px-2 py-1" style="background:#eef2f6;font-size:13px;">
                                                <div class="text-muted fw-semibold" style="font-size:11px;">
                                                    👤 {{ $reponse->auteur?->username ?? '—' }}
                                                    @if($reponse->auteur?->mairie) ({{ $reponse->auteur->mairie->nom }}) @endif
                                                    — {{ $reponse->created_at->format('d/m/Y H:i') }}
                                                </div>
                                                <div style="white-space:pre-wrap;">{{ $reponse->texte }}</div>
                                            </div>
                                        @endforeach

                                        <form method="POST" action="{{ route('dialogue.reponses.store', $question) }}" class="d-flex gap-2">
                                            @csrf
                                            <input type="text" name="texte" class="form-control form-control-sm"
                                                   placeholder="{{ __('Votre réponse…') }} *" required maxlength="3000">
                                            <button class="btn btn-sm text-white" style="background:var(--brand);">{{ __('Répondre') }}</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted fst-italic" style="font-size:14px;">
                                    {{ __('Aucune question posée pour le moment. Soyez le premier !') }}
                                </p>
                            @endforelse
                        </div>
                    @endforeach

                    {{-- Bouton d'ajout (masqué sur l'accueil) --}}
                    <div class="text-end mt-auto pt-3 {{ $sectionActive === 'accueil' ? 'd-none' : '' }}" id="btnAjouterWrap">
                        <button type="button" class="btn btn-success rounded-pill fw-semibold" onclick="ouvrirModalQuestion()">
                            + {{ __('Ajouter une Question') }}
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal d'ajout de question (l'utilisateur connecté est repris automatiquement) --}}
<div class="modal fade" id="modalQuestion" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dialogue.questions.store') }}" class="modal-content">
            @csrf
            <div class="modal-header py-2">
                <h5 class="modal-title" style="font-size:16px;">{{ __('Poser une nouvelle question') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" style="font-size:12px;">
                    👤 {{ __('Publiée en tant que') }} <strong>{{ auth()->user()->username }}</strong>
                    @if(auth()->user()->mairie) ({{ auth()->user()->mairie->nom }}) @endif
                </p>
                <input type="hidden" name="section" id="questionSection" value="">
                <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Description / Question') }} *</label>
                <textarea name="texte" class="form-control" rows="4" required maxlength="3000"
                          placeholder="{{ __('Décrivez votre problème ou question…') }}"></textarea>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                <button type="submit" class="btn btn-success btn-sm">{{ __('Ajouter') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
let sectionCourante = @json($sectionActive);

function montrerSection(section, btn) {
    sectionCourante = section;
    document.querySelectorAll('.dlg-item').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.dlg-section').forEach(s =>
        s.classList.toggle('d-none', s.dataset.section !== section));
    document.getElementById('btnAjouterWrap').classList.toggle('d-none', section === 'accueil');
}

function ouvrirModalQuestion() {
    document.getElementById('questionSection').value = sectionCourante;
    new bootstrap.Modal(document.getElementById('modalQuestion')).show();
}
</script>
@endsection
