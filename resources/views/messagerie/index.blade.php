@extends('layouts.app')

@php use App\Models\Ticket; @endphp

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>
    <h1 class="h3 mb-1">📬 {{ __('Centre de Messagerie') }}</h1>
    @if($admin)
        <p class="text-muted mb-3" style="font-size:14px;">{{ __('Gestionnaire des messages de toutes les mairies (lecture seule).') }}</p>
    @endif

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

    {{-- ── Onglets principaux ── --}}
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-2">
        <ul class="nav nav-tabs" style="flex:1;">
            <li class="nav-item"><button class="nav-link" onclick="ongletMsg('interne', this)">📨 {{ __('Message Interne') }}</button></li>
            <li class="nav-item"><button class="nav-link active" onclick="ongletMsg('externe', this)">🌐 {{ __('Message Externe') }}</button></li>
            <li class="nav-item"><button class="nav-link" onclick="ongletMsg('support', this)">🛟 {{ __('Message Support') }}</button></li>
        </ul>
        @if($admin)
            @include('partials.tri-mairie', ['route' => 'messagerie.index'])
        @endif
    </div>

    {{-- ── Message Interne (à venir) ── --}}
    <div id="msgInterne" class="d-none">
        <div class="card shadow-sm"><div class="card-body text-center text-muted py-5">
            📨 {{ __('Les messages internes seront disponibles prochainement.') }}
        </div></div>
    </div>

    {{-- ── Message Externe ── --}}
    <div id="msgExterne">
        <div class="row g-3">

            {{-- Dossiers --}}
            <div class="col-12 col-md-3">
                <div class="card shadow-sm">
                    <div class="card-header py-2 text-white fw-semibold" style="background:var(--brand-dark);font-size:14px;">
                        {{ __('Dossiers') }}
                    </div>
                    <div class="list-group list-group-flush">
                        @php
                            // « Transféré » vient après les quatre dossiers : ce n'est pas
                            // un statut mais un tri, d'où sa pastille orange clair.
                            $dossiers = Ticket::STATUTS + [Ticket::DOSSIER_TRANSFERE => 'Transféré'];
                            $icones   = [
                                'reception' => '📥', 'reponse' => '↩️', 'cloture' => '🔒',
                                'reouverture_demandee' => '🔓', Ticket::DOSSIER_TRANSFERE => '🔁',
                            ];
                        @endphp
                        @foreach($dossiers as $cle => $label)
                            @php
                                $notif     = in_array($cle, [Ticket::STATUT_RECEPTION, Ticket::STATUT_REOUVERTURE], true);
                                $transfert = $cle === Ticket::DOSSIER_TRANSFERE;
                            @endphp
                            <a href="{{ route('messagerie.index', array_merge(request()->only('mairie', 'q', 'tri'), ['dossier' => $cle])) }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $dossier === $cle ? 'active' : '' }}"
                               style="font-size:14px;">
                                <span>{{ $icones[$cle] }} {{ __($label) }}</span>
                                @if(($compteurs[$cle] ?? 0) > 0)
                                    @if($transfert)
                                        <span class="bulle-notif bulle-notif-transfert">{{ $compteurs[$cle] }}</span>
                                    @elseif($notif)
                                        <span class="bulle-notif">{{ $compteurs[$cle] }}</span>
                                    @else
                                        <span class="compte-total">{{ $compteurs[$cle] }}</span>
                                    @endif
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Liste --}}
            <div class="col-12 col-md-9">
                {{-- Recherche + tri --}}
                <form method="GET" action="{{ route('messagerie.index') }}" class="card shadow-sm mb-2">
                    <input type="hidden" name="dossier" value="{{ $dossier }}">
                    @if(request('mairie'))<input type="hidden" name="mairie" value="{{ request('mairie') }}">@endif
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-7">
                                <div class="search-input-group">
                                    <span class="search-icon">🔍</span>
                                    <input type="text" name="q" value="{{ request('q') }}" class="search-input"
                                           placeholder="{{ __('Rechercher un message ou un sujet, e-mail, nom, prénom…') }}" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-8 col-md-3">
                                <select name="tri" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="recent" @selected($tri === 'recent')>{{ __('Plus récent d\'abord') }}</option>
                                    <option value="ancien" @selected($tri === 'ancien')>{{ __('Plus ancien d\'abord') }}</option>
                                </select>
                            </div>
                            <div class="col-4 col-md-2 d-flex gap-1">
                                <button class="btn btn-sm btn-dark w-100">{{ __('Rechercher') }}</button>
                                @if(request('q'))
                                    <a href="{{ route('messagerie.index', ['dossier' => $dossier]) }}" class="btn btn-sm btn-outline-secondary">✕</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

                <div class="card shadow-sm" id="zoneMessagerie">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>{{ __('Ticket') }}</th>
                                    @if($admin)<th>{{ __('Mairie') }}</th>@endif
                                    <th>{{ __('Nom & Prénom') }}</th>
                                    <th>{{ __('Sujet') }}</th>
                                    <th>{{ __('Service') }}</th>
                                    <th>{{ __('Reçu le') }}</th>
                                    <th class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($tickets as $ticket)
                                @php
                                    // Ancienneté du dernier échange : plus c'est vieux, plus la ligne est grisée
                                    $jours = (int) $ticket->updated_at->diffInDays(now());
                                    $fond  = $jours >= 14 ? '#e9e7e2' : ($jours >= 7 ? '#f2f0eb' : ($jours >= 3 ? '#faf9f6' : ''));
                                @endphp
                                <tr @if($fond) style="background:{{ $fond }};" @endif>
                                    <td class="fw-semibold">{{ $ticket->reference }}</td>
                                    @if($admin)<td style="font-size:13px;">{{ $ticket->mairie?->nom }}</td>@endif
                                    <td>{{ $ticket->nom_complet }}</td>
                                    <td style="font-size:13px;">
                                        {{ $ticket->sujet }}
                                        @if($ticket->estTransfere())
                                            <span class="badge-transfert ms-1">🔁 {{ __('Transféré') }}</span>
                                        @endif
                                    </td>
                                    <td style="font-size:13px;">
                                        @if($ticket->estTransfere())
                                            {{ $ticket->libelleTransfert() }}
                                            <div class="text-muted" style="font-size:11px;">
                                                @if($ticket->facteur){{ __('par') }} {{ $ticket->facteur->username }} · @endif
                                                {{ $ticket->transfere_at->format('d/m/Y') }}
                                            </div>
                                        @else
                                            {{ $ticket->service_label }}
                                        @endif
                                    </td>
                                    <td style="font-size:13px;">
                                        {{ $ticket->created_at->format('d/m/Y') }}
                                        <div class="text-muted" style="font-size:11px;">
                                            {{ __('Dernier échange') }} : {{ $ticket->updated_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ticket{{ $ticket->id }}">
                                            {{ __('Ouvrir') }}
                                        </button>
                                        @if($peutRepondre)
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="modal" data-bs-target="#transfert{{ $ticket->id }}">
                                                {{ $ticket->estTransfere() ? '🔁 ' . __('Retransférer') : '🔁 ' . __('Transférer') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $admin ? 7 : 6 }}" class="text-center text-muted py-4">{{ __('Aucun message dans ce dossier.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Message Support (à venir) ── --}}
    <div id="msgSupport" class="d-none">
        <div class="card shadow-sm"><div class="card-body text-center text-muted py-5">
            🛟 {{ __('Le support sera paramétré prochainement.') }}
        </div></div>
    </div>
</div>

{{-- ── Modales de transfert (« facteur ») ── --}}
@if($peutRepondre)
    @foreach($tickets as $ticket)
    <div class="modal fade" id="transfert{{ $ticket->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('messagerie.transferer', $ticket) }}" class="modal-content">
                @csrf
                <div class="modal-header py-2">
                    <h5 class="modal-title" style="font-size:16px;">🔁 {{ __('Transférer la demande') }} {{ $ticket->reference }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $svcCoches  = $ticket->servicesTransfert();
                        $userCoches = $ticket->idsTransfert();
                    @endphp

                    @if($ticket->estTransfere())
                        <div class="alert alert-info py-2" style="font-size:13px;">
                            {{ __('Déjà transférée à') }}
                            <strong>{{ $ticket->libelleTransfert() }}</strong>
                            {{ __('le') }} {{ $ticket->transfere_at->format('d/m/Y H:i') }}
                            @if($ticket->facteur) {{ __('par') }} {{ $ticket->facteur->username }} @endif
                        </div>
                    @endif

                    <p class="text-muted mb-2" style="font-size:12px;">
                        {{ __('Cochez autant de services et de personnes que nécessaire : la demande arrive chez chacun d\'eux.') }}
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-1" style="font-size:13px;">🏢 {{ __('Vers un ou plusieurs services') }}</label>
                        <div class="border rounded p-2 row g-1" style="max-height:170px;overflow-y:auto;">
                            @foreach($ticket->mairie->libellesServices() as $num => $label)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="services[]" value="{{ $num }}"
                                               id="svc{{ $ticket->id }}_{{ $num }}" @checked(in_array($num, $svcCoches, true))>
                                        <label class="form-check-label" for="svc{{ $ticket->id }}_{{ $num }}" style="font-size:12px;">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-semibold mb-1" style="font-size:13px;">👤 {{ __('Vers une ou plusieurs personnes') }}</label>
                        <div class="border rounded p-2 row g-1" style="max-height:170px;overflow-y:auto;">
                            @forelse($agentsMairie[$ticket->mairie_id] ?? [] as $agent)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="users[]" value="{{ $agent->id }}"
                                               id="usr{{ $ticket->id }}_{{ $agent->id }}" @checked(in_array($agent->id, $userCoches, true))>
                                        <label class="form-check-label" for="usr{{ $ticket->id }}_{{ $agent->id }}" style="font-size:12px;">
                                            {{ $agent->username }} — {{ $agent->service_label }}
                                        </label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted" style="font-size:12px;">{{ __('Aucun collègue enregistré.') }}</div>
                            @endforelse
                        </div>
                    </div>

                    <p class="text-muted mt-2 mb-0" style="font-size:12px;">
                        {{ __('Vous gardez la visibilité sur cette demande après le transfert : si elle revient (réouverture), elle repasse par vous.') }}
                    </p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Transférer') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
@endif

{{-- ── Modales des tickets (fil de discussion) ── --}}
@foreach($tickets as $ticket)
<div class="modal fade" id="ticket{{ $ticket->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title mb-0" style="font-size:16px;">
                        {{ $ticket->sujet }}
                        <span class="badge bg-secondary ms-1" style="font-size:10px;">{{ __($ticket->statut_label) }}</span>
                    </h5>
                    <div class="text-muted" style="font-size:12px;">
                        {{ __('De') }} : <strong>{{ $ticket->nom_complet }}</strong> · {{ $ticket->telephone_complet }} · {{ $ticket->email }}
                        · {{ __('Ticket') }} {{ $ticket->reference }} · {{ $ticket->service_label }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#f4f6f9;">
                @if($ticket->estTransfere())
                    <div class="alerte-transfert py-2 px-3 mb-3 rounded" style="font-size:13px;">
                        🔁 <strong>{{ __('Demande transférée') }}</strong>
                        @if($ticket->facteur) {{ __('par') }} {{ $ticket->facteur->username }} @endif
                        {{ __('le') }} {{ $ticket->transfere_at->format('d/m/Y H:i') }}
                        {{ __('à') }} <strong>{{ $ticket->libelleTransfert() }}</strong>.
                    </div>
                @endif

                @if($ticket->statut === Ticket::STATUT_REOUVERTURE)
                    <div class="alert alert-warning py-2" style="font-size:13px;">
                        🔓 <strong>{{ __('Demande de réouverture') }}</strong>
                        ({{ $ticket->reouverture_demandee_at?->format('d/m/Y H:i') }}) :
                        <div style="white-space:pre-wrap;">{{ $ticket->reouverture_motif }}</div>
                    </div>
                @elseif($ticket->estCloture())
                    <div class="alert alert-secondary py-2" style="font-size:13px;">
                        🔒 {{ __('Conversation clôturée le') }} {{ $ticket->cloture_at?->format('d/m/Y') }}
                        ({{ $ticket->cloture_par === 'citoyen' ? __('par l\'habitant') : __('par la mairie') }}) —
                        {{ __('lecture seule, conservée 6 mois.') }}
                    </div>
                @endif

                @if($ticket->photos)
                    <div class="d-flex gap-2 mb-3 flex-wrap">
                        @foreach($ticket->photos as $photo)
                            <a href="{{ asset('storage/' . $photo) }}" target="_blank">
                                <img src="{{ asset('storage/' . $photo) }}" style="height:70px;border-radius:6px;">
                            </a>
                        @endforeach
                    </div>
                @endif

                @foreach($ticket->messages as $message)
                    @php $ext = $message->estExterieur(); @endphp
                    <div class="d-flex gap-2 mb-2 {{ $ext ? '' : 'flex-row-reverse' }}">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:34px;height:34px;border-radius:50%;font-size:12px;font-weight:700;color:#fff;background:{{ $ext ? '#6c757d' : 'var(--brand)' }};">
                            {{ $message->initiales ?: '?' }}
                        </div>
                        <div class="rounded p-2" style="max-width:75%;background:{{ $ext ? '#fff' : '#dbe7f5' }};border:1px solid #e2e8f0;">
                            <div class="text-muted" style="font-size:11px;">
                                {{ $ext ? $ticket->nom_complet . ' (' . __('extérieur') . ')' : ($message->auteur?->full_name ?? '—') }}
                                — {{ $message->created_at->format('d/m/Y H:i') }}
                            </div>
                            <div style="font-size:14px;white-space:pre-wrap;">{{ $message->corps }}</div>
                            @if($message->fichiers)
                                <div class="d-flex gap-2 flex-wrap mt-2">
                                    @foreach($message->fichiers as $fichier)
                                        <a href="{{ asset('storage/' . $fichier) }}" target="_blank" class="badge bg-secondary text-decoration-none">
                                            📎 {{ \Illuminate\Support\Str::limit(basename($fichier), 24) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($peutRepondre)
                <div class="modal-footer py-2 d-block">
                    @if($ticket->statut === Ticket::STATUT_REOUVERTURE)
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('messagerie.reouverture.accepter', $ticket) }}">
                                @csrf
                                <button class="btn btn-success btn-sm">✅ {{ __('Accepter la réouverture') }}</button>
                            </form>
                            <form method="POST" action="{{ route('messagerie.reouverture.refuser', $ticket) }}">
                                @csrf
                                <button class="btn btn-outline-danger btn-sm">✖ {{ __('Refuser') }}</button>
                            </form>
                        </div>
                    @elseif($ticket->peutEcrire())
                        <form method="POST" action="{{ route('messagerie.repondre', $ticket) }}" enctype="multipart/form-data">
                            @csrf
                            <textarea name="corps" class="form-control mb-2" rows="4" required maxlength="5000"
                                      placeholder="{{ __('Écrire un message à la personne…') }}"></textarea>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <input type="file" name="fichiers[]" class="form-control form-control-sm" style="max-width:300px;"
                                       accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" multiple
                                       onchange="if(this.files.length>3){alert('{{ __('3 fichiers maximum.') }}');this.value='';}">
                                <small class="text-muted">{{ __('Photos ou documents (3 maximum)') }}</small>
                                <button type="submit" class="btn btn-primary ms-auto">{{ __('Envoyer') }}</button>
                            </div>
                        </form>
                        {{-- @js et non {{ }} : l'apostrophe de « L'habitant » était réécrite en
                             &#039;, que le navigateur redécodait en ' au milieu du texte JavaScript.
                             La confirmation ne s'affichait pas et la conversation se clôturait
                             directement, sans demander l'avis de l'agent. --}}
                        <form method="POST" action="{{ route('messagerie.cloturer', $ticket) }}" class="mt-2"
                              onsubmit="return confirm(@js(__('Clôturer cette conversation ? L\'habitant pourra demander sa réouverture pendant 15 jours.')))">
                            @csrf
                            <button class="btn btn-outline-dark btn-sm">🔒 {{ __('Clôturer la conversation') }}</button>
                        </form>
                    @else
                        <div class="text-muted" style="font-size:12px;">🔒 {{ __('Conversation clôturée — lecture seule.') }}</div>
                    @endif
                </div>
            @else
                <div class="modal-footer py-2 text-muted" style="font-size:12px;">
                    👁 {{ __('Lecture seule — vous ne pouvez pas répondre à ce message.') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endforeach

<script>
function ongletMsg(onglet, btn) {
    document.querySelectorAll('.nav-tabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('msgInterne').classList.toggle('d-none', onglet !== 'interne');
    document.getElementById('msgExterne').classList.toggle('d-none', onglet !== 'externe');
    document.getElementById('msgSupport').classList.toggle('d-none', onglet !== 'support');
}
</script>
@include('partials.autorefresh', ['selector' => '#zoneMessagerie'])
@endsection
