<?php

namespace Tests\Feature;

use App\Models\DialogueQuestion;
use App\Support\GuideAide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filet de sécurité sur les langues : toute chaîne passée à __('…') dans une
 * vue doit avoir sa traduction anglaise, sinon la version anglaise du site
 * réaffiche du français sans que personne s'en aperçoive.
 */
class TraductionsTest extends TestCase
{
    use RefreshDatabase;

    private function catalogueAnglais(): array
    {
        return json_decode(file_get_contents(base_path('lang/en.json')), true) ?: [];
    }

    /**
     * Toute chaîne française passée à __('…') dans une vue doit figurer dans
     * lang/en.json.
     *
     * On ne réclame rien pour les chaînes déjà anglaises (l'échafaudage Breeze
     * en contient : « Save », « Current Password »…) : sans entrée, Laravel les
     * affiche telles quelles, ce qui est correct. Le français, lui, ressortirait
     * en français sur la version anglaise du site — c'est ce qu'on empêche ici.
     */
    public function test_aucune_chaine_francaise_de_vue_sans_traduction(): void
    {
        $catalogue = $this->catalogueAnglais();
        $manquants = [];

        $vues = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($vues as $fichier) {
            if (! str_ends_with($fichier->getFilename(), '.blade.php')) {
                continue;
            }

            preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($fichier->getPathname()), $trouves);

            foreach ($trouves[1] as $chaine) {
                $chaine = str_replace("\\'", "'", $chaine);

                // Les clés à points visent les fichiers PHP (messages.*, mgds.*)
                if (preg_match('/^[a-z_]+\.[a-z_]+$/', $chaine)) {
                    continue;
                }

                if (! $this->estFrancais($chaine) || array_key_exists($chaine, $catalogue)) {
                    continue;
                }

                $manquants[$chaine] = true;
            }
        }

        $this->assertSame([], array_keys($manquants),
            'Chaînes françaises sans traduction anglaise dans lang/en.json');
    }

    /** Accent ou mot outil français : suffisant pour distinguer les deux langues. */
    private function estFrancais(string $chaine): bool
    {
        if (preg_match('/[àâäçéèêëîïôöùûüÿœ]/iu', $chaine)) {
            return true;
        }

        $outils = ['le', 'la', 'les', 'des', 'du', 'une', 'un', 'vous', 'votre', 'pour',
                   'dans', 'sur', 'avec', 'sans', 'aux', 'est', 'sont', 'ne', 'pas', 'que'];

        $mots = preg_split('/[^a-zA-Z\']+/u', mb_strtolower($chaine), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return (bool) array_intersect($outils, $mots);
    }

    /** Le guide couvre bien les applications ajoutées depuis. */
    public function test_le_guide_couvre_les_applications_recentes(): void
    {
        $sections = GuideAide::sections();

        foreach (['demarrage', 'taches', 'marche', 'contact', 'messagerie',
                  'pensebete', 'dialogue', 'planning', 'absences', 'compte'] as $cle) {
            $this->assertArrayHasKey($cle, $sections, "Le guide doit couvrir « {$cle} »");
        }

        // Les titres des sections passent par __() : ils doivent être traduits
        $catalogue = $this->catalogueAnglais();
        foreach ($sections as $section) {
            $this->assertArrayHasKey($section['titre'], $catalogue);
        }
    }

    /** Les rubriques de la Boîte de dialogue suivent les applications. */
    public function test_les_rubriques_du_dialogue_sont_traduites(): void
    {
        $catalogue = $this->catalogueAnglais();

        foreach (DialogueQuestion::SECTIONS as $cle => $libelle) {
            $this->assertArrayHasKey($libelle, $catalogue, "Rubrique « {$cle} » non traduite");
            $this->assertArrayHasKey($cle, DialogueQuestion::SECTIONS_ACCES,
                "Rubrique « {$cle} » sans règle d'accès");
        }
    }
}
