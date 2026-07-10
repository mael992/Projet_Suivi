<?php

namespace App\Models;

use App\Support\Referentiel;
use Illuminate\Database\Eloquent\Model;

class Standard extends Model
{
    protected $fillable = [
        'mairie_id',
        'service',
        'telephone_indicatif',
        'telephone',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'service' => 'integer',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function getServiceLabelAttribute(): string
    {
        return Referentiel::serviceLabel($this->service);
    }

    public function getTelephoneCompletAttribute(): string
    {
        return '(' . $this->telephone_indicatif . ') ' . $this->telephone;
    }
}
