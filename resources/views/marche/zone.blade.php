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
                    <div class="btn-group w-100 mb-3" role="group">
                        @foreach(['rangee' => 'Rangée simple', 'double' => 'Double rangée', 'peripherie' => 'En périphérie'] as $cle => $label)
                            <input type="radio" class="btn-check" name="cfgDisposition" id="disp_{{ $cle }}" value="{{ $cle }}"
                                   @checked(($config['disposition'] ?? 'double') === $cle) {{ $peutEditer ? '' : 'disabled' }}>
                            <label class="btn btn-outline-primary btn-sm" for="disp_{{ $cle }}" style="font-size:11px;">{{ __($label) }}</label>
                        @endforeach
                    </div>

                    <label class="form-label mb-1" style="font-size:12px;">
                        {{ __('Écart entre exposants') }} : <strong><span id="ecartVal">{{ $config['ecart'] ?? 1 }}</span> m</strong>
                    </label>
                    <input type="range" id="cfgEcart" class="form-range mb-2" min="0" max="10" step="0.5"
                           value="{{ $config['ecart'] ?? 1 }}" {{ $peutEditer ? '' : 'disabled' }}>

                    <label class="form-label mb-1" style="font-size:12px;">
                        {{ __('Taille d\'un stand') }} : <strong><span id="standVal">{{ $config['taille_stand'] ?? 3 }}</span> m</strong>
                    </label>
                    <input type="range" id="cfgStand" class="form-range mb-3" min="2" max="8" step="0.5"
                           value="{{ $config['taille_stand'] ?? 3 }}" {{ $peutEditer ? '' : 'disabled' }}>

                    @if($peutEditer)
                    <label class="form-label fw-semibold mb-1" style="font-size:13px;">{{ __('Ajouter un obstacle') }}</label>
                    <div class="d-flex gap-1 flex-wrap mb-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="ajouterObstacle('arbre')">🌳 {{ __('Arbre') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-info"    onclick="ajouterObstacle('fontaine')">⛲ {{ __('Fontaine') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="ajouterObstacle('poteau')">⚡ {{ __('Poteau élec.') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-danger"  onclick="ajouterObstacle('temporaire')">🚧 {{ __('Temporaire') }}</button>
                    </div>
                    <p class="text-muted mb-3" style="font-size:11px;">
                        {{ __('Glissez les obstacles sur le plan 2D — les stands qui les chevauchent sont retirés automatiquement.') }}
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
                <li class="nav-item"><button class="nav-link active" data-vue="3d" onclick="montrerVue('3d', this)">🧊 {{ __('Vue 3D') }}</button></li>
                <li class="nav-item"><button class="nav-link" data-vue="2d" onclick="montrerVue('2d', this)">🗺️ {{ __('Plan 2D (obstacles)') }}</button></li>
            </ul>

            <div class="card shadow-sm">
                <div class="card-body p-2">
                    <div id="vue3d" style="width:100%;height:52vh;min-height:320px;border-radius:8px;overflow:hidden;background:#bfd8ee;"></div>
                    <div id="vue2d" class="d-none" style="width:100%;">
                        <svg id="svg2d" style="width:100%;height:52vh;min-height:320px;display:block;border-radius:8px;background:#e8e4da;touch-action:none;"></svg>
                    </div>
                </div>
            </div>
            <p class="text-muted mt-2 mb-0" style="font-size:11px;">
                🖱 / 👆 {{ __('Vue 3D : faites tourner (glisser), zoomez (molette / pincer). Plan 2D : glissez les obstacles, double-clic pour en supprimer un.') }}
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
let stands    = [];                                                // calculés

const TAILLES_OBSTACLES = { arbre: 2.5, fontaine: 4, poteau: 0.8, temporaire: 2 };
const EMOJIS            = { arbre: '🌳', fontaine: '⛲', poteau: '⚡', temporaire: '🚧' };

function cfg() {
    return {
        longueur:    parseFloat(document.getElementById('cfgLongueur').value) || 50,
        largeur:     parseFloat(document.getElementById('cfgLargeur').value)  || 30,
        disposition: document.querySelector('input[name=cfgDisposition]:checked')?.value || 'double',
        ecart:       parseFloat(document.getElementById('cfgEcart').value),
        stand:       parseFloat(document.getElementById('cfgStand').value),
    };
}

// ── Calcul des stands (disposition + écart + obstacles) ──────────
function calculerStands() {
    const c = cfg();
    const pas = c.stand + c.ecart;
    const profondeur = 2.5; // profondeur d'un stand (m)
    stands = [];

    function rangee(y, orientation) {
        for (let x = 1 + c.stand / 2; x + c.stand / 2 <= c.longueur - 1; x += pas) {
            stands.push({ x, y, w: c.stand, d: profondeur, rot: orientation });
        }
    }
    function colonne(x, orientation) {
        for (let y = 1 + c.stand / 2; y + c.stand / 2 <= c.largeur - 1; y += pas) {
            stands.push({ x, y, w: c.stand, d: profondeur, rot: orientation });
        }
    }

    if (c.disposition === 'rangee') {
        rangee(c.largeur / 2, 0);
    } else if (c.disposition === 'double') {
        const allee = Math.max(4, c.largeur * 0.25);
        rangee(c.largeur / 2 - allee / 2 - profondeur / 2, 0);
        rangee(c.largeur / 2 + allee / 2 + profondeur / 2, 180);
    } else { // peripherie
        rangee(1 + profondeur / 2, 180);
        rangee(c.largeur - 1 - profondeur / 2, 0);
        if (c.largeur > 14) {
            colonne(1 + profondeur / 2, 90);
            colonne(c.longueur - 1 - profondeur / 2, 270);
        }
    }

    // Retirer les stands en collision avec un obstacle
    stands = stands.filter(s => !obstacles.some(o => {
        const r = (TAILLES_OBSTACLES[o.type] || 2) / 2 + Math.max(s.w, s.d) / 2;
        return Math.hypot(o.x - s.x, o.y - s.y) < r;
    }));

    document.getElementById('statStands').textContent = stands.length;
    document.getElementById('statMetres').textContent = Math.round(stands.length * c.stand);
}

// ── Vue 2D (SVG) : obstacles glissables ──────────────────────────
const svg = document.getElementById('svg2d');

function dessiner2D() {
    const c = cfg();
    svg.setAttribute('viewBox', `0 0 ${c.longueur} ${c.largeur}`);
    svg.innerHTML = '';

    const fond = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    fond.setAttribute('width', c.longueur); fond.setAttribute('height', c.largeur);
    fond.setAttribute('fill', '#d7d2c4');
    svg.appendChild(fond);

    stands.forEach(s => {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        const horiz = s.rot % 180 === 0;
        g.setAttribute('x', s.x - (horiz ? s.w : s.d) / 2);
        g.setAttribute('y', s.y - (horiz ? s.d : s.w) / 2);
        g.setAttribute('width',  horiz ? s.w : s.d);
        g.setAttribute('height', horiz ? s.d : s.w);
        g.setAttribute('fill', '#b08d4a'); g.setAttribute('stroke', '#12294a'); g.setAttribute('stroke-width', '0.12');
        g.setAttribute('rx', '0.3');
        svg.appendChild(g);
    });

    obstacles.forEach((o, i) => {
        const t = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        t.setAttribute('x', o.x); t.setAttribute('y', o.y);
        t.setAttribute('font-size', TAILLES_OBSTACLES[o.type] || 2);
        t.setAttribute('text-anchor', 'middle'); t.setAttribute('dominant-baseline', 'central');
        t.style.cursor = PEUT_EDITER ? 'grab' : 'default';
        t.style.touchAction = 'none';
        t.textContent = EMOJIS[o.type];
        if (PEUT_EDITER) {
            t.addEventListener('pointerdown', e => { e.preventDefault(); t.setPointerCapture(e.pointerId); t.dataset.drag = '1'; });
            t.addEventListener('pointermove', e => {
                if (t.dataset.drag !== '1') return;
                const pt = coordSvg(e);
                o.x = Math.max(0.5, Math.min(cfg().longueur - 0.5, pt.x));
                o.y = Math.max(0.5, Math.min(cfg().largeur  - 0.5, pt.y));
                t.setAttribute('x', o.x); t.setAttribute('y', o.y);
            });
            ['pointerup', 'pointercancel'].forEach(ev => t.addEventListener(ev, () => { t.dataset.drag = '0'; toutRedessiner(); }));
            t.addEventListener('dblclick', () => { obstacles.splice(i, 1); toutRedessiner(); });
        }
        svg.appendChild(t);
    });
}

function coordSvg(e) {
    const pt = svg.createSVGPoint();
    pt.x = e.clientX; pt.y = e.clientY;
    return pt.matrixTransform(svg.getScreenCTM().inverse());
}

function ajouterObstacle(type) {
    const c = cfg();
    obstacles.push({ type, x: c.longueur / 2, y: c.largeur / 2 });
    montrerVue('2d', document.querySelector('[data-vue="2d"]'));
    toutRedessiner();
}

// ── Vue 3D (three.js) ────────────────────────────────────────────
let THREE3 = null, scene, camera, renderer, controls, groupeMarche;

async function init3D() {
    const conteneur = document.getElementById('vue3d');
    try {
        const THREE = await import('three');
        const { OrbitControls } = await import('three/addons/controls/OrbitControls.js');
        THREE3 = THREE;

        scene = new THREE.Scene();
        scene.background = new THREE.Color(0xbfd8ee);

        camera = new THREE.PerspectiveCamera(55, conteneur.clientWidth / conteneur.clientHeight, 0.1, 2000);

        renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setSize(conteneur.clientWidth, conteneur.clientHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        conteneur.appendChild(renderer.domElement);

        controls = new OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.maxPolarAngle = Math.PI / 2.05;

        const soleil = new THREE.DirectionalLight(0xffffff, 2.2);
        soleil.position.set(40, 60, 20);
        soleil.castShadow = true;
        soleil.shadow.camera.left = -80; soleil.shadow.camera.right = 80;
        soleil.shadow.camera.top = 80;   soleil.shadow.camera.bottom = -80;
        scene.add(soleil);
        scene.add(new THREE.AmbientLight(0xffffff, 0.75));

        groupeMarche = new THREE.Group();
        scene.add(groupeMarche);

        construire3D();

        window.addEventListener('resize', () => {
            camera.aspect = conteneur.clientWidth / conteneur.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(conteneur.clientWidth, conteneur.clientHeight);
        });

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

function construire3D() {
    if (!THREE3) return;
    const THREE = THREE3;
    const c = cfg();

    groupeMarche.clear();

    // Sol
    const sol = new THREE.Mesh(
        new THREE.BoxGeometry(c.longueur, 0.2, c.largeur),
        new THREE.MeshStandardMaterial({ color: 0xcfc9b8 })
    );
    sol.position.set(c.longueur / 2, -0.1, c.largeur / 2);
    sol.receiveShadow = true;
    groupeMarche.add(sol);

    // Herbe autour
    const herbe = new THREE.Mesh(
        new THREE.BoxGeometry(c.longueur * 2.2, 0.1, c.largeur * 2.2),
        new THREE.MeshStandardMaterial({ color: 0x9dc183 })
    );
    herbe.position.set(c.longueur / 2, -0.21, c.largeur / 2);
    herbe.receiveShadow = true;
    groupeMarche.add(herbe);

    // Stands : comptoir + 4 pieds + toit rayé
    const couleursToit = [0xb03a3a, 0x2e5d9e, 0x2e8b57, 0xb08d4a];
    stands.forEach((s, i) => {
        const stand = new THREE.Group();

        const comptoir = new THREE.Mesh(
            new THREE.BoxGeometry(s.w, 0.9, s.d),
            new THREE.MeshStandardMaterial({ color: 0x8a6a3b })
        );
        comptoir.position.y = 0.45;
        comptoir.castShadow = true;
        stand.add(comptoir);

        for (const [px, pz] of [[-1, -1], [1, -1], [-1, 1], [1, 1]]) {
            const pied = new THREE.Mesh(
                new THREE.CylinderGeometry(0.05, 0.05, 2.4),
                new THREE.MeshStandardMaterial({ color: 0x555555 })
            );
            pied.position.set(px * (s.w / 2 - 0.1), 1.2, pz * (s.d / 2 - 0.1));
            stand.add(pied);
        }

        const toit = new THREE.Mesh(
            new THREE.ConeGeometry(Math.max(s.w, s.d) * 0.72, 0.8, 4),
            new THREE.MeshStandardMaterial({ color: couleursToit[i % couleursToit.length] })
        );
        toit.rotation.y = Math.PI / 4;
        toit.scale.set(s.w / Math.max(s.w, s.d), 1, s.d / Math.max(s.w, s.d));
        toit.position.y = 2.8;
        toit.castShadow = true;
        stand.add(toit);

        stand.position.set(s.x, 0, s.y);
        stand.rotation.y = -(s.rot * Math.PI) / 180;
        groupeMarche.add(stand);
    });

    // Obstacles
    obstacles.forEach(o => {
        const g = new THREE.Group();
        if (o.type === 'arbre') {
            const tronc = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.28, 2.2), new THREE.MeshStandardMaterial({ color: 0x6b4a2b }));
            tronc.position.y = 1.1; tronc.castShadow = true; g.add(tronc);
            const feuillage = new THREE.Mesh(new THREE.SphereGeometry(1.5, 12, 10), new THREE.MeshStandardMaterial({ color: 0x3f7d3a }));
            feuillage.position.y = 3; feuillage.castShadow = true; g.add(feuillage);
        } else if (o.type === 'fontaine') {
            const bassin = new THREE.Mesh(new THREE.CylinderGeometry(2, 2, 0.6, 20), new THREE.MeshStandardMaterial({ color: 0x9aa5ad }));
            bassin.position.y = 0.3; bassin.castShadow = true; g.add(bassin);
            const eau = new THREE.Mesh(new THREE.CylinderGeometry(1.8, 1.8, 0.15, 20), new THREE.MeshStandardMaterial({ color: 0x3f8fbf }));
            eau.position.y = 0.6; g.add(eau);
            const jet = new THREE.Mesh(new THREE.CylinderGeometry(0.15, 0.3, 1.6, 10), new THREE.MeshStandardMaterial({ color: 0x9aa5ad }));
            jet.position.y = 1.2; g.add(jet);
        } else if (o.type === 'poteau') {
            const poteau = new THREE.Mesh(new THREE.CylinderGeometry(0.1, 0.1, 6), new THREE.MeshStandardMaterial({ color: 0x444444 }));
            poteau.position.y = 3; poteau.castShadow = true; g.add(poteau);
            const lampe = new THREE.Mesh(new THREE.SphereGeometry(0.25), new THREE.MeshStandardMaterial({ color: 0xffe08a, emissive: 0x996f00 }));
            lampe.position.y = 6; g.add(lampe);
        } else {
            const bloc = new THREE.Mesh(new THREE.BoxGeometry(2, 1, 1), new THREE.MeshStandardMaterial({ color: 0xd9534f }));
            bloc.position.y = 0.5; bloc.castShadow = true; g.add(bloc);
            for (const dx of [-0.8, 0.8]) {
                const bande = new THREE.Mesh(new THREE.BoxGeometry(0.15, 1.02, 1.02), new THREE.MeshStandardMaterial({ color: 0xffffff }));
                bande.position.set(dx, 0.5, 0); g.add(bande);
            }
        }
        g.position.set(o.x, 0, o.y);
        groupeMarche.add(g);
    });

    // Caméra recadrée sur la zone
    const d = Math.max(c.longueur, c.largeur);
    camera.position.set(c.longueur / 2, d * 0.75, c.largeur / 2 + d * 0.95);
    controls.target.set(c.longueur / 2, 0, c.largeur / 2);
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
    document.getElementById('vue3d').parentElement.querySelector('#vue3d').classList.toggle('d-none', vue !== '3d');
    document.getElementById('vue2d').classList.toggle('d-none', vue !== '2d');
}

['cfgLongueur', 'cfgLargeur', 'cfgEcart', 'cfgStand'].forEach(id =>
    document.getElementById(id).addEventListener('input', () => {
        document.getElementById('ecartVal').textContent = document.getElementById('cfgEcart').value;
        document.getElementById('standVal').textContent = document.getElementById('cfgStand').value;
        toutRedessiner();
    }));
document.querySelectorAll('input[name=cfgDisposition]').forEach(r => r.addEventListener('change', toutRedessiner));

function sauverConfig() {
    fetch('{{ route('marche.zones.config', array_merge(['zone' => $zone->id], $mairieParam)) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            marche_type:  document.getElementById('cfgType').value || null,
            longueur_m:   cfg().longueur,
            largeur_m:    cfg().largeur,
            disposition:  cfg().disposition,
            ecart:        cfg().ecart,
            taille_stand: cfg().stand,
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
