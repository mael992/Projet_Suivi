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

    /** Le dernier message vient-il de la personne extérieure (mairie doit répondre) ? */
    public function attendReponseMairie(): bool
    {
        $dernier = $this->messages()->latest('created_at')->first();

        return $dernier !== null && $dernier->user_id === null;
    }

    /** Nombre de tickets externes en attente de réponse, visibles par l'utilisateur (badge). */
    public static function enAttentePour(User $user): int
    {
        $requete = static::where('type', 'externe');

        if (! $user->isAdmin()) {
            $requete->where('mairie_id', $user->mairie_id);

            if (! $user->estDirection()) {
                $cats        = $user->categoriesCommunication();
                $numServices = array_values(array_filter($cats, fn ($c) => $c !== 'inconnu'));
                $inconnu     = in_array('inconnu', $cats, true);

                $requete->where(function ($q) use ($numServices, $inconnu) {
                    if ($numServices) {
                        $q->whereIn('service', $numServices);
                    }
                    if ($inconnu) {
                        $q->orWhereNull('service');
                    }
                    if (! $numServices && ! $inconnu) {
                        $q->whereRaw('1 = 0');
                    }
                });
            }
        }

        return $requete->with('messages')->get()->filter->attendReponseMairie()->count();
    }

    /** Référence séquentielle par mairie (à partir de 1). */
    public static function genererReference(int $mairieId): string
    {
        return (string) (static::where('mairie_id', $mairieId)->count() + 1);
    }
}
