<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    /* Écritures volontairement grandes : le plan est lu sur papier, souvent debout */
    body   { font-family: DejaVu Sans, sans-serif; font-size: 14px; color: #111; margin: 0; padding: 14px 18px; }
    h1     { font-size: 21px; margin: 0 0 3px; color: #1d3a63; }
    .sub   { color: #555; font-size: 13px; margin-bottom: 10px; }
    /* Fond retiré : le plan reste lisible et n'assombrit pas l'impression */
    .plan  { position: relative; border: 2px solid #8f8a7e; background: #fff; }
    .stand { position: absolute; background: #b08d4a; border: 1px solid #12294a; color: #fff;
             font-size: 10px; font-weight: bold; text-align: center; overflow: hidden; }
    .stand-nomme { background: #8a6a3b; }
    .obst  { position: absolute; border: 1px dashed #c94f46; border-radius: 50%;
             background: rgba(201,79,70,0.12); text-align: center; font-size: 11px;
             font-weight: bold; color: #7a2e2e; }
    .legende { margin-top: 10px; font-size: 12px; color: #333; }
    table  { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
    th     { background: #1d3a63; color: #fff; text-align: left; padding: 5px 7px; }
    td     { border-bottom: 1px solid #ddd; padding: 5px 7px; }
</style>
</head>
<body>

@php
    // Échelle : le plan occupe toute la largeur utile de la page paysage
    $largeurPx = 760;
    $echelle   = $largeurPx / max($longueur, 0.1);
    $hauteurPx = $largeur * $echelle;

    $emojis = ['arbre' => 'A', 'fontaine' => 'F', 'poteau' => 'P', 'temporaire' => 'T'];
    $rayons = ['arbre' => 1.6, 'fontaine' => 2.2, 'poteau' => 0.4, 'temporaire' => 1.2];

    $nommes = collect($stands)->filter(fn ($s) => ! empty($s['nom']))->values();
@endphp

<h1>🗺️ Plan du marché — {{ $zone->nom }}</h1>
<div class="sub">
    {{ $mairie->nom }} · {{ $zone->marche_type ? $zone->marche_type_label : 'Marché' }} ·
    {{ count($stands) }} emplacements · zone {{ $longueur }} m × {{ $largeur }} m —
    <strong>plan du {{ $genereLe->format('d/m/Y à H:i') }}</strong>
</div>

<div class="plan" style="width: {{ $largeurPx }}px; height: {{ $hauteurPx }}px;">
    @foreach($stands as $i => $s)
        @php
            $horiz = ((float) $s['rot']) % 180 == 0;
            $w = ($horiz ? $s['w'] : $s['d']) * $echelle;
            $h = ($horiz ? $s['d'] : $s['w']) * $echelle;
        @endphp
        <div class="stand {{ ! empty($s['nom']) ? 'stand-nomme' : '' }}"
             style="left: {{ ($s['x'] * $echelle) - $w / 2 }}px; top: {{ ($s['y'] * $echelle) - $h / 2 }}px;
                    width: {{ $w }}px; height: {{ $h }}px; line-height: {{ $h }}px;">
            {{ $s['nom'] ?: ($i + 1) }}
        </div>
    @endforeach

    @foreach($obstacles as $o)
        @php
            $r = ($rayons[$o['type']] ?? 1.5) * $echelle;
        @endphp
        <div class="obst"
             style="left: {{ ($o['x'] * $echelle) - $r }}px; top: {{ ($o['y'] * $echelle) - $r }}px;
                    width: {{ $r * 2 }}px; height: {{ $r * 2 }}px; line-height: {{ $r * 2 }}px;">
            {{ $emojis[$o['type']] ?? '?' }}
        </div>
    @endforeach
</div>

<div class="legende">
    Emplacements numérotés de 1 à {{ count($stands) }} — les cases foncées portent une référence ou un nom d'exposant.
    Obstacles : A = arbre, F = fontaine, P = poteau électrique, T = obstacle temporaire.
</div>

@if($nommes->isNotEmpty())
    <table>
        <thead>
            <tr><th style="width:80px;">Emplacement</th><th>Référence / Exposant</th></tr>
        </thead>
        <tbody>
        @foreach($stands as $i => $s)
            @if(! empty($s['nom']))
                <tr><td>{{ $i + 1 }}</td><td>{{ $s['nom'] }}</td></tr>
            @endif
        @endforeach
        </tbody>
    </table>
@endif

</body>
</html>
