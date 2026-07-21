@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('apps') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('mgds.nav_apps') }}</a>
    <h1 class="h3 mb-1">💌 {{ __('Centre de Messagerie') }}</h1>
    @if($admin)
        <p class="text-muted mb-3" style="font-size:14px;">{{ __('Gestionnaire des messages de toutes les mairies (lecture seule).') }}</p>
    @endif

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
    @endif

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
                        <tr>
                            <td class="fw-semibold">{{ $ticket->reference }}</td>
                            @if($admin)<td style="font-size:13px;">{{ $ticket->mairie?->nom }}</td>@endif
                            <td>{{ $ticket->nom_complet }}</td>
                            <td style="font-size:13px;">{{ $ticket->sujet }}</td>
                            <td style="font-size:13px;">{{ $ticket->service_label }}</td>
                            <td style="font-size:13px;">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#ticket{{ $ticket->id }}">
                                    {{ __('Ouvrir') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="{{ __('Bientôt disponible') }}">
                                    {{ __('Transféré') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $admin ? 7 : 6 }}" class="text-center text-muted py-4">{{ __('Aucun message pour le moment.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
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

{{-- ── Modales des tickets (fil de discussion) ── --}}
@foreach($tickets as $ticket)
<div class="modal fade" id="ticket{{ $ticket->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title mb-0" style="font-size:16px;">{{ $ticket->sujet }}</h5>
                    <div class="text-muted" style="font-size:12px;">
                        {{ __('De') }} : <strong>{{ $ticket->nom_complet }}</strong> · {{ $ticket->telephone_complet }} · {{ $ticket->email }}
                        · {{ __('Ticket') }} {{ $ticket->reference }} · {{ $ticket->service_label }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#f4f6f9;">
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
                                {{ $ext ? $ticket->nom_complet . ' (' . __('extérieur') . ')' : ($message->auteur?->username ?? '—') }}
                                — {{ $message->created_at->format('d/m/Y H:i') }}
                            </div>
                            <div style="font-size:14px;white-space:pre-wrap;">{{ $message->corps }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($peutRepondre)
                <div class="modal-footer py-2">
                    <form method="POST" action="{{ route('messagerie.repondre', $ticket) }}" class="w-100 d-flex gap-2">
                        @csrf
                        <input type="text" name="corps" class="form-control form-control-sm"
                               placeholder="{{ __('Écrire un message à la personne…') }}" required maxlength="5000">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Envoyer') }}</button>
                    </form>
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
