<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body   { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 40px; }
    .header{ border-bottom: 3px solid #1d3a63; padding-bottom: 12px; margin-bottom: 22px; }
    h1     { font-size: 19px; margin: 0; color: #1d3a63; }
    .sub   { color: #666; font-size: 11px; margin-top: 3px; }
    .bloc  { background: #f8fafc; border-left: 4px solid #b08d4a; padding: 14px 18px; margin: 18px 0; }
    table  { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
    th     { background: #1d3a63; color: #fff; text-align: left; padding: 5px 8px; }
    td     { border-bottom: 1px solid #ddd; padding: 4px 8px; }
    .ref   { font-family: DejaVu Sans Mono, monospace; font-size: 11px; }
    .footer{ margin-top: 40px; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

<div class="header">
    <h1>Attestation de destruction de données</h1>
    <div class="sub">MGDS — Mairie Gestion Des Services · m-gds.com</div>
</div>

<p>
    Je soussigné, éditeur de la plateforme MGDS, atteste que l'ensemble des données
    de la collectivité désignée ci-dessous a été <strong>définitivement supprimé</strong>
    de la plateforme, conformément à sa demande et aux dispositions du Règlement général
    sur la protection des données (RGPD).
</p>

<div class="bloc">
    <strong>Collectivité :</strong> {{ $nomMairie }} ({{ $codePostal }})<br>
    <strong>Date et heure de destruction :</strong> {{ $genereLe->format('d/m/Y à H:i') }}<br>
    <strong>Référence de l'opération :</strong> <span class="ref">{{ $reference }}</span><br>
    @if($operateur)<strong>Opérée par :</strong> {{ $operateur }}@endif
</div>

<p>Éléments supprimés :</p>

<table>
    <thead>
        <tr><th>Catégorie de données</th><th style="width:140px;">Enregistrements</th></tr>
    </thead>
    <tbody>
    @foreach($volumes as $categorie => $nombre)
        <tr>
            <td>{{ ucfirst(str_replace('_', ' ', $categorie)) }}</td>
            <td>{{ $nombre }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<p style="margin-top:18px;">
    Les fichiers déposés (photos, pièces jointes, plans) rattachés à cette collectivité
    ont également été effacés du serveur. Les comptes utilisateurs correspondants ont été
    supprimés et ne permettent plus aucune connexion.
</p>

<p>
    Les sauvegardes techniques encore en rotation sont écrasées automatiquement dans un
    délai maximal de trente jours, à l'issue duquel aucune copie ne subsiste.
</p>

<div class="footer">
    Document généré automatiquement le {{ $genereLe->format('d/m/Y à H:i') }} — MGDS.
    Référence {{ $reference }} — à conserver par la collectivité.
</div>

</body>
</html>
