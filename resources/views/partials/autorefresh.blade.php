{{--
    Auto-rafraîchissement (comme PlanEx) : recharge la zone $selector
    toutes les 30 s en arrière-plan et ne remplace le contenu que s'il
    a changé (comparaison), avec un léger flash doré.
    Usage : @include('partials.autorefresh', ['selector' => '#zoneTaches'])
--}}
<script>
(function () {
    const SELECTEUR  = @json($selector);
    const INTERVALLE = {{ $intervalle ?? 30000 }};

    setInterval(async () => {
        if (document.hidden) return; // onglet en arrière-plan : on ne recharge pas

        try {
            const rep = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!rep.ok) return;

            const doc    = new DOMParser().parseFromString(await rep.text(), 'text/html');
            const neuf   = doc.querySelector(SELECTEUR);
            const actuel = document.querySelector(SELECTEUR);

            if (neuf && actuel && neuf.innerHTML !== actuel.innerHTML) {
                actuel.innerHTML = neuf.innerHTML;
                actuel.classList.remove('zone-rafraichie');
                void actuel.offsetWidth; // relance l'animation
                actuel.classList.add('zone-rafraichie');
            }
        } catch (e) {
            // Hors ligne ou erreur réseau : on réessaiera au prochain tick
        }
    }, INTERVALLE);
})();
</script>
