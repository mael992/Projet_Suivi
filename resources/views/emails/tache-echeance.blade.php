<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #111; background:#f4f6f9; margin:0; padding:20px; }
.card { background:white; max-width:520px; margin:0 auto; border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1); }
.header { background:#1d3a63; border-bottom:3px solid #b08d4a; padding:20px 24px; color:#fff; font-size:18px; font-weight:bold; }
.body { padding:28px 24px; }
.warn { background:#fef3c7; border:1px solid #fde68a; border-radius:6px; padding:14px 18px; font-size:14px; margin:16px 0; }
.footer { border-top:1px solid #eee; padding:14px 24px; font-size:12px; color:#888; }
</style></head>
<body>
<div class="card">
    <div class="header">MGDS — {{ $tache->mairie?->nom }}</div>
    <div class="body">
        <h2 style="margin:0 0 12px;font-size:17px;">Bonjour {{ $destinataire->prenom }},</h2>

        <div class="warn">
            ⏰ <strong>Ne l'oubliez pas :</strong> vous avez une tâche de travail à réaliser
            <strong>au plus tard aujourd'hui</strong>, le {{ $tache->date_butoir->format('d/m/Y') }}.
        </div>

        <p style="font-size:14px;">
            🔖 <strong>Référence :</strong> {{ $tache->reference }}<br>
            👥 <strong>Service :</strong> {{ $tache->service_label }}
            @if($tache->description_instruction)<br>📝 {{ \Illuminate\Support\Str::limit($tache->description_instruction, 200) }}@endif
        </p>

        <a href="{{ url('/dashboard') }}" style="display:inline-block;margin-top:8px;padding:10px 22px;background:#1d3a63;color:white;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">
            Voir la tâche →
        </a>
    </div>
    <div class="footer">
        © {{ date('Y') }} MGDS — Ce message a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
