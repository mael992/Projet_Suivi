@extends('layouts.app')

@section('content')
@php
    $config    = $zone->config ?? [];
    $noms      = $config['noms'] ?? [];
    $obstacles = $config['obstacles'] ?? [];
@endphp

<div class="container py-4" style="max-width:1000px;">

    <a href="{{ route('marche.public') }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">
        ← {{ __('Marché — exposants') }}
    </a>

    <h1 class="h3 mb-1">🗺️ {{ __('Plan du marché') }} — {{ $zone->nom }}</h1>
    <p class="text-muted mb-3" style="font-size:14px;">
        {{ $zone->mairie->nom }} ·
        {{ __('Marché du') }} <strong>{{ $code->valable_le->format('d/m/Y') }}</strong> ·
        {{ __('code') }} <strong>{{ $code->code }}</strong>
    </p>

    <div class="card shadow-sm">
        <div class="card-body p-2">
            <svg id="planPublic" style="width:100%;height:60vh;min-height:320px;display:block;border-radius:8px;background:#e8e4da;"></svg>
        </div>
    </div>

    <p class="text-muted mt-2" style="font-size:12px;">
        💡 {{ __('Les emplacements portant un nom ou une référence sont déjà attribués. Contactez la mairie pour toute question.') }}
    </p>
</div>

<script>
// Mêmes réglages que le plan de la mairie : le rendu est identique, en lecture seule
const CFG = {
    longueur:    {{ $zone->longueur_m }},
    largeur:     {{ $zone->largeur_m }},
    disposition: @json($config['disposition'] ?? 'double'),
    ecart:       {{ $config['ecart'] ?? 1 }},
    stand:       {{ $config['taille_stand'] ?? 3 }},
    allee:       {{ $config['allee'] ?? 5 }},
    degagement:  {{ $config['degagement'] ?? 1 }},
};
const NOMS      = @json($noms);
const OBSTACLES = @json(array_values($obstacles));
const RAYONS    = { arbre: 1.6, fontaine: 2.2, poteau: 0.4, temporaire: 1.2 };
const EMOJIS    = { arbre: '🌳', fontaine: '⛲', poteau: '⚡', temporaire: '🚧' };
const PROFONDEUR = 2.5;

function calculerStands() {
    const c = CFG, pas = c.stand + c.ecart;
    let stands = [];

    const rangee = (y, rot, x0 = 1, x1 = null) => {
        x1 = x1 ?? c.longueur - 1;
        for (let x = x0 + c.stand / 2; x + c.stand / 2 <= x1; x += pas) {
            stands.push({ x, y, w: c.stand, d: PROFONDEUR, rot });
        }
    };
    const colonne = (x, rot, y0 = 1, y1 = null) => {
        y1 = y1 ?? c.largeur - 1;
        for (let y = y0 + c.stand / 2; y + c.stand / 2 <= y1; y += pas) {
            stands.push({ x, y, w: c.stand, d: PROFONDEUR, rot });
        }
    };

    switch (c.disposition) {
        case 'rangee': rangee(c.largeur / 2, 0); break;
        case 'double':
            rangee(c.largeur / 2 - c.allee / 2 - PROFONDEUR / 2, 0);
            rangee(c.largeur / 2 + c.allee / 2 + PROFONDEUR / 2, 180);
            break;
        case 'grille': {
            const bloc = 2 * PROFONDEUR + 0.4, motif = bloc + c.allee;
            for (let y = 1 + c.allee / 2 + PROFONDEUR / 2; y + PROFONDEUR / 2 + c.allee / 2 <= c.largeur - 1; y += motif) {
                rangee(y, 180);
                if (y + bloc <= c.largeur - 1 - c.allee / 2) rangee(y + PROFONDEUR + 0.4, 0);
            }
            break;
        }
        case 'u':
            rangee(1 + PROFONDEUR / 2, 180);
            colonne(1 + PROFONDEUR / 2, 90, 1 + PROFONDEUR + c.ecart);
            colonne(c.longueur - 1 - PROFONDEUR / 2, 270, 1 + PROFONDEUR + c.ecart);
            break;
        default:
            rangee(1 + PROFONDEUR / 2, 180);
            rangee(c.largeur - 1 - PROFONDEUR / 2, 0);
            if (c.largeur > 4 * PROFONDEUR) {
                colonne(1 + PROFONDEUR / 2, 90, 1 + PROFONDEUR + c.ecart, c.largeur - 1 - PROFONDEUR - c.ecart);
                colonne(c.longueur - 1 - PROFONDEUR / 2, 270, 1 + PROFONDEUR + c.ecart, c.largeur - 1 - PROFONDEUR - c.ecart);
            }
    }

    return stands.filter(s =>
        s.x - s.w / 2 >= 0 && s.x + s.w / 2 <= c.longueur &&
        s.y - s.d / 2 >= 0 && s.y + s.d / 2 <= c.largeur &&
        !OBSTACLES.some(o => Math.hypot(o.x - s.x, o.y - s.y) < (RAYONS[o.type] || 1.5) + c.degagement + Math.max(s.w, s.d) / 2)
    );
}

const SVG_NS = 'http://www.w3.org/2000/svg';
const el = (tag, attrs) => {
    const e = document.createElementNS(SVG_NS, tag);
    for (const k in attrs) e.setAttribute(k, attrs[k]);
    return e;
};

(function dessiner() {
    const svg = document.getElementById('planPublic');
    svg.setAttribute('viewBox', `-1 -1 ${CFG.longueur + 2} ${CFG.largeur + 2}`);
    svg.appendChild(el('rect', { x: 0, y: 0, width: CFG.longueur, height: CFG.largeur, fill: '#b9b4a8', stroke: '#8f8a7e', 'stroke-width': 0.15 }));

    calculerStands().forEach((s, i) => {
        const horiz = s.rot % 180 === 0;
        svg.appendChild(el('rect', {
            x: s.x - (horiz ? s.w : s.d) / 2,
            y: s.y - (horiz ? s.d : s.w) / 2,
            width:  horiz ? s.w : s.d,
            height: horiz ? s.d : s.w,
            fill: NOMS[i] ? '#8a6a3b' : '#b08d4a',
            stroke: '#12294a', 'stroke-width': 0.1, rx: 0.25,
        }));
        const t = el('text', {
            x: s.x, y: s.y, 'font-size': 0.75, 'text-anchor': 'middle',
            'dominant-baseline': 'central', fill: '#fff', 'font-weight': 'bold',
        });
        t.textContent = NOMS[i] || String(i + 1);
        svg.appendChild(t);
    });

    OBSTACLES.forEach(o => {
        const r = RAYONS[o.type] || 1.5;
        svg.appendChild(el('circle', { cx: o.x, cy: o.y, r, fill: 'rgba(255,255,255,0.5)', stroke: '#666', 'stroke-width': 0.06 }));
        const t = el('text', { x: o.x, y: o.y, 'font-size': Math.max(r * 1.4, 1.8), 'text-anchor': 'middle', 'dominant-baseline': 'central' });
        t.textContent = EMOJIS[o.type] || '';
        svg.appendChild(t);
    });
})();
</script>
@endsection
