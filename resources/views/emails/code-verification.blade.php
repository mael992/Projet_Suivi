<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #111; background:#f4f6f9; margin:0; padding:20px; }
.card { background:white; max-width:520px; margin:0 auto; border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1); }
.header { background:#1d3a63; border-bottom:3px solid #b08d4a; padding:20px 24px; color:#fff; font-size:18px; font-weight:bold; }
.body { padding:28px 24px; }
.code { font-size:32px; font-weight:bold; letter-spacing:8px; text-align:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin:18px 0; color:#1d3a63; }
.info { background:#f8fafc; border-left:4px solid #b08d4a; padding:14px 18px; border-radius:4px; margin:16px 0; font-size:13px; }
.footer { border-top:1px solid #eee; padding:14px 24px; font-size:12px; color:#888; }
</style></head>
<body>
<div class="card">
    <div class="header">MGDS — Code de vérification</div>
    <div class="body">
        <h2 style="margin:0 0 12px;font-size:17px;">Bonjour,</h2>

        <p style="font-size:14px;">Voici votre code de vérification {{ $motif }} :</p>

        <div class="code">{{ $code }}</div>

        <p style="font-size:14px;">Ce code est valable <strong>{{ $minutes }} minutes</strong> et ne peut servir qu'une fois.</p>

        <div class="info">
            🔒 Ne communiquez jamais ce code, même à une personne qui se présente comme l'équipe MGDS ou votre mairie.
            Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail et changez votre mot de passe.
        </div>
    </div>
    <div class="footer">
        © {{ date('Y') }} MGDS — Ce message a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
