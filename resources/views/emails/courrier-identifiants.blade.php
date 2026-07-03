<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #111; background:#f4f6f9; margin:0; padding:20px; }
.card { background:white; max-width:520px; margin:0 auto; border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1); }
.header { background:#1f2937; border-bottom:3px solid #2563eb; padding:20px 24px; color:#fff; font-size:18px; font-weight:bold; }
.body { padding:28px 24px; }
.body h2 { margin:0 0 12px; font-size:18px; }
.footer { border-top:1px solid #eee; padding:14px 24px; font-size:12px; color:#888; }
</style></head>
<body>
<div class="card">
    <div class="header">MGDS</div>
    <div class="body">
        <h2>Bonjour {{ $user->prenom }} {{ $user->nom }},</h2>
        <p>Un compte vient de vous être créé sur <strong>MGDS</strong>
        @if($user->mairie) pour la <strong>{{ $user->mairie->nom }}</strong>@endif.</p>
        <p>Vous trouverez en pièce jointe le courrier contenant vos identifiants provisoires.</p>
        <p style="font-size:13px;color:#555;">
            ⚠️ Le mot de passe provisoire est valable 48 heures : à votre première connexion,
            vous devrez en choisir un nouveau.
        </p>
        <a href="{{ url('/login') }}" style="display:inline-block;margin-top:8px;padding:10px 22px;background:#2563eb;color:white;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">
            Se connecter à MGDS →
        </a>
    </div>
    <div class="footer">
        © {{ date('Y') }} MGDS — Ce message a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
