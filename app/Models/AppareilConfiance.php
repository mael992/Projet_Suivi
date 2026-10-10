<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Appareil de confiance d'un utilisateur (double authentification) : la
 * connexion depuis ce navigateur, à cette adresse IP, ne redemande pas de
 * code pendant 30 jours.
 */
class AppareilConfiance extends Model
{
    protected $table = 'appareils_confiance';

    protected $fillable = ['user_id', 'jeton_hash', 'ip', 'navigateur_hash', 'expire_at'];

    protected $hidden = ['jeton_hash'];

    protected function casts(): array
    {
        return [
            'expire_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
