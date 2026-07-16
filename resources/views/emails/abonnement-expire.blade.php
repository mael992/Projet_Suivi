<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><style>
body { font-family: Arial, sans-serif; color: #111; background:#f4f6f9; margin:0; padding:20px; }
.card { background:white; max-width:520px; margin:0 auto; border-radius:10px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.1); }
.header { background:#1d3a63; border-bottom:3px solid #b08d4a; padding:20px 24px; color:#fff; font-size:18px; font-weight:bold; }
.body { padding:28px 24px; }
.info { background:#f8fafc; border-left:4px solid #b08d4a; padding:14px 18px; border-radius:4px; margin:16px 0; font-size:14px; }
.footer { border-top:1px solid #eee; padding:14px 24px; font-size:12px; color:#888; }
</style></head>
<body>
<div class="card">
    <div class="header">MGDS — {{ $mairie->nom }}</div>
    <div class="body">
        <h2 style="margin:0 0 12px;font-size:17px;">Bonjour,</h2>

        <p style="font-size:14px;">
            L'abonnement de votre mairie au service <strong>m-gds.com</strong>
            a pris fin le <strong>{{ $mairie->date_fin_abonnement->format('d/m/Y') }}</strong>.
            Votre mairie est désormais désabonnée de notre service.
        </p>

        <p style="font-size:14px;">
            C'est avec regret que nous prenons acte du non-renouvellement de votre abonnement.
        </p>

        <div class="info">
            Pour souscrire de nouveau ou demander tout renseignement, merci de nous contacter :
            <br><br>
            @if(config('mgds.support_phone'))📞 <strong>{{ config('mgds.support_phone') }}</strong><br>@endif
            @if(config('mgds.support_email'))✉️ <a href="mailto:{{ config('mgds.support_email') }}">{{ config('mgds.support_email') }}</a>@endif
        </div>

        <p style="font-size:14px;">Nous espérons vous retrouver très bientôt parmi nos mairies partenaires.</p>
    </div>
    <div class="footer">
        © {{ date('Y') }} MGDS — Ce message a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
