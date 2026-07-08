<?php

namespace App\Models;

use App\Support\Referentiel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'prenom',
        'nom',
        'username',
        'email',
        'password',
        'temp_password',
        'temp_password_expires_at',
        'must_change_password',
        'role',
        'mairie_id',
        'service',
        'grade',
        'reference',
        'telephone_indicatif',
        'telephone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'                 => 'hashed',
            'temp_password_expires_at' => 'datetime',
            'must_change_password'     => 'boolean',
            'service'                  => 'integer',
            'grade'                    => 'integer',
        ];
    }

    // ── Relations ────────────────────────────────────────────────

    public function mairie()
    {
        return $this->belongsTo(Mairie::class);
    }

    public function taches()
    {
        return $this->hasMany(Tache::class);
    }

    // ── Rôles & permissions ──────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Grades 1 à 4 : peuvent créer / modifier / supprimer des tâches */
    public function peutGererTaches(): bool
    {
        return $this->isAdmin()
            || in_array($this->grade, Referentiel::GRADES_CREATION_TACHE, true);
    }

    /** Maire, Responsable ou Sous-Responsable : accès à la Gestion de la Mairie */
    public function peutGererMairie(): bool
    {
        return in_array($this->grade, [
            Referentiel::GRADE_MAIRE,
            Referentiel::GRADE_RESPONSABLE,
            Referentiel::GRADE_SOUS_RESP,
        ], true);
    }

    /** Cabinet du maire / DGS / Maire : voient toutes les tâches de la mairie */
    public function voitTousLesServices(): bool
    {
        return $this->isAdmin()
            || $this->grade === Referentiel::GRADE_MAIRE
            || in_array($this->service, Referentiel::SERVICES_VUE_GLOBALE, true);
    }

    /** Responsable / Sous-Responsable : voient toutes les tâches de leur service */
    public function voitSonService(): bool
    {
        return in_array($this->grade, [Referentiel::GRADE_RESPONSABLE, Referentiel::GRADE_SOUS_RESP], true);
    }

    // ── Attributs pratiques ──────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? '')) ?: $this->username;
    }

    public function getServiceLabelAttribute(): string
    {
        return Referentiel::serviceLabel($this->service);
    }

    public function getGradeLabelAttribute(): string
    {
        return Referentiel::gradeLabel($this->grade);
    }

    public function getTelephoneCompletAttribute(): string
    {
        return $this->telephone
            ? '(' . $this->telephone_indicatif . ') ' . $this->telephone
            : '—';
    }

    /** Le mot de passe provisoire a-t-il expiré ? */
    public function tempPasswordExpired(): bool
    {
        return $this->temp_password_expires_at !== null
            && $this->temp_password_expires_at->isPast();
    }

    // ── Génération automatique ───────────────────────────────────

    /**
     * Identifiant unique prenom.nom — si déjà pris (même dans une autre
     * mairie), on ajoute +1 : prenom.nom1, prenom.nom2, …
     */
    public static function genererUsername(string $prenom, string $nom, ?int $ignoreId = null): string
    {
        $base = Str::slug($prenom, '') . '.' . Str::slug($nom, '');
        $base = Str::lower($base);

        $username = $base;
        $i = 0;
        while (static::where('username', $username)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $i++;
            $username = $base . $i;
        }

        return $username;
    }

    /**
     * Référence automatique "service-numéro" (numérotation par mairie
     * et par service, en partant de 0).
     */
    public static function genererReference(int $mairieId, int $service): string
    {
        $count = static::where('mairie_id', $mairieId)
            ->where('service', $service)
            ->count();

        return $service . '-' . $count;
    }
}
