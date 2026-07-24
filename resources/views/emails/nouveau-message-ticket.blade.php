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
    <div class="header">MGDS — {{ $ticket->mairie?->nom }}</div>
    <div class="body">
        <h2 style="margin:0 0 12px;font-size:17px;">Bonjour,</h2>

        <p style="font-size:14px;">
            Vous avez un <strong>nouveau message</strong> concernant la demande
            « <strong>{{ $ticket->sujet }}</strong> ».
            Merci de consulter votre ticket <strong>{{ $ticket->reference }}</strong>
            pour lire le message en attente de réponse.
        </p>

        @if($pourCitoyen)
            <div class="info">
                🎫 <strong>Votre ticket :</strong> {{ $ticket->reference }}<br>
                Rendez-vous sur la page « Contacter votre Mairie », onglet
                « J'ai déjà un ticket », avec votre numéro de ticket et votre e-mail.
            </div>
            <a href="{{ url('/contacter-mairie') }}" style="display:inline-block;margin-top:4px;padding:10px 22px;background:#1d3a63;color:white;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">
                Consulter mon ticket →
            </a>
        @else
            <div class="info">
                🎫 <strong>Ticket :</strong> {{ $ticket->reference }} · {{ $ticket->service_label }}<br>
                👤 <strong>De :</strong> {{ $ticket->nom_complet }} · {{ $ticket->email }}
            </div>
            <a href="{{ url('/messagerie') }}" style="display:inline-block;margin-top:4px;padding:10px 22px;background:#1d3a63;color:white;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">
                Ouvrir le Centre de Messagerie →
            </a>
        @endif
    </div>
    <div class="footer">
        © {{ date('Y') }} MGDS — Ce message a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
