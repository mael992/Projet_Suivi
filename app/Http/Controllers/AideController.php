<?php

namespace App\Http\Controllers;

use App\Support\GuideAide;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Application « Besoin d'aide » : guide complet des applications MGDS,
 * avec les exemples d'e-mails réellement envoyés par la plateforme.
 */
class AideController extends Controller
{
    public function index()
    {
        return view('aide.index', ['sections' => GuideAide::sections()]);
    }

    public function pdf()
    {
        $pdf = Pdf::loadView('pdf.aide', [
            'sections'  => GuideAide::sections(),
            'genereLe'  => now(),
            'user'      => auth()->user(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('MGDS_Besoin_d_aide_' . now()->format('Y-m-d_H-i') . '.pdf');
    }
}
