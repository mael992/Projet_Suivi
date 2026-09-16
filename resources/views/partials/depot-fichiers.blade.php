{{--
    Zone de dépôt de documents : glisser-déposer OU « parcourir ».
    Usage : @include('partials.depot-fichiers', ['champ' => 'fichiers'])

    Les fichiers déposés sont reversés dans le vrai <input type="file"> via
    DataTransfer : le formulaire s'envoie donc normalement, sans JavaScript
    côté serveur, et « parcourir » continue de marcher si le glisser-déposer
    n'est pas pris en charge.
--}}
@php
    use App\Support\DocumentsTache;
    $idZone = 'depot_' . $champ . '_' . uniqid();
    $accept = implode(',', array_map(fn ($e) => '.' . $e, DocumentsTache::EXTENSIONS));
@endphp

<div class="depot-fichiers border rounded p-3 text-center" id="{{ $idZone }}"
     style="border-style:dashed !important;border-width:2px !important;cursor:pointer;background:#fafaf7;transition:background .15s;">
    <div style="font-size:28px;line-height:1;">📎</div>
    <div class="fw-semibold mt-1" style="font-size:14px;">{{ __('Glissez vos documents ici') }}</div>
    <div class="text-muted" style="font-size:12px;">
        {{ __('ou cliquez pour parcourir') }} —
        PDF, Word, Excel, {{ __('images') }} ·
        {{ DocumentsTache::MAX_FICHIERS }} {{ __('fichiers max') }},
        {{ intdiv(DocumentsTache::MAX_KO, 1024) }} {{ __('Mo chacun') }},
        {{ intdiv(DocumentsTache::MAX_TOTAL_KO, 1024) }} {{ __('Mo au total') }}
    </div>
    <input type="file" name="{{ $champ }}[]" multiple accept="{{ $accept }}" class="d-none">
    <ul class="list-unstyled text-start mb-0 mt-2 liste-depot" style="font-size:13px;"></ul>
</div>
@error($champ)<div class="text-danger" style="font-size:13px;">{{ $message }}</div>@enderror
@error($champ . '.*')<div class="text-danger" style="font-size:13px;">{{ $message }}</div>@enderror

<script>
(function () {
    const zone  = document.getElementById(@json($idZone));
    const input = zone.querySelector('input[type=file]');
    const liste = zone.querySelector('.liste-depot');
    const MAX   = {{ DocumentsTache::MAX_FICHIERS }};
    const MAXO  = {{ DocumentsTache::MAX_KO }} * 1024;
    // Total d'un envoi : au-delà, le serveur rejette le formulaire entier
    const TOTAL = {{ DocumentsTache::MAX_TOTAL_KO }} * 1024;
    const EXT   = @json(DocumentsTache::EXTENSIONS);

    // Liste de travail : on cumule les dépôts successifs au lieu de les écraser
    let fichiers = [];

    function taille(o) {
        return o >= 1048576 ? (o / 1048576).toFixed(1).replace('.', ',') + ' Mo' : Math.max(1, Math.round(o / 1024)) + ' Ko';
    }

    function synchroniser() {
        const dt = new DataTransfer();
        fichiers.forEach(f => dt.items.add(f));
        input.files = dt.files;

        liste.innerHTML = '';
        fichiers.forEach((f, i) => {
            const li = document.createElement('li');
            li.className = 'd-flex justify-content-between align-items-center border-top pt-1 mt-1';
            li.innerHTML = '<span></span><button type="button" class="btn btn-sm btn-link text-danger p-0">✕</button>';
            li.firstChild.textContent = '📄 ' + f.name + ' (' + taille(f.size) + ')';
            li.lastChild.addEventListener('click', ev => { ev.stopPropagation(); fichiers.splice(i, 1); synchroniser(); });
            liste.appendChild(li);
        });
    }

    function ajouter(nouveaux) {
        const refus = [];
        Array.from(nouveaux).forEach(f => {
            const ext = f.name.split('.').pop().toLowerCase();
            if (! EXT.includes(ext))          return refus.push(f.name + ' : ' + @json(__('format non accepté')));
            if (f.size > MAXO)                return refus.push(f.name + ' : ' + @json(__('trop volumineux')));
            if (fichiers.length >= MAX)       return refus.push(f.name + ' : ' + @json(__('nombre maximum atteint')));
            const cumul = fichiers.reduce((t, x) => t + x.size, 0);
            if (cumul + f.size > TOTAL)       return refus.push(f.name + ' : ' + @json(__('taille totale dépassée')));
            fichiers.push(f);
        });
        synchroniser();
        if (refus.length) alert(refus.join('\n'));
    }

    zone.addEventListener('click', ev => {
        // Le clic relancé sur l'input remonte jusqu'ici : sans ce garde-fou,
        // la zone rouvrirait la fenêtre de sélection en boucle
        if (ev.target === input || ev.target.closest('button')) return;
        input.click();
    });
    input.addEventListener('change', () => {
        // « Parcourir » remplace la sélection du navigateur : on la réintègre à la liste
        const choisis = Array.from(input.files).filter(f => ! fichiers.includes(f));
        ajouter(choisis);
    });

    ['dragenter', 'dragover'].forEach(t => zone.addEventListener(t, ev => {
        ev.preventDefault();
        zone.style.background = '#eef5fd';
    }));
    ['dragleave', 'drop'].forEach(t => zone.addEventListener(t, ev => {
        ev.preventDefault();
        zone.style.background = '#fafaf7';
    }));
    zone.addEventListener('drop', ev => ajouter(ev.dataTransfer.files));
})();
</script>
