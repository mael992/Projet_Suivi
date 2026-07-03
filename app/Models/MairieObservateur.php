<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MairieObservateur extends Model
{
    protected $table = 'mairie_observateurs';

    protected $fillable = [
        'mairie_id',
        'nom',
        'email',
    ];

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }
}
