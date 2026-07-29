<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body   { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
    h1     { font-size: 17px; margin: 0 0 2px; color: #1d3a63; }
    .sub   { color: #666; margin-bottom: 6px; font-size: 10px; }
    .bandeau { background:#f3ead8; border-left:4px solid #b08d4a; padding:8px 10px; margin-bottom:14px; font-size:10px; }
    h2     { font-size: 13px; color: #1d3a63; border-bottom: 2px solid #b08d4a; padding-bottom: 3px; margin: 16px 0 6px; }
    h3     { font-size: 11px; margin: 10px 0 4px; }
    ol, ul { margin: 4px 0 4px 16px; padding: 0; }
    li     { margin-bottom: 3px; }
    .intro { margin: 4px 0 6px; }
    .astuce{ background:#f3ead8; border-left:3px solid #b08d4a; padding:6px 8px; margin:6px 0; }
    .mail  { border:1px solid #ddd; margin:5px 0; }
    .mail-quand { background:#f1f3f6; color:#555; padding:3px 6px; font-size:9px; }
    .mail-objet { font-weight:bold; padding:4px 6px 0; }
    .mail-corps { padding:4px 6px 6px; white-space:pre-wrap; color:#222; font-size:10px; }
    .sommaire { margin-bottom: 10px; font-size: 10px; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #888; text-align: center; }
</style>
</head>
<body>

<h1>🆘 Besoin d'aide — MGDS</h1>
<div class="sub">Mairie – Gestion Des Services</div>

<div class="bandeau">
    <strong>Besoin d'aide du {{ $genereLe->format('d/m/Y') }} à {{ $genereLe->format('H:i') }}</strong><br>
    @php
        $signature = 'Document généré depuis m-gds.com';
        if ($user) {
            $signature .= ' par ' . $user->username;
            if ($user->mairie) {
                $signature .= ' — ' . $user->mairie->nom;
            }
        }
    @endphp
    {{ $signature }}.<br>
    Les informations peuvent évoluer : téléchargez à nouveau ce guide après une mise à jour du site.
</div>

<div class="sommaire">
    <strong>Sommaire :</strong>
    @foreach($sections as $section)
        {{ $section['icone'] }} {{ $section['titre'] }}@if(!$loop->last) · @endif
    @endforeach
</div>

@foreach($sections as $section)
    <h2>{{ $section['icone'] }} {{ $section['titre'] }}</h2>
    <div class="intro">{{ $section['intro'] }}</div>

    <h3>📋 Comment faire</h3>
    <ol>
        @foreach($section['etapes'] as $etape)
            <li>{{ $etape }}</li>
        @endforeach
    </ol>

    @if($section['astuces'])
        <div class="astuce">
            <strong>💡 Bon à savoir</strong>
            <ul>
                @foreach($section['astuces'] as $astuce)
                    <li>{{ $astuce }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($section['emails'])
        <h3>✉️ E-mails que vous pouvez recevoir</h3>
        @foreach($section['emails'] as $email)
            <div class="mail">
                <div class="mail-quand">Quand : {{ $email['quand'] }}</div>
                <div class="mail-objet">Objet : {{ $email['objet'] }}</div>
                <div class="mail-corps">{{ $email['corps'] }}</div>
            </div>
        @endforeach
    @endif
@endforeach

<div class="footer">
    © {{ date('Y') }} MGDS — Besoin d'aide du {{ $genereLe->format('d/m/Y à H:i') }}
</div>

</body>
</html>
