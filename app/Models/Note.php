<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = ['user_id', 'dossier', 'titre', 'contenu', 'image'];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
