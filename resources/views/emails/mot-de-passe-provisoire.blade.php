<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #111; background:#f4f6f9; margin:0; padding:20px; }
.card { background:white; max-width:520px; margin:0 auto; border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1); }
.header { background:#1f2937; border-bottom:3px solid #2563eb; padding:20px 24px; color:#fff; font-size:18px; font-weight:bold; }
.body { padding:28px 24px; }
.body h2 { margin:0 0 12px; font-size:18px; }
.mdp { display:inline-block; margin:6px 0 14px; padding:12px 20px; background:#f1f5f9; border:1px dashed #94a3b8;
       border-radius:8px; font-family:"Courier New", monospace; font-size:20px; font-weight:bold; letter-spacing:2px; }
.footer { border-top:1px solid #eee; padding:14px 24px; font-size:12px; color:#888; }
</style></head>
<body>
<div class="card">
    <div class="header">MGDS</div>
    <div class="body">
        <h2>Bonjour {{ $user->prenom }} {{ $user->nom }},</h2>
        <p>Un mot de passe provisoire a été demandé pour votre compte
            <strong>{{ $user->username }}</strong>.</p>

        <div class="mdp">{{ $motDePasse }}</div>

        <p style="font-size:13px;color:#555;">
            ⚠️ Il est valable <strong>{{ $heures }} heures</strong> et ne sert qu'une fois :
            dès que vous vous connectez avec, vous devez choisir votre nouveau mot de passe.
        </p>
        <p style="font-size:13px;color:#555;">
            Vous n'êtes à l'origine de cette demande ? Ignorez cet e-mail :
            <strong>votre mot de passe habituel reste valable</strong> et ce mot de passe
            provisoire cessera de fonctionner tout seul.
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
