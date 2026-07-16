<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Question de la Boîte de dialogue (entraide entre mairies).
 * Les questions sont partagées entre toutes les mairies.
 */
class DialogueQuestion extends Model
{
    public const SECTIONS = [
        'tableau-suivis' => 'Tableau des suivis',
        'marche'         => 'Marché',
        'fiche-contact'  => 'Fiche Contact',
        'administration' => 'Paramètres Administration',
    ];

    protected $fillable = ['user_id', 'section', 'texte'];

    public function auteur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reponses()
    {
        return $this->hasMany(DialogueReponse::class)->orderBy('created_at');
    }
}
