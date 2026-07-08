<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarchePlan extends Model
{
    protected $table = 'marche_plans';

    protected $fillable = [
        'mairie_id',
        'nom',
        'date',
        'notes',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function axes()
    {
        return $this->hasMany(MarcheAxe::class);
    }

    /** Emplacements posés directement sur le fond de plan (2D) */
    public function emplacements()
    {
        return $this->hasMany(MarcheEmplacement::class, 'marche_plan_id');
    }

    /** URL du fond de plan (image uploadée, sinon exemple fourni) */
    public function fondUrl(): string
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : asset('images/fond-plan-exemple.png');
    }
}
