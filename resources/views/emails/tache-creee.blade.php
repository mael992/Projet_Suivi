<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #111; background:#f4f6f9; margin:0; padding:20px; }
.card { background:white; max-width:520px; margin:0 auto; border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1); }
.header { background:#1f2937; border-bottom:3px solid #2563eb; padding:20px 24px; color:#fff; font-size:18px; font-weight:bold; }
.body { padding:28px 24px; }
.info { background:#f8fafc; border-left:4px solid #2563eb; padding:14px 18px; border-radius:4px; margin:16px 0; font-size:14px; }
.info div { margin:6px 0; }
.footer { border-top:1px solid #eee; padding:14px 24px; font-size:12px; color:#888; }
</style></head>
<body>
<div class="card">
    <div class="header">MGDS — {{ $tache->mairie?->nom }}</div>
    <div class="body">
        <h2 style="margin:0 0 12px;font-size:17px;">
            Bonjour{{ $destinataire ? ' ' . $destinataire->prenom : '' }},
        </h2>

        @if($pourResponsable && ! $tache->assigne)
            <p>⚠️ Attention : vous devez effectuer une <strong>nouvelle tâche de travail</strong> —
            il faut <strong>affecter une personne</strong> de votre service.</p>
        @elseif($pourResponsable && $tache->assigne)
            <p>⚠️ Attention : une <strong>nouvelle tâche de travail</strong> attend
            <strong>{{ $tache->assigne->username }}</strong>. Merci de lui demander de la réaliser.</p>
        @else
            <p>⚠️ Attention : une <strong>nouvelle tâche de travail</strong> vous a été affectée.</p>
        @endif

        <div class="info">
            <div>🔖 <strong>Référence :</strong> {{ $tache->reference }}</div>
            <div>🏛️ <strong>Mairie :</strong> {{ $tache->mairie?->nom }}</div>
            <div>👥 <strong>Service :</strong> {{ $tache->service_label }}</div>
            @if($tache->assigne)<div>👤 <strong>Assignée à :</strong> {{ $tache->assigne->username }}</div>@endif
            <div>📅 <strong>Clôture prévue :</strong> {{ $tache->date_butoir->format('d/m/Y') }}</div>
            @if($tache->description_instruction)<div>📝 {{ Str::limit($tache->description_instruction, 200) }}</div>@endif
        </div>

        <a href="{{ url('/dashboard') }}" style="display:inline-block;margin-top:8px;padding:10px 22px;background:#2563eb;color:white;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">
            Voir le tableau des anomalies →
        </a>
    </div>
    <div class="footer">
        © {{ date('Y') }} MGDS — Ce message a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
