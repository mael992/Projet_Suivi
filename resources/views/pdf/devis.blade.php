<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body    { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 34px 40px; }
    .entete { border-bottom: 3px solid #1d3a63; padding-bottom: 10px; margin-bottom: 16px; }
    h1      { font-size: 20px; margin: 0; color: #1d3a63; }
    .ref    { font-size: 12px; color: #555; margin-top: 2px; }
    .cols   { width: 100%; margin-bottom: 14px; }
    .cols td{ vertical-align: top; width: 50%; padding: 0; }
    .bloc   { background: #f8fafc; border-left: 4px solid #b08d4a; padding: 10px 12px; }
    .bloc strong { display: block; margin-bottom: 3px; color: #1d3a63; }
    table.lignes { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.lignes th { background: #1d3a63; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; }
    table.lignes td { border-bottom: 1px solid #ddd; padding: 6px 8px; }
    .num    { text-align: right; }
    .totaux { width: 46%; margin-left: 54%; margin-top: 10px; border-collapse: collapse; }
    .totaux td { padding: 5px 8px; border-bottom: 1px solid #eee; }
    .totaux .ttc { background: #1d3a63; color: #fff; font-weight: bold; border: none; }
    .mentions { margin-top: 16px; font-size: 10px; color: #444; line-height: 1.5; }
    .signature { margin-top: 22px; border: 1px dashed #999; padding: 10px 12px; font-size: 10px; }
    .pied   { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8.5px; color: #777; text-align: center; }
</style>
</head>
<body>

<div class="entete">
    <h1>Devis</h1>
    <div class="ref">
        N° {{ $devis->reference }} — établi le {{ $devis->date_devis->format('d/m/Y') }} —
        valable jusqu'au <strong>{{ $devis->valableJusquau()->format('d/m/Y') }}</strong>
        ({{ $devis->validite_jours }} jours)
    </div>
</div>

<table class="cols">
    <tr>
        <td style="padding-right:8px;">
            <div class="bloc">
                <strong>Prestataire</strong>
                {{ $editeur['nom'] }}@if($editeur['forme']) — {{ $editeur['forme'] }}@endif<br>
                @if($editeur['adresse']){{ $editeur['adresse'] }}<br>@endif
                @if($editeur['code_postal'] || $editeur['ville']){{ $editeur['code_postal'] }} {{ $editeur['ville'] }}<br>@endif
                @if(config('mgds.support_phone'))Tél. {{ config('mgds.support_phone') }}<br>@endif
                @if(config('mgds.support_email')){{ config('mgds.support_email') }}<br>@endif
                @if($editeur['siret'])SIRET : {{ $editeur['siret'] }}<br>@endif
                @if($editeur['rcs'])RCS : {{ $editeur['rcs'] }}<br>@endif
                @if($editeur['tva'])TVA intracom. : {{ $editeur['tva'] }}@endif
            </div>
        </td>
        <td style="padding-left:8px;">
            <div class="bloc">
                <strong>Client</strong>
                {{ $devis->client_nom }}<br>
                @if($devis->client_adresse){{ $devis->client_adresse }}<br>@endif
                @if($devis->client_email){{ $devis->client_email }}@endif
            </div>
        </td>
    </tr>
</table>

<table class="lignes">
    <thead>
        <tr>
            <th>Désignation de la prestation</th>
            <th class="num" style="width:70px;">Quantité</th>
            <th class="num" style="width:95px;">P.U. HT</th>
            <th class="num" style="width:95px;">Total HT</th>
        </tr>
    </thead>
    <tbody>
    @foreach($devis->lignes as $ligne)
        @php $total = ((float) $ligne['quantite']) * ((float) $ligne['prix_unitaire']); @endphp
        <tr>
            <td>{{ $ligne['designation'] }}</td>
            <td class="num">{{ rtrim(rtrim(number_format((float) $ligne['quantite'], 2, ',', ' '), '0'), ',') }}</td>
            <td class="num">{{ number_format((float) $ligne['prix_unitaire'], 2, ',', ' ') }} €</td>
            <td class="num">{{ number_format($total, 2, ',', ' ') }} €</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totaux">
    <tr>
        <td>Total HT</td>
        <td class="num">{{ number_format($devis->totalHt(), 2, ',', ' ') }} €</td>
    </tr>
    <tr>
        <td>TVA {{ rtrim(rtrim(number_format($devis->taux_tva, 2, ',', ' '), '0'), ',') }} %</td>
        <td class="num">{{ number_format($devis->montantTva(), 2, ',', ' ') }} €</td>
    </tr>
    <tr>
        <td class="ttc">Total TTC</td>
        <td class="ttc num">{{ number_format($devis->totalTtc(), 2, ',', ' ') }} €</td>
    </tr>
</table>

<div class="mentions">
    @if($devis->lieu_execution)<strong>Lieu d'exécution :</strong> {{ $devis->lieu_execution }}<br>@endif
    @if($devis->delai_execution)<strong>Délai d'exécution :</strong> {{ $devis->delai_execution }}<br>@endif
    @if($devis->modalites_paiement)<strong>Modalités de paiement :</strong> {{ $devis->modalites_paiement }}<br>@endif
    @if($devis->conditions)<strong>Conditions d'exécution :</strong> {{ $devis->conditions }}<br>@endif
    @unless($editeur['tva_applicable'])
        <em>TVA non applicable, article 293 B du CGI.</em><br>
    @endunless
    Devis gratuit et sans engagement. Passé le {{ $devis->valableJusquau()->format('d/m/Y') }}, les prix
    indiqués ne sont plus garantis.
</div>

<div class="signature">
    <strong>Bon pour accord</strong> — à retourner daté et signé, avec la mention « Devis reçu avant exécution des prestations ».<br><br>
    Date : ............................  Signature et cachet :
</div>

<div class="pied">
    {{ $editeur['nom'] }} — Devis {{ $devis->reference }} — page généré le {{ now()->format('d/m/Y à H:i') }}
</div>

</body>
</html>
