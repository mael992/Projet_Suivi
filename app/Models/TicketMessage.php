<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'corps'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** true = message de la personne extérieure (pas d'agent de mairie). */
    public function estExterieur(): bool
    {
        return $this->user_id === null;
    }

    /** Initiales pour l'avatar (personne extérieure ou agent). */
    public function getInitialesAttribute(): string
    {
        if ($this->auteur) {
            return strtoupper(mb_substr($this->auteur->prenom ?? '', 0, 1) . mb_substr($this->auteur->nom ?? '', 0, 1));
        }

        return strtoupper(mb_substr($this->ticket->prenom ?? '', 0, 1) . mb_substr($this->ticket->nom ?? '', 0, 1));
    }
}
