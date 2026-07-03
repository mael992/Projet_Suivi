<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body  { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
    h1    { font-size: 16px; margin-bottom: 2px; }
    .sub  { color: #666; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th    { background: #1f2937; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; }
    td    { border-bottom: 1px solid #ddd; padding: 5px 8px; vertical-align: top; }
    .std  { background: #f3f4f6; font-weight: bold; }
    .grade{ color: #666; font-size: 9px; }
    .footer { margin-top: 18px; font-size: 9px; color: #888; text-align: center; }
</style>
</head>
<body>

<h1>Fiche Contact — {{ $mairie->nom }}</h1>
<div class="sub">Générée le {{ now()->format('d/m/Y à H:i') }} — MGDS</div>

<table>
    <thead>
        <tr>
            <th style="width:40%">Service</th>
            <th style="width:25%">Téléphone</th>
            <th style="width:35%">Adresse mail</th>
        </tr>
    </thead>
    <tbody>
        @foreach($standards as $standard)
            <tr class="std">
                <td>{{ $standard->service_label }} — Standard</td>
                <td>{{ $standard->telephone_complet }}</td>
                <td>—</td>
            </tr>
        @endforeach

        @foreach($contacts as $contact)
            <tr>
                <td>
                    {{ $contact->service_label }}<br>
                    <strong>{{ $contact->prenom }} {{ $contact->nom }}</strong>
                    <span class="grade">({{ $contact->grade_label }})</span>
                </td>
                <td>{{ $contact->telephone_complet }}</td>
                <td>{{ $contact->email ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">© {{ date('Y') }} MGDS — Gestion des services de la mairie</div>

</body>
</html>
