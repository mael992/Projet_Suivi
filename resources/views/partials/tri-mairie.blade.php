{{--
    Sélecteur de tri par mairie (admin) — réutilisable dans toutes les applications.
    Variables attendues : $mairies (collection), $filtre (id ou 'tout'), $route (nom de route).
--}}
<form method="GET" action="{{ route($route) }}" class="d-flex align-items-center gap-2">
    <label style="font-size:13px;" class="text-muted mb-0 fw-semibold">🏛️ {{ __('Trier par mairie') }} :</label>
    <select name="mairie" class="form-select form-select-sm" style="width:auto;min-width:180px;" onchange="this.form.submit()">
        <option value="tout" @selected($filtre === 'tout')>{{ __('Tout') }}</option>
        @foreach($mairies as $m)
            <option value="{{ $m->id }}" @selected((string) $filtre === (string) $m->id)>{{ $m->nom }}</option>
        @endforeach
    </select>
</form>
