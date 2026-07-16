<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rappel extends Model
{
    protected $table = 'rappels';

    protected $fillable = ['user_id', 'date_rappel', 'texte', 'fichier', 'envoye'];

    protected function casts(): array
    {
        return [
            'date_rappel' => 'date',
            'envoye'      => 'boolean',
        ];
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
