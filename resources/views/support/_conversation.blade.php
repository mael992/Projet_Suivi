{{-- Fil d'une demande au support. $vueAdmin : affichage côté équipe MGDS.
     Les réponses de l'équipe sont toujours signées « Admin ». --}}
<div class="card-body" id="zoneConversation" style="background:#f4f6f9;">
    @foreach($demande->messages as $message)
        @php
            // Le côté droit est celui de la personne qui lit
            $aDroite = $vueAdmin ? $message->auteur === 'admin' : $message->estDuDemandeur();
            $avatar  = match ($message->auteur) { 'admin' => 'MGDS', 'assistant' => '🤖', default => '👤' };
            $couleur = match ($message->auteur) { 'admin' => 'var(--brand)', 'assistant' => '#b08d4a', default => '#6c757d' };
        @endphp
        <div class="d-flex gap-2 mb-2 {{ $aDroite ? 'flex-row-reverse' : '' }}">
            <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:34px;height:34px;border-radius:50%;font-size:10px;font-weight:700;color:#fff;background:{{ $couleur }};">
                {{ $avatar }}
            </div>
            <div class="rounded p-2" style="max-width:75%;background:{{ $aDroite ? '#dbe7f5' : '#fff' }};border:1px solid #e2e8f0;">
                <div class="text-muted" style="font-size:11px;">
                    {{ $message->signature($vueAdmin) }} — {{ $message->created_at->format('d/m/Y H:i') }}
                </div>
                <div style="font-size:14px;white-space:pre-wrap;">{{ $message->corps }}</div>
            </div>
        </div>
    @endforeach
</div>
