<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body  { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 14px 18px; }
    h1    { font-size: 18px; margin: 0 0 2px; color: #1d3a63; }
    .sub  { color: #555; font-size: 11px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #cbd5e0; padding: 4px 5px; text-align: center; vertical-align: middle; }
    th    { background: #1d3a63; color: #fff; font-size: 10px; }
    .agent { text-align: left; background: #f7fafc; }
    .agent .role { display: block; color: #718096; font-size: 9px; }
    .repos   { background: #edf2f7; text-transform: uppercase; font-weight: bold; color: #718096; }
    .absent  { background: #fff3cd; text-transform: uppercase; font-weight: bold; color: #856404; font-size: 9px; }
    .jour    { font-size: 10px; }
    .sousTotal { display: block; font-weight: bold; color: #e53e3e; font-size: 9px; }
    .total   { font-weight: bold; color: #2b6cb0; background: #f7fafc; }
    .signature { font-size: 9px; }
</style>
</head>
<body>

@php use App\Models\PlanningLigne; @endphp
@php $joursLabels = [1 => 'LUN', 2 => 'MAR', 3 => 'MER', 4 => 'JEU', 5 => 'VEN', 6 => 'SAM', 7 => 'DIM']; @endphp

<h1>🕒 Planning — {{ $planning->libelle() }}</h1>
<div class="sub">
    {{ $planning->mairie->nom }} · {{ $planning->periodeLabel() }} —
    <strong>édité le {{ $genereLe->format('d/m/Y à H:i') }}</strong>
</div>

<table>
    <thead>
        <tr>
            <th style="width:130px;">EMPLOYÉ(E)</th>
            <th style="width:44px;">DURÉE</th>
            @foreach($joursLabels as $numero => $label)
                <th>{{ $label }}<br>{{ $dates[$numero]->format('d/m') }}</th>
            @endforeach
            <th style="width:44px;">TOTAL</th>
            <th style="width:44px;">VAR.</th>
            <th style="width:70px;">SIGNATURE</th>
        </tr>
    </thead>
    <tbody>
    @foreach($lignes as $ligne)
        @php
            $total     = $ligne->totalMinutes($dates);
            $variation = $ligne->variationMinutes($dates);
        @endphp
        <tr>
            <td class="agent">
                {{ $ligne->user?->full_name }}
                <span class="role">{{ $ligne->user?->service_label }}</span>
            </td>
            <td>{{ PlanningLigne::formatMinutes($ligne->duree_contrat) }}</td>

            @foreach(array_keys($joursLabels) as $numero)
                @php
                    $journee = $ligne->journee($numero);
                    $absence = $ligne->absencePour($dates[$numero]);
                @endphp
                @if($absence)
                    <td class="absent">{{ $absence->motifLabel() }}</td>
                @elseif($journee['repos'])
                    <td class="repos">Repos</td>
                @else
                    <td class="jour">
                        @foreach($journee['creneaux'] as $creneau)
                            {{ $creneau[0] }}–{{ $creneau[1] }}<br>
                        @endforeach
                        <span class="sousTotal">{{ PlanningLigne::formatMinutes($ligne->minutesJour($numero, $dates[$numero])) }}</span>
                    </td>
                @endif
            @endforeach

            <td class="total">{{ PlanningLigne::formatMinutes($total) }}</td>
            <td class="total">{{ PlanningLigne::formatMinutes($variation) }}</td>
            <td class="signature">
                @if($ligne->estSigne())
                    ✅ {{ $ligne->signe_at->format('d/m/Y') }}
                @else
                    &nbsp;
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
