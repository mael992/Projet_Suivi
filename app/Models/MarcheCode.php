<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Code d'accès au plan 2D remis aux exposants : valable jusqu'au jour du
 * marché inclus, inutilisable ensuite.
 */
class MarcheCode extends Model
{
    protected $fillable = ['marche_zone_id', 'code', 'valable_le'];

    protected function casts(): array
    {
        return ['valable_le' => 'date'];
    }

    public function zone()
    {
        return $this->belongsTo(MarcheZone::class, 'marche_zone_id');
    }

    /** Le code est-il encore utilisable aujourd'hui ? (date du marché incluse) */
    public function estValide(): bool
    {
        return $this->valable_le->copy()->endOfDay()->isFuture();
    }

    /** Génère un code court, lisible et unique (sans caractères ambigus). */
    public static function genererCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
            $code = str_replace(['0', 'O', 'I', '1'], ['2', 'P', 'J', '3'], $code);
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
