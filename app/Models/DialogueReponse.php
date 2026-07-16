<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialogueReponse extends Model
{
    protected $fillable = ['dialogue_question_id', 'user_id', 'texte'];

    public function question()
    {
        return $this->belongsTo(DialogueQuestion::class, 'dialogue_question_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
