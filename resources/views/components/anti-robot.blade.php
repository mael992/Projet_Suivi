{{-- Champs anti-robots des formulaires publics (voir ProtectionFormulairePublic) :
     un champ piège invisible, et l'heure d'affichage chiffrée. --}}
<div style="position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
    <label for="{{ $id = 'controle_' . \Illuminate\Support\Str::random(6) }}">{{ __('Laissez ce champ vide') }}</label>
    <input type="text" id="{{ $id }}" name="{{ \App\Http\Middleware\ProtectionFormulairePublic::CHAMP_PIEGE }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Http\Middleware\ProtectionFormulairePublic::CHAMP_HEURE }}" value="{{ encrypt(now()->timestamp) }}">
