<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'user_id', 'dossier', 'titre', 'contenu', 'image',
        'notifier', 'date_notification', 'notifiee',
    ];

    protected function casts(): array
    {
        return [
            'notifier'          => 'boolean',
            'notifiee'          => 'boolean',
            'date_notification' => 'date',
        ];
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
