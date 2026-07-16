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
    <div class="header">MGDS — {{ $mairie->nom }}</div>
    <div class="body">
        <h2 style="margin:0 0 12px;font-size:17px;">Bonjour,</h2>

        <div class="warn">
            ⚠️ <strong>Attention :</strong> l'abonnement de votre mairie au service
            <strong>m-gds.com</strong> se termine <strong>aujourd'hui,
            le {{ $mairie->date_fin_abonnement->format('d/m/Y') }}</strong>.<br><br>
            Votre mairie n'aura plus accès au site à partir de la fin de la journée.
        </div>

        <p style="font-size:14px;">
            Pour renouveler votre abonnement sans interruption de service,
            contactez-nous dès maintenant :
        </p>
        <p style="font-size:14px;">
            @if(config('mgds.support_phone'))📞 <strong>{{ config('mgds.support_phone') }}</strong><br>@endif
            @if(config('mgds.support_email'))✉️ <a href="mailto:{{ config('mgds.support_email') }}">{{ config('mgds.support_email') }}</a>@endif
        </p>
    </div>
    <div class="footer">
        © {{ date('Y') }} MGDS — Ce message a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
