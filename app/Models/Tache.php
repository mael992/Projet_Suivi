<?php

namespace App\Models;

use App\Support\Referentiel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tache extends Model
{
    protected $table = 'taches';

    protected $fillable = [
        'mairie_id',
        'reference',
        'service',
        'user_id',
        'created_by',
        'statut',
        'photo_avant',
        'photo_apres',
        'description_instruction',
        'description_cloture',
        'date_butoir',
        'date_cloture',
    ];

    protected function casts(): array
    {
        return [
            'service'      => 'integer',
            'date_butoir'  => 'date',
            'date_cloture' => 'datetime',
        ];
    }

    // ── Relations ────────────────────────────────────────────────

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    /** Employé chargé de réaliser la tâche */
    public function assigne()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes de visibilité ─────────────────────────────────────

    /**
     * Restreint les tâches visibles selon le rôle :
     *  - admin                        → tout (toutes mairies)
     *  - maire / cabinet du maire /
     *    direction générale services  → toutes les tâches de sa mairie
     *  - responsable / sous-resp.     → tâches de son service
     *  - secrétaire / employé         → ses propres tâches
     */
    public function scopeVisiblesPar(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $query->where('mairie_id', $user->mairie_id);

        if ($user->voitTousLesServices()) {
            return $query;
        }

        if ($user->voitSonService()) {
            return $query->where('service', $user->service);
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('created_by', $user->id);
        });
    }

    // ── Attributs pratiques ──────────────────────────────────────

    public function getServiceLabelAttribute(): string
    {
        return Referentiel::serviceLabel($this->service);
    }

    public function getStatutLabelAttribute(): string
    {
        return Referentiel::statutLabel($this->statut);
    }

    public function estFaite(): bool
    {
        return $this->statut === Referentiel::STATUT_FAIT;
    }

    /** Photo après obligatoire dès qu'une photo avant existe */
    public function photoApresObligatoire(): bool
    {
        return $this->photo_avant !== null;
    }

    // ── Génération de référence ──────────────────────────────────

    /**
     * Référence "service-numéro" par mairie et par service, à partir de 0.
     * Ex : première tâche du Service Technique (12) → 12-0, puis 12-1…
     */
    public static function genererReference(int $mairieId, int $service): string
    {
        $dernier = static::where('mairie_id', $mairieId)
            ->where('service', $service)
            ->where('reference', 'like', $service . '-%')
            ->get()
            ->map(fn ($t) => (int) substr($t->reference, strlen((string) $service) + 1))
            ->max();

        $numero = $dernier === null ? 0 : $dernier + 1;

        return $service . '-' . $numero;
    }
}
