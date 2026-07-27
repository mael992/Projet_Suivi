@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:720px;">

    <a href="{{ route('contact.mairie') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">← {{ __('Contacter votre Mairie') }}</a>
    <h1 class="h4 mb-1">🎫 {{ __('Ticket') }} {{ $ticket->reference }} — {{ $ticket->sujet }}</h1>
    <p class="text-muted mb-3" style="font-size:13px;">
        {{ $ticket->mairie?->nom }} · {{ $ticket->service_label }}
    </p>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body" style="background:#f4f6f9;">
            @foreach($ticket->messages as $message)
                @php $ext = $message->estExterieur(); @endphp
                <div class="d-flex gap-2 mb-2 {{ $ext ? 'flex-row-reverse' : '' }}">
                    <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:34px;height:34px;border-radius:50%;font-size:12px;font-weight:700;color:#fff;background:{{ $ext ? '#6c757d' : 'var(--brand)' }};">
                        {{ $message->initiales ?: '?' }}
                    </div>
                    <div class="rounded p-2" style="max-width:75%;background:{{ $ext ? '#dbe7f5' : '#fff' }};border:1px solid #e2e8f0;">
                        <div class="text-muted" style="font-size:11px;">
                            @if($ext)
                                {{ __('Vous') }}
                            @else
                                {{ $message->auteur?->full_name ?? __('Mairie') }}
                                @if($message->auteur) — {{ $ticket->mairie?->nom }} @endif
                            @endif
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
        <div class="card-footer">
            @if($ticket->peutEcrire())
                <form method="POST" action="{{ route('contact.ticket.repondre', $ticket) }}" enctype="multipart/form-data">
                    @csrf
                    <textarea name="corps" class="form-control mb-2" rows="4" required minlength="2" maxlength="5000"
                              placeholder="{{ __('Écrire un message à la mairie…') }}"></textarea>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <input type="file" name="fichiers[]" class="form-control form-control-sm" style="max-width:340px;"
                               accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" multiple
                               onchange="if(this.files.length>3){alert('{{ __('3 fichiers maximum.') }}');this.value='';}">
                        <small class="text-muted">{{ __('Photos ou documents (3 maximum)') }}</small>
                        <button type="submit" class="btn btn-primary ms-auto">{{ __('Envoyer') }}</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('contact.ticket.cloturer', $ticket) }}" class="mt-2"
                      onsubmit="return confirm('{{ __('Clôturer votre demande ? Vous pourrez encore demander sa réouverture pendant 15 jours.') }}')">
                    @csrf
                    <button class="btn btn-outline-dark btn-sm">🔒 {{ __('Je n\'ai plus besoin d\'aide — clôturer') }}</button>
                </form>

            @elseif($ticket->statut === \App\Models\Ticket::STATUT_REOUVERTURE)
                <div class="alert alert-warning py-2 mb-0" style="font-size:13px;">
                    🔓 {{ __('Votre demande de réouverture a été transmise. La mairie va l\'étudier.') }}
                </div>

            @elseif($ticket->reouverturePossible())
                <form method="POST" action="{{ route('contact.ticket.reouverture', $ticket) }}">
                    @csrf
                    <p class="text-muted mb-2" style="font-size:13px;">
                        🔒 {{ __('Cette conversation est clôturée.') }}
                        {{ __('Vous pouvez demander sa réouverture pendant encore') }}
                        <strong>{{ $ticket->joursRestantsReouverture() }} {{ __('jour(s)') }}</strong>.
                    </p>
                    <textarea name="motif" class="form-control mb-2" rows="3" required minlength="2" maxlength="1000"
                              placeholder="{{ __('Expliquez pourquoi vous souhaitez rouvrir cette demande…') }}"></textarea>
                    <button class="btn btn-primary btn-sm">🔓 {{ __('Demander la réouverture') }}</button>
                </form>

            @else
                <div class="alert alert-secondary py-2 mb-0" style="font-size:13px;">
                    🔒 {{ __('Cette conversation est clôturée et ne peut plus être rouverte. Elle reste consultable pendant 6 mois.') }}
                    {{ __('Pour une nouvelle demande, utilisez l\'onglet « J\'ai besoin d\'aide ».') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
