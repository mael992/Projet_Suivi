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
    <div class="header">MGDS — Support technique</div>
    <div class="body">
        <h2 style="margin:0 0 12px;font-size:17px;">Bonjour,</h2>

        @if($pourAdmin)
            <p style="font-size:14px;">
                Un <strong>nouveau message</strong> attend une réponse au support technique.
            </p>
            <div class="info">
                🎫 <strong>Demande :</strong> {{ $demande->reference }}<br>
                👤 <strong>De :</strong> {{ $demande->libelleDemandeur() }}
            </div>
            <a href="{{ route('admin.messages.show', $demande) }}" style="display:inline-block;margin-top:4px;padding:10px 22px;background:#1d3a63;color:white;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">
                Ouvrir la demande →
            </a>
        @else
            <p style="font-size:14px;">
                @if($nouvelle)
                    Votre demande <strong>{{ $demande->reference }}</strong> a bien été transmise au support technique MGDS.
                    Un conseiller vous répondra au plus vite.
                @else
                    Le support technique MGDS a <strong>répondu</strong> à votre demande <strong>{{ $demande->reference }}</strong>.
                @endif
            </p>
            <div class="info">
                🔒 Ce lien est personnel : il vous permet de suivre votre demande et de répondre au support.
                Ne le transmettez pas.
            </div>
            <a href="{{ route('support.suivi', $demande->jeton) }}" style="display:inline-block;margin-top:4px;padding:10px 22px;background:#1d3a63;color:white;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">
                Voir ma demande →
            </a>
        @endif
    </div>
    <div class="footer">
        © {{ date('Y') }} MGDS — Ce message a été envoyé automatiquement, merci de ne pas y répondre.
    </div>
</div>
</body>
</html>
