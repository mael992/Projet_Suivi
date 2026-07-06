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
}
