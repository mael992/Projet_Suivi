<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body   { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 40px; }
    .header{ border-bottom: 3px solid #1f2937; padding-bottom: 12px; margin-bottom: 24px; }
    .header h1 { font-size: 20px; margin: 0; }
    .header .sub { color: #666; font-size: 11px; }
    .box   { background: #f8fafc; border: 1px solid #e5e7eb; border-left: 4px solid #1f2937; padding: 14px 18px; margin: 18px 0; }
    .box table td { padding: 4px 8px; }
    .label { color: #666; }
    .value { font-weight: bold; }
    .warn  { background: #fef3c7; border: 1px solid #fde68a; padding: 10px 14px; font-size: 11px; margin-top: 18px; }
    .footer{ margin-top: 40px; font-size: 10px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

<div class="header">
    <h1>MGDS — Gestion des services de la mairie</h1>
    <div class="sub">Courrier d'identifiants de connexion</div>
</div>

<p>Bonjour <strong>{{ $user->prenom }} {{ $user->nom }}</strong>,</p>

<p>
    Un compte vous a été créé sur la plateforme MGDS
    @if($user->mairie) pour la <strong>{{ $user->mairie->nom }}</strong>@endif.
    Voici vos identifiants de connexion provisoires :
</p>

<div class="box">
    <table>
        @if($user->mairie)
        <tr><td class="label">Mairie :</td><td class="value">{{ $user->mairie->nom }}</td></tr>
        @endif
        <tr><td class="label">Identifiant :</td><td class="value">{{ $user->username }}</td></tr>
        <tr><td class="label">Mot de passe provisoire :</td><td class="value">{{ $user->temp_password }}</td></tr>
        @if($user->service)
        <tr><td class="label">Service :</td><td class="value">{{ $user->service_label }}</td></tr>
        @endif
        @if($user->grade)
        <tr><td class="label">Statut :</td><td class="value">{{ $user->grade_label }}</td></tr>
        @endif
    </table>
</div>

<div class="warn">
    ⚠️ Ce mot de passe provisoire est valable <strong>48 heures</strong>.
    À votre première connexion, vous devrez obligatoirement choisir un nouveau mot de passe.
    Passé ce délai, contactez votre responsable ou un administrateur.
</div>

<p>Connectez-vous en sélectionnant votre mairie sur la page de connexion.</p>

<div class="footer">© {{ date('Y') }} MGDS — Document confidentiel, à remettre en main propre.</div>

</body>
</html>
