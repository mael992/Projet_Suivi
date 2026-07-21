<?php

namespace App\Models;

use App\Support\Referentiel;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'mairie_id', 'reference', 'type', 'service',
        'nom', 'prenom', 'telephone_indicatif', 'telephone', 'email',
        'sujet', 'photos', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'service' => 'integer',
            'photos'  => 'array',
        ];
    }

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function getServiceLabelAttribute(): string
    {
        return $this->service ? Referentiel::serviceLabel($this->service) : 'Je ne sais pas';
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->nom . ' ' . $this->prenom);
    }

    public function getTelephoneCompletAttribute(): string
    {
        return '(' . $this->telephone_indicatif . ') ' . $this->telephone;
    }

    /** Référence séquentielle par mairie (à partir de 1). */
    public static function genererReference(int $mairieId): string
    {
        return (string) (static::where('mairie_id', $mairieId)->count() + 1);
    }
}
