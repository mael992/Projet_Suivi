@extends('layouts.app')

@section('content')
@php
    $ongletActif = request('onglet') === 'notes' ? 'notes' : 'calendrier';
    $jours       = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    $premierJour = (int) $mois->copy()->startOfMonth()->isoWeekday(); // 1 = lundi
    $nbJours     = $mois->daysInMonth;
    $notesParDossier = $notes->groupBy(fn ($n) => $n->dossier ?: '');
@endphp

<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>
    <h1 class="h3 mb-3">🗓️ {{ __('Pense-bête') }}</h1>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    {{-- ── Onglets ── --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link {{ $ongletActif === 'calendrier' ? 'active' : '' }}" onclick="montrerOnglet('calendrier', this)">
                📅 {{ __('Calendrier') }}
                @if($badgeCalendrier > 0)<span class="bulle-notif ms-1" title="{{ __('Rappels aujourd\'hui') }}">{{ $badgeCalendrier }}</span>@endif
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $ongletActif === 'notes' ? 'active' : '' }}" onclick="montrerOnglet('notes', this)">
                🗒️ {{ __('Notes') }}
                @if($badgeNotes > 0)<span class="bulle-notif ms-1" title="{{ __('Notes à rappeler aujourd\'hui') }}">{{ $badgeNotes }}</span>@endif
            </button>
        </li>
    </ul>

    {{-- ════════ ONGLET CALENDRIER ════════ --}}
    <div id="ongletCalendrier" class="{{ $ongletActif === 'calendrier' ? '' : 'd-none' }}">

        {{-- Recherche : de date à date ou par mot --}}
        <form method="GET" action="{{ route('pensebete.index') }}" class="card shadow-sm mb-3">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1" style="font-size:12px;">🔍 {{ __('Recherche par mot') }}</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                               placeholder="{{ __('ex : rendez-vous, réunion…') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Du') }}</label>
                        <input type="date" name="du" value="{{ request('du') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Au') }}</label>
                        <input type="date" name="au" value="{{ request('au') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-1">
                        <button class="btn btn-sm btn-dark w-100">{{ __('Rechercher') }}</button>
                        @if($enRecherche)
                            <a href="{{ route('pensebete.index') }}" class="btn btn-sm btn-outline-secondary">✕</a>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        {{-- Résultats de recherche --}}
        @if($enRecherche)
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2 fw-semibold" style="font-size:13px;">{{ __('Résultats') }} ({{ $resultats->count() }})</div>
                <ul class="list-group list-group-flush">
                    @forelse($resultats as $rappel)
                        <li class="list-group-item d-flex justify-content-between align-items-center" style="font-size:14px;">
                            <span>
                                <strong>{{ $rappel->date_rappel->format('d/m/Y') }}</strong>
                                — {{ Str::limit($rappel->texte, 90) ?: __('(sans texte)') }}
                                @if($rappel->fichier)
                                    <a href="{{ asset('storage/' . $rappel->fichier) }}" target="_blank">📎</a>
                                @endif
                            </span>
                            <form action="{{ route('pensebete.rappels.destroy', $rappel) }}" method="POST"
                                  onsubmit="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer ce rappel ?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0">🗑</button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item text-muted" style="font-size:14px;">{{ __('Aucun résultat.') }}</li>
                    @endforelse
                </ul>
            </div>
        @endif

        {{-- Navigation du mois + bouton ajouter --}}
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('pensebete.index', ['mois' => $mois->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary">←</a>
                <span class="fw-semibold text-capitalize" style="min-width:150px;text-align:center;">
                    {{ $mois->locale(app()->getLocale())->translatedFormat('F Y') }}
                </span>
                <a href="{{ route('pensebete.index', ['mois' => $mois->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary">→</a>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="ouvrirModalRappel()">➕ {{ __('Ajouter') }}</button>
        </div>

        {{-- Grille du calendrier --}}
        <div class="card shadow-sm">
            <div class="card-body p-2">
                <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
                    @foreach($jours as $j)
                        <div class="text-center text-muted fw-semibold" style="font-size:11px;">{{ __($j) }}</div>
                    @endforeach

                    @for($i = 1; $i < $premierJour; $i++)
                        <div></div>
                    @endfor

                    @for($jour = 1; $jour <= $nbJours; $jour++)
                        @php
                            $dateStr    = $mois->copy()->day($jour)->format('Y-m-d');
                            $duJour     = $rappelsDuMois->get($dateStr, collect());
                            $estAujourdhui = $dateStr === now()->format('Y-m-d');
                        @endphp
                        <div class="border rounded p-1 {{ $estAujourdhui ? 'border-2' : '' }}"
                             style="min-height:74px;font-size:11px;{{ $estAujourdhui ? 'border-color:var(--gold) !important;background:#faf6ec;' : '' }}cursor:pointer;"
                             onclick="ouvrirModalRappel('{{ $dateStr }}')" title="{{ __('Ajouter un rappel le') }} {{ $jour }}">
                            <div class="fw-semibold {{ $estAujourdhui ? '' : 'text-muted' }}">{{ $jour }}</div>
                            @foreach($duJour as $rappel)
                                <div class="rounded px-1 mb-1 text-truncate rappel-chip"
                                     style="background:var(--brand);color:#fff;max-width:100%;"
                                     title="{{ $rappel->texte }}"
                                     onclick="event.stopPropagation(); supprimerRappel({{ $rappel->id }}, '{{ $rappel->date_rappel->format('d/m/Y') }}')">
                                    🔔 {{ Str::limit($rappel->texte, 18) ?: __('Rappel') }}
                                    @if($rappel->fichier)📎@endif
                                </div>
                            @endforeach
                        </div>
                    @endfor
                </div>
            </div>
        </div>
        <p class="mt-3 mb-0 p-3 rounded" style="font-size:15px;background:#f3ead8;border-left:4px solid var(--gold);">
            💡 {{ __('Cliquez sur un jour pour ajouter un rappel, ou sur un rappel existant pour le supprimer. Un email « Ne pas oublier » vous est envoyé le jour J.') }}
        </p>

        {{-- Formulaires de suppression cachés --}}
        @foreach($rappelsDuMois->flatten() as $rappel)
            <form id="delRappel{{ $rappel->id }}" action="{{ route('pensebete.rappels.destroy', $rappel) }}" method="POST" class="d-none">
                @csrf @method('DELETE')
            </form>
        @endforeach
    </div>

    {{-- ════════ ONGLET NOTES ════════ --}}
    <div id="ongletNotes" class="{{ $ongletActif === 'notes' ? '' : 'd-none' }}">

        <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
            {{-- Tri : alphabétique ⇅ ou par date de création (récent → ancien) --}}
            <a href="{{ route('pensebete.index', ['onglet' => 'notes', 'tri' => $tri === 'alpha' ? 'date' : 'alpha']) }}"
               class="btn btn-sm btn-outline-secondary" title="{{ __('Changer le tri') }}">
                ⇅ {{ $tri === 'alpha' ? __('Tri : A → Z') : __('Tri : plus récent → plus ancien') }}
            </a>
            <button type="button" class="btn btn-sm btn-primary" onclick="ouvrirModalNote()">➕ {{ __('Ajouter une Note') }}</button>
        </div>

        <div class="row g-3">
            {{-- Liste des notes (gauche, style boîte mail) --}}
            <div class="col-12 col-md-4">
                <div class="card shadow-sm" style="max-height:64vh;overflow-y:auto;">
                    <div class="list-group list-group-flush">
                        @forelse($notesParDossier as $dossier => $groupe)
                            @if($dossier !== '')
                                <div class="list-group-item py-1 text-muted fw-semibold" style="font-size:11px;background:#f4f2ec;">
                                    📁 {{ $dossier }}
                                </div>
                            @endif
                            @foreach($groupe as $note)
                                <button type="button" class="list-group-item list-group-item-action note-item" data-note="{{ $note->id }}"
                                        onclick="ouvrirNote({{ $note->id }}, this)">
                                    <div class="fw-semibold text-truncate" style="font-size:14px;">
                                        {{ $note->titre }}
                                        @if($note->notifier)<span title="{{ __('Rappel programmé le') }} {{ $note->date_notification?->format('d/m/Y') }}">🔔</span>@endif
                                    </div>
                                    <div class="text-muted" style="font-size:11px;">
                                        {{ $note->updated_at->format('d/m/Y H:i') }}
                                        @if($note->image) · 🖼 @endif
                                    </div>
                                </button>
                            @endforeach
                        @empty
                            <div class="list-group-item text-muted" style="font-size:14px;">
                                {{ __('Aucune note pour le moment.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Contenu de la note (droite sur PC) --}}
            <div class="col-md-8 d-none d-md-block">
                <div class="card shadow-sm" style="min-height:300px;">
                    <div class="card-body">
                        <div id="noteVide" class="text-muted text-center py-5" style="font-size:14px;">
                            ← {{ __('Sélectionnez une note pour l\'afficher.') }}
                        </div>
                        @foreach($notes as $note)
                            @php
                                $noteJson = json_encode([
                                    'id'                => $note->id,
                                    'titre'             => $note->titre,
                                    'dossier'           => $note->dossier,
                                    'contenu'           => $note->contenu,
                                    'notifier'          => (bool) $note->notifier,
                                    'date_notification' => $note->date_notification?->format('Y-m-d'),
                                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
                            @endphp
                            <div class="note-detail d-none" data-note="{{ $note->id }}">
                                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                    <div>
                                        <h2 class="h5 mb-0">{{ $note->titre }}</h2>
                                        <div class="text-muted" style="font-size:12px;">
                                            @if($note->dossier)📁 {{ $note->dossier }} · @endif
                                            {{ __('Modifiée le') }} {{ $note->updated_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick='ouvrirModalNote({!! $noteJson !!})'>✏️ {{ __('Modifier') }}</button>
                                        <form action="{{ route('pensebete.notes.destroy', $note) }}" method="POST"
                                              onsubmit="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer cette note ?') }}')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">🗑 {{ __('Supprimer') }}</button>
                                        </form>
                                    </div>
                                </div>
                                @if($note->image)
                                    <a href="{{ asset('storage/' . $note->image) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $note->image) }}" class="img-fluid mb-2" style="max-height:240px;border-radius:8px;">
                                    </a>
                                @endif
                                <div style="font-size:14px;white-space:pre-wrap;">{{ $note->contenu }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Panneau mobile (ouvre la note en bas, croix pour fermer) --}}
        <div id="notePanneauMobile" class="d-md-none d-none position-fixed bottom-0 start-0 end-0 bg-white border-top shadow-lg p-3"
             style="max-height:65vh;overflow-y:auto;z-index:1040;border-radius:14px 14px 0 0;">
            <button type="button" class="btn-close position-absolute" style="top:12px;right:14px;" onclick="fermerNoteMobile()"></button>
            <div id="notePanneauContenu"></div>
        </div>
    </div>
</div>

{{-- ── Modal Rappel ── --}}
<div class="modal fade" id="modalRappel" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('pensebete.rappels.store') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header py-2">
                <h5 class="modal-title" style="font-size:16px;">📅 {{ __('Ajouter un rappel') }}</h5>
                <button type="button" class="btn-close" onclick="annulerModal('modalRappel')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Jour du rappel') }} *</label>
                    <input type="date" name="date_rappel" id="rappelDate" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Texte du rappel') }}</label>
                    <textarea name="texte" class="form-control" rows="3" maxlength="3000"
                              placeholder="{{ __('ex : 14 juillet — préparer la cérémonie…') }}"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">📎 {{ __('Photo ou document à déposer') }}</label>
                    <input type="file" name="fichier" class="form-control" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="annulerModal('modalRappel')">{{ __('Annuler') }}</button>
                <button type="submit" class="btn btn-success btn-sm">{{ __('Enregistrer') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Note (création + modification) ── --}}
<div class="modal fade" id="modalNote" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('pensebete.notes.store') }}" enctype="multipart/form-data" class="modal-content" id="formNote">
            @csrf
            <input type="hidden" name="_method" id="noteMethode" value="POST">
            <div class="modal-header py-2">
                <h5 class="modal-title" style="font-size:16px;" id="modalNoteTitre">🗒️ {{ __('Ajouter une Note') }}</h5>
                <button type="button" class="btn-close" onclick="annulerModal('modalNote')"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Titre') }} *</label>
                    <input type="text" name="titre" id="noteTitre" class="form-control" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">📁 {{ __('Dossier') }} <span class="text-muted">({{ __('facultatif') }})</span></label>
                    <input type="text" name="dossier" id="noteDossier" class="form-control" list="dossiersExistants" maxlength="100"
                           placeholder="{{ __('ex : Réunions, Idées…') }}">
                    <datalist id="dossiersExistants">
                        @foreach($dossiers as $d)<option value="{{ $d }}">@endforeach
                    </datalist>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notifier" id="noteNotifier" value="1" onchange="majNoteNotif()">
                        <label class="form-check-label" for="noteNotifier" style="font-size:13px;">
                            🔔 {{ __('Être notifié par e-mail de cette note') }}
                        </label>
                    </div>
                    <div id="noteDateWrap" class="mt-2 d-none">
                        <label class="form-label mb-1" style="font-size:12px;">{{ __('Date du rappel') }} *</label>
                        <input type="date" name="date_notification" id="noteDateNotif" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">{{ __('Texte') }}</label>
                    <textarea name="contenu" id="noteContenu" class="form-control" rows="5" maxlength="10000"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">🖼 {{ __('Photo') }}</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="annulerModal('modalNote')">{{ __('Annuler') }}</button>
                <button type="submit" class="btn btn-success btn-sm">{{ __('Enregistrer') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Onglets ──────────────────────────────────────────────────────
function montrerOnglet(onglet, btn) {
    document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('ongletCalendrier').classList.toggle('d-none', onglet !== 'calendrier');
    document.getElementById('ongletNotes').classList.toggle('d-none', onglet !== 'notes');
}

// ── Modales (annulation avec confirmation) ───────────────────────
function annulerModal(id) {
    if (confirm('{{ __('Annuler ? Les informations saisies seront perdues.') }}')) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).hide();
    }
}

// ── Calendrier ───────────────────────────────────────────────────
function ouvrirModalRappel(date) {
    document.getElementById('rappelDate').value = date || '';
    new bootstrap.Modal(document.getElementById('modalRappel')).show();
}

function supprimerRappel(id, date) {
    if (confirm('{{ __('Êtes-vous sûr de vouloir supprimer le rappel du') }} ' + date + ' ?')) {
        document.getElementById('delRappel' + id).submit();
    }
}

// ── Notes ────────────────────────────────────────────────────────
const estMobile = () => window.matchMedia('(max-width: 767px)').matches;

function ouvrirNote(id, btn) {
    document.querySelectorAll('.note-item').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const detail = document.querySelector('.note-detail[data-note="' + id + '"]');

    if (estMobile()) {
        // Sur téléphone : la note s'ouvre en bas, croix pour fermer
        document.getElementById('notePanneauContenu').innerHTML = detail.innerHTML;
        document.getElementById('notePanneauMobile').classList.remove('d-none');
    } else {
        document.getElementById('noteVide').classList.add('d-none');
        document.querySelectorAll('.note-detail').forEach(d => d.classList.add('d-none'));
        detail.classList.remove('d-none');
    }
}

function fermerNoteMobile() {
    document.getElementById('notePanneauMobile').classList.add('d-none');
}

function majNoteNotif() {
    const coche = document.getElementById('noteNotifier').checked;
    document.getElementById('noteDateWrap').classList.toggle('d-none', !coche);
    document.getElementById('noteDateNotif').required = coche;
}

function ouvrirModalNote(note) {
    const form = document.getElementById('formNote');
    if (note && note.id) {
        form.action = '{{ url('/pense-bete/notes') }}/' + note.id;
        document.getElementById('noteMethode').value = 'PUT';
        document.getElementById('modalNoteTitre').textContent = '✏️ {{ __('Modifier la note') }}';
        document.getElementById('noteTitre').value   = note.titre || '';
        document.getElementById('noteDossier').value = note.dossier || '';
        document.getElementById('noteContenu').value = note.contenu || '';
        document.getElementById('noteNotifier').checked = !!note.notifier;
        document.getElementById('noteDateNotif').value = note.date_notification || '';
    } else {
        form.action = '{{ route('pensebete.notes.store') }}';
        document.getElementById('noteMethode').value = 'POST';
        document.getElementById('modalNoteTitre').textContent = '🗒️ {{ __('Ajouter une Note') }}';
        document.getElementById('noteTitre').value = '';
        document.getElementById('noteDossier').value = '';
        document.getElementById('noteContenu').value = '';
        document.getElementById('noteNotifier').checked = false;
        document.getElementById('noteDateNotif').value = '';
    }
    majNoteNotif();
    fermerNoteMobile();
    new bootstrap.Modal(document.getElementById('modalNote')).show();
}
</script>
@endsection
