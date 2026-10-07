<?php

namespace Tests;

use App\Http\Middleware\ProtectionFormulairePublic;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Champs anti-robots d'un formulaire public affiché depuis une minute
     * (voir ProtectionFormulairePublic).
     */
    protected function jetonFormulaire(): array
    {
        return [ProtectionFormulairePublic::CHAMP_HEURE => encrypt(now()->subMinute()->timestamp)];
    }
}
