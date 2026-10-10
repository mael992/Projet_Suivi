{{-- Onglet « Message Support » du Centre de messagerie : demandes au support
     technique MGDS. L'agent n'y voit que les siennes ; l'équipe MGDS (admins)
     les voit toutes, classées par dossier, et répond signée « Admin ». --}}
@php use App\Models\SupportDemande; @endphp

<div class="row g-3">

    {{-- Liste des demandes --}}
    <div class="col-12 col-md-4">
        @if($admin)
            <div class="card shadow-sm mb-2">
                <div class="card-header py-2 text-white fw-semibold" style="background:var(--brand-dark);font-size:14px;">
                    {{ __('Dossiers') }}
                </div>
                <div class="list-group list-group-flush">
                    @foreach(SupportDemande::STATUTS as $statut => $libelle)
                        <a href="{{ route('messagerie.index', ['onglet' => 'support', 'support' => $statut]) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $supportDossier === $statut ? 'active' : '' }}"
                           style="font-size:14px;">
                            {{ __($libelle) }}
                            <span class="badge {{ $statut === SupportDemande::STATUT_RECEPTION && $supportCompteurs[$statut] ? 'bg-danger' : 'bg-secondary' }}">{{ $supportCompteurs[$statut] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header py-2 fw-semibold d-flex justify-content-between align-items-center" style="font-size:14px;">
                {{ $admin ? __('Demandes') : __('Mes demandes au support') }}
                @unless($admin)
                    <a href="{{ route('contact') }}" class="btn btn-sm btn-outline-primary py-0">＋ {{ __('Nouvelle demande') }}</a>
                @endunless
            </div>
            <div class="list-group list-group-flush">
                @forelse($supportDemandes as $d)
                    <a href="{{ route('messagerie.index', ['onglet' => 'support', 'support' => $admin ? $supportDossier : null, 'demande' => $d->id]) }}"
                       class="list-group-item list-group-item-action {{ $supportOuverte?->id === $d->id ? 'active' : '' }}"
                       style="font-size:13px;">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="fw-semibold">{{ $d->reference }} — {{ $d->concerne_label }}</span>
                            <span class="badge {{ $d->statut === SupportDemande::STATUT_REPONSE ? 'bg-success' : ($d->estCloture() ? 'bg-secondary' : 'bg-warning text-dark') }}">
                                {{ $d->statut_label }}
                            </span>
                        </div>
                        <small class="{{ $supportOuverte?->id === $d->id ? '' : 'text-muted' }}">
                            @if($admin){{ $d->libelleDemandeur() }} · @endif{{ $d->updated_at->format('d/m/Y H:i') }}
                        </small>
                    </a>
                @empty
                    <div class="list-group-item text-center text-muted py-4" style="font-size:13px;">
                        {{ $admin ? __('Aucune demande dans ce dossier.') : __('Vous n\'avez encore envoyé aucune demande au support.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Conversation ouverte --}}
    <div class="col-12 col-md-8">
        @if($supportOuverte)
            @php $demande = $supportOuverte; @endphp
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    🛟 {{ $demande->reference }} — {{ $demande->concerne_label }}
                    <span class="text-muted fw-normal" style="font-size:13px;">· {{ $demande->statut_label }} · {{ __('Ouverte le') }} {{ $demande->created_at->format('d/m/Y H:i') }}</span>
                </div>

                @if($admin)
                    {{-- Qui écrit et réponses du questionnaire --}}
                    <div class="card-body border-bottom" style="font-size:14px;">
                        @if($demande->avec_compte)
                            <span class="badge bg-success">{{ __('Compte MGDS') }}</span>
                            {{ $demande->libelleDemandeur() }}
                        @else
                            <span class="badge bg-warning text-dark">{{ __('Sans compte MGDS') }}</span>
                            {{ trim($demande->prenom . ' ' . $demande->nom) }} ·
                            <a href="mailto:{{ $demande->email }}">{{ $demande->email }}</a>
                        @endif
                        @if($demande->precision)
                            <div class="mt-1"><span class="text-muted">{{ __('Précision') }} :</span> <span style="white-space:pre-wrap;">{{ $demande->precision }}</span></div>
                        @endif
                    </div>
                @endif

                @include('support._conversation', ['vueAdmin' => $admin])

                <div class="card-footer">
                    @if($demande->estCloture())
                        <div class="text-muted" style="font-size:13px;">
                            🔒 {{ $admin ? __('Cette demande est clôturée.') : __('Cette demande est clôturée. Pour un nouveau problème, ouvrez une nouvelle demande depuis la page Contact.') }}
                        </div>
                    @elseif($admin)
                        <form method="POST" action="{{ route('admin.messages.repondre', $demande) }}">
                            @csrf
                            <textarea name="corps" class="form-control mb-2" rows="4" required minlength="2" maxlength="5000"
                                      placeholder="{{ __('Votre réponse, signée « Admin »…') }}"></textarea>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">{{ __('La réponse est signée « Admin » : la personne ne sait pas quel admin lui répond.') }}</small>
                                <button type="submit" class="btn btn-primary">{{ __('Envoyer') }}</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.messages.cloturer', $demande) }}" class="mt-2"
                              onsubmit="return confirm('{{ __('Clôturer cette demande ?') }}')">
                            @csrf
                            <button class="btn btn-outline-dark btn-sm">🔒 {{ __('Clôturer') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('support.repondre', $demande->jeton) }}">
                            @csrf
                            <textarea name="corps" class="form-control mb-2" rows="4" required minlength="2" maxlength="5000"
                                      placeholder="{{ __('Écrire un message au support…') }}"></textarea>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">{{ __('Envoyer') }}</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('support.cloturer', $demande->jeton) }}" class="mt-2"
                              onsubmit="return confirm('{{ __('Clôturer votre demande au support ?') }}')">
                            @csrf
                            <button class="btn btn-outline-dark btn-sm">🔒 {{ __('Je n\'ai plus besoin d\'aide — clôturer') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @else
            <div class="card shadow-sm"><div class="card-body text-center text-muted py-5">
                🛟 {{ __('Choisissez une demande pour afficher la conversation.') }}
            </div></div>
        @endif
    </div>
</div>
