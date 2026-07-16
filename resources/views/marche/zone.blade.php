@extends('layouts.app')

@php use App\Models\MarcheZone; @endphp

@section('content')
@php
    $peutEditer  = auth()->user()->aDroit('marche_gestion');
    $mairieParam = request()->only('mairie');
    $config      = $zone->config ?? [];
@endphp

<div class="container-fluid px-3 px-md-4 py-4">

    <a href="{{ route('marche.ville', $mairieParam) }}" class="text-decoration-none d-inline-block mb-2" style="font-size:14px;">
        ← {{ __('Vue aérienne') }}
    </a>
    <h1 class="h3 mb-1">🎪 {{ $zone->nom }} <span class="badge bg-secondary bg-opacity-25 text-secondary align-middle" style="font-size:12px;">{{ __($zone->type_label) }}</span></h1>
    <p class="text-muted mb-3" style="font-size:14px;">
        {{ __('Choisissez le type de marché, la disposition des exposants et placez les obstacles : le marché se dessine en 3D.') }}
    </p>

    @include('marche.partials.onglets')

    <div class="row g-3">

        {{-- ── Panneau de configuration ── --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">

                    <label class="form-label fw-semibold mb-1" style="font-size:13px;">{{ __('Type de marché') }}</label>
                    <select id="cfgType" class="form-select form-select-sm mb-3" {{ $peutEditer ? '' : 'disabled' }}>
                        <option value="">— {{ __('Sélectionnez') }} —</option>
                        @foreach(MarcheZone::TYPES_MARCHE as $cle => $label)
                            <option value="{{ $cle }}" @selected($zone->marche_type === $cle)>{{ __($label) }}</option>
                        @endforeach
                    </select>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label mb-1" style="font-size:12px;">{{ __('Longueur de la zone (m)') }}</label>
                            <input type="number" id="cfgLongueur" class="form-control form-control-sm" min="5" max="2000"
                                   value="{{ $zone->longueur_m }}" {{ $peutEditer ? '' : 'disabled' }}>
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1" style="font-size:12px;">{{ __('Largeur de la zone (m)') }}</label>
                            <input type="number" id="cfgLargeur" class="form-control form-control-sm" min="3" max="2000"
                                   value="{{ $zone->largeur_m }}" {{ $peutEditer ? '' : 'disabled' }}>
                        </div>
                    </div>

                    <label class="form-label fw-semibold mb-1" style="font-size:13px;">{{ __('Disposition des exposants') }}</label>
                    <select id="cfgDisposition" class="form-select form-select-sm mb-3" {{ $peutEditer ? '' : 'disabled' }}>
                        @foreach(['rangee' => 'Rangée simple', 'double' => 'Double rangée (allée centrale)', 'grille' => 'Grille (plusieurs allées)', 'u' => 'En U', 'peripherie' => 'En périphérie'] as $cle => $label)
                            <option value="{{ $cle }}" @selected(($config['disposition'] ?? 'double') === $cle)>{{ __($label) }}</option>
                        @endforeach
                    </select>

                    <label class="form-label mb-1" style="font-size:12px;">
                        {{ __('Écart entre exposants') }} : <strong><span id="ecartVal">{{ $config['ecart'] ?? 1 }}</span> m</strong>
                    </label>
                    <input type="range" id="cfgEcart" class="form-range mb-2" min="0" max="20" step="0.5"
                           value="{{ $config['ecart'] ?? 1 }}" {{ $peutEditer ? '' : 'disabled' }}>

                    <label class="form-label mb-1" style="font-size:12px;">
                        {{ __('Taille d\'un stand') }} : <strong><span id="standVal">{{ $config['taille_stand'] ?? 3 }}</span> m</strong>
                    </label>
                    <input type="range" id="cfgStand" class="form-range mb-2" min="2" max="10" step="0.5"
                           value="{{ $config['taille_stand'] ?? 3 }}" {{ $peutEditer ? '' : 'disabled' }}>

                    <label class="form-label mb-1" style="font-size:12px;">
                        {{ __('Largeur des allées') }} : <strong><span id="alleeVal">{{ $config['allee'] ?? 5 }}</span> m</strong>
                    </label>
                    <input type="range" id="cfgAllee" class="form-range mb-2" min="2" max="15" step="0.5"
                           value="{{ $config['allee'] ?? 5 }}" {{ $peutEditer ? '' : 'disabled' }}>

                    <label class="form-label mb-1" style="font-size:12px;">
                        {{ __('Dégagement autour des obstacles') }} : <strong><span id="degagementVal">{{ $config['degagement'] ?? 1 }}</span> m</strong>
                    </label>
                    <input type="range" id="cfgDegagement" class="form-range mb-3" min="0" max="10" step="0.5"
                           value="{{ $config['degagement'] ?? 1 }}" {{ $peutEditer ? '' : 'disabled' }}>

                    @if($peutEditer)
                    <label class="form-label fw-semibold mb-1" style="font-size:13px;">{{ __('Ajouter un obstacle') }}</label>
                    <div class="d-flex gap-1 flex-wrap mb-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="ajouterObstacle('arbre')">🌳 {{ __('Arbre') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-info"    onclick="ajouterObstacle('fontaine')">⛲ {{ __('Fontaine') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="ajouterObstacle('poteau')">⚡ {{ __('Poteau élec.') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-danger"  onclick="ajouterObstacle('temporaire')">🚧 {{ __('Temporaire') }}</button>
                    </div>
                    <p class="text-muted mb-3" style="font-size:11px;">
                        {{ __('Glissez les obstacles sur le plan 2D — les stands trop proches sont retirés selon le dégagement choisi.') }}
                    </p>
                    @endif

                    <div class="d-flex align-items-center justify-content-between border-top pt-3">
                        <div style="font-size:13px;">
                            <div>🎪 <strong><span id="statStands">0</span></strong> {{ __('exposants possibles') }}</div>
                            <div class="text-muted" style="font-size:11px;">📏 <span id="statMetres">0</span> {{ __('mètres linéaires') }}</div>
                        </div>
                        @if($peutEditer)
                        <button type="button" class="btn btn-success btn-sm" id="btnSauverCfg" onclick="sauverConfig()">💾 {{ __('Enregistrer') }}</button>
                        @endif
                    </div>
                    <div id="cfgMsg" class="text-success mt-2 d-none" style="font-size:12px;">✓ {{ __('Configuration enregistrée.') }}</div>
                </div>
            </div>
        </div>

        {{-- ── Aperçus 2D & 3D ── --}}
        <div class="col-12 col-lg-8">
            <ul class="nav nav-tabs mb-2" id="apercus">
                <li class="nav-item"><button class="nav-link active" data-vue="3d" onclick="montrerVue('3d', this)">🏙 {{ __('Vue 3D') }}</button></li>
                <li class="nav-item"><button class="nav-link" data-vue="2d" onclick="montrerVue('2d', this)">🗺️ {{ __('Plan 2D (obstacles)') }}</button></li>
            </ul>

            <div class="card shadow-sm">
                <div class="card-body p-2">
                    <div id="conteneur3d" style="position:relative;">
                        <div id="vue3d" style="width:100%;height:56vh;min-height:340px;border-radius:8px;overflow:hidden;background:#cfd9e4;touch-action:none;"></div>
                        {{-- Contrôles de secours (fonctionnent partout, souris ou tactile) --}}
                        <div style="position:absolute;right:10px;top:10px;display:flex;flex-direction:column;gap:6px;z-index:5;">
                            <button type="button" class="btn btn-light btn-sm shadow-sm" style="width:38px;" onclick="zoom3D(0.8)" title="{{ __('Zoomer') }}">➕</button>
                            <button type="button" class="btn btn-light btn-sm shadow-sm" style="width:38px;" onclick="zoom3D(1.25)" title="{{ __('Dézoomer') }}">➖</button>
                            <button type="button" class="btn btn-light btn-sm shadow-sm" style="width:38px;" onclick="tourner3D(-Math.PI/8)" title="{{ __('Tourner à gauche') }}">↺</button>
                            <button type="button" class="btn btn-light btn-sm shadow-sm" style="width:38px;" onclick="tourner3D(Math.PI/8)" title="{{ __('Tourner à droite') }}">↻</button>
                            <button type="button" class="btn btn-light btn-sm shadow-sm" style="width:38px;" onclick="resetVue3D()" title="{{ __('Recentrer') }}">🎯</button>
                        </div>
                    </div>
                    <div id="vue2d" class="d-none" style="width:100%;">
                        <svg id="svg2d" style="width:100%;height:56vh;min-height:340px;display:block;border-radius:8px;background:#e8e4da;touch-action:none;"></svg>
                    </div>
                </div>
            </div>
            <p class="text-muted mt-2 mb-0" style="font-size:11px;">
                🖱 / 👆 {{ __('Vue 3D : glisser pour tourner, molette / pincer ou boutons ➕➖ pour zoomer. Plan 2D : glissez les obstacles, double-clic (ou appui long) pour en supprimer un.') }}
            </p>
        </div>
    </div>
</div>

{{-- Import map : permet à OrbitControls de résoudre « three » --}}
<script type="importmap">
{
    "imports": {
        "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
        "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"
    }
}
</script>

<script>
// ── État ─────────────────────────────────────────────────────────
const PEUT_EDITER = @json($peutEditer);
let obstacles = @json(array_values($config['obstacles'] ?? []));   // [{type, x, y}] en mètres
let stands    = [];

const RAYONS_OBSTACLES = { arbre: 1.6, fontaine: 2.2, poteau: 0.4, temporaire: 1.2 };
const EMOJIS           = { arbre: '🌳', fontaine: '⛲', poteau: '⚡', temporaire: '🚧' };

function cfg() {
    return {
        longueur:    parseFloat(document.getElementById('cfgLongueur').value) || 50,
        largeur:     parseFloat(document.getElementById('cfgLargeur').value)  || 30,
        disposition: document.getElementById('cfgDisposition').value || 'double',
        ecart:       parseFloat(document.getElementById('cfgEcart').value),
        stand:       parseFloat(document.getElementById('cfgStand').value),
        allee:       parseFloat(document.getElementById('cfgAllee').value),
        degagement:  parseFloat(document.getElementById('cfgDegagement').value),
    };
}

// ── Calcul des stands ────────────────────────────────────────────
const PROFONDEUR = 2.5; // profondeur d'un stand (m)

function calculerStands() {
    const c   = cfg();
    const pas = c.stand + c.ecart;
    stands = [];

    // rot 0 = comptoir face au sud (+y), 180 = face au nord
    function rangee(y, rot, x0 = 1, x1 = null) {
        x1 = x1 ?? c.longueur - 1;
        for (let x = x0 + c.stand / 2; x + c.stand / 2 <= x1; x += pas) {
            stands.push({ x, y, w: c.stand, d: PROFONDEUR, rot });
        }
    }
    function colonne(x, rot, y0 = 1, y1 = null) {
        y1 = y1 ?? c.largeur - 1;
        for (let y = y0 + c.stand / 2; y + c.stand / 2 <= y1; y += pas) {
            stands.push({ x, y, w: c.stand, d: PROFONDEUR, rot });
        }
    }

    switch (c.disposition) {
        case 'rangee':
            rangee(c.largeur / 2, 0);
            break;

        case 'double': {
            rangee(c.largeur / 2 - c.allee / 2 - PROFONDEUR / 2, 0);
            rangee(c.largeur / 2 + c.allee / 2 + PROFONDEUR / 2, 180);
            break;
        }

        case 'grille': {
            // Paires de rangées dos à dos séparées par des allées
            const bloc = 2 * PROFONDEUR + 0.4;          // deux stands dos à dos
            const motif = bloc + c.allee;               // + une allée
            for (let y = 1 + c.allee / 2 + PROFONDEUR / 2; y + PROFONDEUR / 2 + c.allee / 2 <= c.largeur - 1; y += motif) {
                rangee(y, 180);
                if (y + bloc <= c.largeur - 1 - c.allee / 2) {
                    rangee(y + PROFONDEUR + 0.4, 0);
                }
            }
            break;
        }

        case 'u': {
            rangee(1 + PROFONDEUR / 2, 180);                                    // fond du U
            colonne(1 + PROFONDEUR / 2, 90, 1 + PROFONDEUR + c.ecart);          // branche gauche
            colonne(c.longueur - 1 - PROFONDEUR / 2, 270, 1 + PROFONDEUR + c.ecart); // branche droite
            break;
        }

        case 'peripherie':
        default: {
            rangee(1 + PROFONDEUR / 2, 180);
            rangee(c.largeur - 1 - PROFONDEUR / 2, 0);
            if (c.largeur > 4 * PROFONDEUR) {
                colonne(1 + PROFONDEUR / 2, 90, 1 + PROFONDEUR + c.ecart, c.largeur - 1 - PROFONDEUR - c.ecart);
                colonne(c.longueur - 1 - PROFONDEUR / 2, 270, 1 + PROFONDEUR + c.ecart, c.largeur - 1 - PROFONDEUR - c.ecart);
            }
            break;
        }
    }

    // Retirer les stands hors zone ou trop proches d'un obstacle (dégagement réglable)
    stands = stands.filter(s =>
        s.x - s.w / 2 >= 0 && s.x + s.w / 2 <= c.longueur &&
        s.y - s.d / 2 >= 0 && s.y + s.d / 2 <= c.largeur &&
        !obstacles.some(o => {
            const r = (RAYONS_OBSTACLES[o.type] || 1.5) + c.degagement + Math.max(s.w, s.d) / 2;
            return Math.hypot(o.x - s.x, o.y - s.y) < r;
        })
    );

    document.getElementById('statStands').textContent = stands.length;
    document.getElementById('statMetres').textContent = Math.round(stands.length * c.stand);
}

// ── Vue 2D (SVG) ─────────────────────────────────────────────────
const svg    = document.getElementById('svg2d');
const SVG_NS = 'http://www.w3.org/2000/svg';

function el(tag, attrs) {
    const e = document.createElementNS(SVG_NS, tag);
    for (const k in attrs) e.setAttribute(k, attrs[k]);
    return e;
}

function dessiner2D() {
    const c = cfg();
    svg.setAttribute('viewBox', `-1 -1 ${c.longueur + 2} ${c.largeur + 2}`);
    svg.innerHTML = '';

    svg.appendChild(el('rect', { x: 0, y: 0, width: c.longueur, height: c.largeur, fill: '#b9b4a8', stroke: '#8f8a7e', 'stroke-width': 0.15 }));

    stands.forEach(s => {
        const horiz = s.rot % 180 === 0;
        svg.appendChild(el('rect', {
            x: s.x - (horiz ? s.w : s.d) / 2,
            y: s.y - (horiz ? s.d : s.w) / 2,
            width:  horiz ? s.w : s.d,
            height: horiz ? s.d : s.w,
            fill: '#b08d4a', stroke: '#12294a', 'stroke-width': 0.1, rx: 0.25,
        }));
    });

    obstacles.forEach((o, i) => {
        const rayon = RAYONS_OBSTACLES[o.type] || 1.5;
        const g = el('g', { style: 'cursor:' + (PEUT_EDITER ? 'grab' : 'default') + ';touch-action:none;' });

        // Cercle de dégagement + zone de saisie fiable (souris ET tactile)
        g.appendChild(el('circle', { cx: o.x, cy: o.y, r: rayon + cfg().degagement, fill: 'rgba(217,83,79,0.10)', stroke: '#d9534f', 'stroke-width': 0.08, 'stroke-dasharray': '0.4 0.3' }));
        g.appendChild(el('circle', { cx: o.x, cy: o.y, r: Math.max(rayon, 1.4), fill: 'rgba(255,255,255,0.55)', stroke: '#666', 'stroke-width': 0.06 }));
        const t = el('text', { x: o.x, y: o.y, 'font-size': Math.max(rayon * 1.4, 1.8), 'text-anchor': 'middle', 'dominant-baseline': 'central', 'pointer-events': 'none' });
        t.textContent = EMOJIS[o.type];
        g.appendChild(t);

        if (PEUT_EDITER) {
            let dernierTap = 0, appuiLong = null;
            g.addEventListener('pointerdown', e => {
                e.preventDefault();
                g.setPointerCapture(e.pointerId);
                g.dataset.drag = '1';
                // Appui long (600 ms) = suppression sur mobile
                appuiLong = setTimeout(() => { g.dataset.drag = '0'; supprimerObstacle(i); }, 600);
            });
            g.addEventListener('pointermove', e => {
                if (g.dataset.drag !== '1') return;
                clearTimeout(appuiLong);
                const pt = coordSvg(e);
                o.x = Math.max(0.5, Math.min(cfg().longueur - 0.5, pt.x));
                o.y = Math.max(0.5, Math.min(cfg().largeur  - 0.5, pt.y));
                g.querySelectorAll('circle').forEach(cc => { cc.setAttribute('cx', o.x); cc.setAttribute('cy', o.y); });
                t.setAttribute('x', o.x); t.setAttribute('y', o.y);
            });
            ['pointerup', 'pointercancel'].forEach(ev => g.addEventListener(ev, e => {
                clearTimeout(appuiLong);
                if (g.dataset.drag === '1') {
                    g.dataset.drag = '0';
                    // Double-tap = suppression
                    const maintenant = Date.now();
                    if (maintenant - dernierTap < 350) { supprimerObstacle(i); return; }
                    dernierTap = maintenant;
                    toutRedessiner();
                }
            }));
            g.addEventListener('dblclick', () => supprimerObstacle(i));
        }
        svg.appendChild(g);
    });
}

function supprimerObstacle(i) {
    if (confirm('{{ __('Supprimer cet obstacle ?') }}')) {
        obstacles.splice(i, 1);
        toutRedessiner();
    }
}

function coordSvg(e) {
    const ctm = svg.getScreenCTM();
    if (!ctm) return { x: 0, y: 0 };
    const pt = svg.createSVGPoint();
    pt.x = e.clientX; pt.y = e.clientY;
    return pt.matrixTransform(ctm.inverse());
}

function ajouterObstacle(type) {
    const c = cfg();
    obstacles.push({ type, x: c.longueur / 2, y: c.largeur / 2 });
    montrerVue('2d', document.querySelector('[data-vue="2d"]'));
    toutRedessiner();
}

// ── Vue 3D (three.js) ────────────────────────────────────────────
let THREE3 = null, scene, camera, renderer, controls, groupeMarche;

function textureRayures(THREE, couleur) {
    const cv = document.createElement('canvas');
    cv.width = 128; cv.height = 128;
    const ctx = cv.getContext('2d');
    ctx.fillStyle = '#f4f1e8';
    ctx.fillRect(0, 0, 128, 128);
    ctx.fillStyle = couleur;
    for (let x = 0; x < 128; x += 32) ctx.fillRect(x, 0, 16, 128);
    const tx = new THREE.CanvasTexture(cv);
    tx.wrapS = tx.wrapT = THREE.RepeatWrapping;
    return tx;
}

function textureAsphalte(THREE) {
    const cv = document.createElement('canvas');
    cv.width = 256; cv.height = 256;
    const ctx = cv.getContext('2d');
    ctx.fillStyle = '#9d998f';
    ctx.fillRect(0, 0, 256, 256);
    for (let i = 0; i < 2200; i++) {
        const g = 130 + Math.floor(Math.random() * 60);
        ctx.fillStyle = `rgba(${g},${g - 4},${g - 10},0.35)`;
        ctx.fillRect(Math.random() * 256, Math.random() * 256, 1.6, 1.6);
    }
    const tx = new THREE.CanvasTexture(cv);
    tx.wrapS = tx.wrapT = THREE.RepeatWrapping;
    return tx;
}

async function init3D() {
    const conteneur = document.getElementById('vue3d');
    try {
        const THREE = await import('three');
        const { OrbitControls } = await import('three/addons/controls/OrbitControls.js');
        THREE3 = THREE;

        scene = new THREE.Scene();
        scene.background = new THREE.Color(0xcfd9e4);
        scene.fog = new THREE.Fog(0xcfd9e4, 90, 320);

        camera = new THREE.PerspectiveCamera(50, conteneur.clientWidth / conteneur.clientHeight, 0.1, 2000);

        renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setSize(conteneur.clientWidth, conteneur.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        conteneur.appendChild(renderer.domElement);

        controls = new OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = 0.08;
        controls.maxPolarAngle = Math.PI / 2.1;
        controls.minDistance = 6;
        controls.maxDistance = 400;

        // Éclairage réaliste : ciel + soleil doux
        scene.add(new THREE.HemisphereLight(0xdfe8f2, 0x8c8474, 0.9));
        const soleil = new THREE.DirectionalLight(0xfff3dd, 1.6);
        soleil.position.set(45, 70, 25);
        soleil.castShadow = true;
        soleil.shadow.mapSize.set(2048, 2048);
        soleil.shadow.camera.left = -120; soleil.shadow.camera.right = 120;
        soleil.shadow.camera.top = 120;   soleil.shadow.camera.bottom = -120;
        scene.add(soleil);

        groupeMarche = new THREE.Group();
        scene.add(groupeMarche);

        construire3D();
        resetVue3D();

        window.addEventListener('resize', redimensionner3D);

        (function boucle() {
            requestAnimationFrame(boucle);
            controls.update();
            renderer.render(scene, camera);
        })();
    } catch (err) {
        conteneur.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-3" style="font-size:13px;">'
            + '{{ __('Vue 3D indisponible (connexion requise pour charger le moteur 3D). Le plan 2D reste utilisable.') }}</div>';
    }
}

function redimensionner3D() {
    if (!renderer) return;
    const conteneur = document.getElementById('vue3d');
    if (conteneur.clientWidth === 0) return;
    camera.aspect = conteneur.clientWidth / conteneur.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(conteneur.clientWidth, conteneur.clientHeight);
}

/* Contrôles de secours */
function zoom3D(facteur) {
    if (!camera || !controls) return;
    const dir = camera.position.clone().sub(controls.target);
    const dist = Math.max(controls.minDistance, Math.min(controls.maxDistance, dir.length() * facteur));
    camera.position.copy(controls.target.clone().add(dir.normalize().multiplyScalar(dist)));
}
function tourner3D(angle) {
    if (!camera || !controls) return;
    const p = camera.position.clone().sub(controls.target);
    const cosA = Math.cos(angle), sinA = Math.sin(angle);
    const nx = p.x * cosA - p.z * sinA;
    const nz = p.x * sinA + p.z * cosA;
    camera.position.set(controls.target.x + nx, camera.position.y, controls.target.z + nz);
}
function resetVue3D() {
    if (!camera || !controls) return;
    const c = cfg();
    const d = Math.max(c.longueur, c.largeur);
    camera.position.set(c.longueur / 2, d * 0.7, c.largeur / 2 + d * 0.9);
    controls.target.set(c.longueur / 2, 0, c.largeur / 2);
}

function construire3D() {
    if (!THREE3) return;
    const THREE = THREE3;
    const c = cfg();

    groupeMarche.clear();

    // Sol : asphalte texturé + bordure
    const texSol = textureAsphalte(THREE);
    texSol.repeat.set(c.longueur / 8, c.largeur / 8);
    const sol = new THREE.Mesh(
        new THREE.BoxGeometry(c.longueur, 0.2, c.largeur),
        new THREE.MeshStandardMaterial({ map: texSol, roughness: 0.95 })
    );
    sol.position.set(c.longueur / 2, -0.1, c.largeur / 2);
    sol.receiveShadow = true;
    groupeMarche.add(sol);

    const abords = new THREE.Mesh(
        new THREE.BoxGeometry(c.longueur * 2.4, 0.1, c.largeur * 2.4),
        new THREE.MeshStandardMaterial({ color: 0x7d8b6f, roughness: 1 })
    );
    abords.position.set(c.longueur / 2, -0.22, c.largeur / 2);
    abords.receiveShadow = true;
    groupeMarche.add(abords);

    // Stands « pro » : comptoir bois, structure métal, auvent rayé incliné
    const couleursAuvent = ['#7a2e2e', '#274d6d', '#2e5d43', '#6d5327'];
    const matBois   = new THREE.MeshStandardMaterial({ color: 0x8a6a4b, roughness: 0.8 });
    const matMetal  = new THREE.MeshStandardMaterial({ color: 0x5a5f66, metalness: 0.6, roughness: 0.4 });

    stands.forEach((s, i) => {
        const stand = new THREE.Group();

        // Comptoir
        const comptoir = new THREE.Mesh(new THREE.BoxGeometry(s.w, 0.92, s.d), matBois);
        comptoir.position.y = 0.46;
        comptoir.castShadow = comptoir.receiveShadow = true;
        stand.add(comptoir);

        // Plateau
        const plateau = new THREE.Mesh(new THREE.BoxGeometry(s.w + 0.08, 0.05, s.d + 0.08),
            new THREE.MeshStandardMaterial({ color: 0xa8845c, roughness: 0.6 }));
        plateau.position.y = 0.95;
        plateau.castShadow = true;
        stand.add(plateau);

        // Montants métalliques
        for (const [px, pz] of [[-1, -1], [1, -1], [-1, 1], [1, 1]]) {
            const pied = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 2.3, 8), matMetal);
            pied.position.set(px * (s.w / 2 - 0.08), 1.15, pz * (s.d / 2 - 0.08));
            stand.add(pied);
        }

        // Auvent incliné rayé
        const texAuvent = textureRayures(THREE, couleursAuvent[i % couleursAuvent.length]);
        texAuvent.repeat.set(Math.max(1, Math.round(s.w / 0.8)), 1);
        const auvent = new THREE.Mesh(
            new THREE.BoxGeometry(s.w + 0.5, 0.06, s.d + 0.9),
            new THREE.MeshStandardMaterial({ map: texAuvent, side: THREE.DoubleSide, roughness: 0.7 })
        );
        auvent.position.y = 2.42;
        auvent.position.z = 0.15;
        auvent.rotation.x = -0.16;
        auvent.castShadow = true;
        stand.add(auvent);

        stand.position.set(s.x, 0, s.y);
        stand.rotation.y = -(s.rot * Math.PI) / 180;
        groupeMarche.add(stand);
    });

    // Obstacles réalistes
    obstacles.forEach(o => {
        const g = new THREE.Group();
        if (o.type === 'arbre') {
            const tronc = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.24, 2.4, 10),
                new THREE.MeshStandardMaterial({ color: 0x5d4630, roughness: 0.9 }));
            tronc.position.y = 1.2; tronc.castShadow = true; g.add(tronc);
            const matFeuille = new THREE.MeshStandardMaterial({ color: 0x4a6741, roughness: 0.9 });
            [[0, 3.1, 0, 1.6], [0.7, 2.7, 0.3, 1.1], [-0.6, 2.8, -0.4, 1.0]].forEach(([fx, fy, fz, fr]) => {
                const feuille = new THREE.Mesh(new THREE.SphereGeometry(fr, 10, 8), matFeuille);
                feuille.position.set(fx, fy, fz); feuille.castShadow = true; g.add(feuille);
            });
        } else if (o.type === 'fontaine') {
            const pierre = new THREE.MeshStandardMaterial({ color: 0x9b9b93, roughness: 0.7 });
            const bassin = new THREE.Mesh(new THREE.CylinderGeometry(2.1, 2.2, 0.7, 24), pierre);
            bassin.position.y = 0.35; bassin.castShadow = true; g.add(bassin);
            const eau = new THREE.Mesh(new THREE.CylinderGeometry(1.9, 1.9, 0.1, 24),
                new THREE.MeshStandardMaterial({ color: 0x5e88a8, roughness: 0.15, metalness: 0.25 }));
            eau.position.y = 0.66; g.add(eau);
            const colonne = new THREE.Mesh(new THREE.CylinderGeometry(0.22, 0.34, 1.3, 12), pierre);
            colonne.position.y = 1.2; colonne.castShadow = true; g.add(colonne);
            const vasque = new THREE.Mesh(new THREE.CylinderGeometry(0.75, 0.35, 0.28, 16), pierre);
            vasque.position.y = 1.9; vasque.castShadow = true; g.add(vasque);
        } else if (o.type === 'poteau') {
            const poteau = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.11, 6.4, 8),
                new THREE.MeshStandardMaterial({ color: 0x3c4046, metalness: 0.5, roughness: 0.5 }));
            poteau.position.y = 3.2; poteau.castShadow = true; g.add(poteau);
            const bras = new THREE.Mesh(new THREE.BoxGeometry(1.1, 0.08, 0.08),
                new THREE.MeshStandardMaterial({ color: 0x3c4046 }));
            bras.position.set(0.45, 6.1, 0); g.add(bras);
            const lampe = new THREE.Mesh(new THREE.SphereGeometry(0.18, 10, 8),
                new THREE.MeshStandardMaterial({ color: 0xf5e6b8, emissive: 0x8a6d1f, emissiveIntensity: 0.6 }));
            lampe.position.set(0.95, 6.0, 0); g.add(lampe);
        } else {
            const beton = new THREE.MeshStandardMaterial({ color: 0xb8b2a6, roughness: 0.9 });
            const bloc = new THREE.Mesh(new THREE.BoxGeometry(1.9, 0.85, 0.55), beton);
            bloc.position.y = 0.43; bloc.castShadow = true; g.add(bloc);
            for (const dx of [-0.6, 0, 0.6]) {
                const bande = new THREE.Mesh(new THREE.BoxGeometry(0.32, 0.87, 0.57),
                    new THREE.MeshStandardMaterial({ color: 0xc94f46 }));
                bande.position.set(dx, 0.43, 0); g.add(bande);
            }
        }
        g.position.set(o.x, 0, o.y);
        groupeMarche.add(g);
    });
}

// ── Orchestration ────────────────────────────────────────────────
function toutRedessiner() {
    calculerStands();
    dessiner2D();
    construire3D();
}

function montrerVue(vue, btn) {
    document.querySelectorAll('#apercus .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('conteneur3d').classList.toggle('d-none', vue !== '3d');
    document.getElementById('vue2d').classList.toggle('d-none', vue !== '2d');
    if (vue === '3d') setTimeout(redimensionner3D, 50);
    if (vue === '2d') dessiner2D();
}

['cfgLongueur', 'cfgLargeur', 'cfgEcart', 'cfgStand', 'cfgAllee', 'cfgDegagement'].forEach(id =>
    document.getElementById(id).addEventListener('input', () => {
        document.getElementById('ecartVal').textContent      = document.getElementById('cfgEcart').value;
        document.getElementById('standVal').textContent      = document.getElementById('cfgStand').value;
        document.getElementById('alleeVal').textContent      = document.getElementById('cfgAllee').value;
        document.getElementById('degagementVal').textContent = document.getElementById('cfgDegagement').value;
        toutRedessiner();
    }));
document.getElementById('cfgDisposition').addEventListener('change', toutRedessiner);

function sauverConfig() {
    const c = cfg();
    fetch('{{ route('marche.zones.config', array_merge(['zone' => $zone->id], $mairieParam)) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            marche_type:  document.getElementById('cfgType').value || null,
            longueur_m:   c.longueur,
            largeur_m:    c.largeur,
            disposition:  c.disposition,
            ecart:        c.ecart,
            taille_stand: c.stand,
            allee:        c.allee,
            degagement:   c.degagement,
            obstacles:    obstacles,
        }),
    }).then(r => {
        const msg = document.getElementById('cfgMsg');
        if (r.ok) { msg.classList.remove('d-none'); setTimeout(() => msg.classList.add('d-none'), 2500); }
        else alert('{{ __('Erreur lors de la sauvegarde.') }}');
    });
}

// Démarrage
calculerStands();
dessiner2D();
init3D();
</script>
@endsection
