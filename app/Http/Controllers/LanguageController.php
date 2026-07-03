<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    // Langues : français / anglais uniquement
    protected array $supported = ['fr', 'en'];

    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (!in_array($locale, $this->supported)) {
            $locale = 'fr';
        }

        session(['locale' => $locale]);

        return redirect()->back()->withHeaders([
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
