<?php

namespace Tests\Feature;

use App\Models\Contenu;
use App\Models\Rayon;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_endpoints_publics_exposent_le_contenu_publie(): void
    {
        SiteSetting::create([
            'cle' => 'adresse',
            'libelle' => 'Adresse',
            'valeur' => '08 BP 0266 Cotonou, Bénin',
            'groupe' => 'contact',
        ]);

        $contenu = Contenu::create([
            'rubrique' => 'journal',
            'titre' => 'Journal d’Abouloussi — 2022',
            'slug' => 'journal-abouloussi-2022',
            'resume' => 'Numéro historique du journal.',
            'corps' => 'Téléchargez le journal.',
            'statut' => 'publie',
            'date_publication' => '2022-12-31',
            'a_la_une' => true,
        ]);

        $this->getJson('/api/site/settings')
            ->assertOk()
            ->assertJsonPath('data.contact.adresse', '08 BP 0266 Cotonou, Bénin');

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.journaux.0.titre', 'Journal d’Abouloussi — 2022')
            ->assertJsonPath('data.featured.0.slug', 'journal-abouloussi-2022');

        $this->getJson('/api/journaux')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'journal-abouloussi-2022');

        $this->getJson('/api/contenus/'.$contenu->slug)
            ->assertOk()
            ->assertJsonPath('data.vues', 1);
    }

    public function test_l_accueil_expose_les_parametres_et_les_sections_historiques(): void
    {
        SiteSetting::create([
            'cle' => 'hero_titre',
            'libelle' => 'Titre du hero',
            'valeur' => "LA VOIX D'ABOULOUSSI",
            'groupe' => 'accueil',
        ]);
        SiteSetting::create([
            'cle' => 'journal_telechargement_titre',
            'libelle' => 'Télécharger les journaux',
            'valeur' => 'Télécharger les journaux',
            'groupe' => 'journal',
        ]);
        SiteSetting::create([
            'cle' => 'jda_objectifs_titre',
            'libelle' => 'Titre Objectifs JDA',
            'valeur' => 'Objetifs',
            'groupe' => 'jda',
        ]);
        SiteSetting::create([
            'cle' => 'pjk_commission_1_titre',
            'libelle' => 'Titre commission PJK 1',
            'valeur' => '1- La Commission Environnement',
            'groupe' => 'parlement',
        ]);
        SiteSetting::create([
            'cle' => 'dgp_intro_titre',
            'libelle' => 'Titre Dis-moi Grand-Père',
            'valeur' => 'Contes, Legendes & Proverbes',
            'groupe' => 'histoire',
        ]);

        Contenu::create([
            'rubrique' => 'plan',
            'titre' => 'DEMOGRAPHIE',
            'slug' => 'plan-demographie',
            'statut' => 'publie',
        ]);
        Contenu::create([
            'rubrique' => 'image',
            'titre' => 'Koko',
            'slug' => 'galerie-koko-1',
            'statut' => 'publie',
            'image' => 'media/galerie.jpg',
        ]);
        Contenu::create([
            'rubrique' => 'conte',
            'titre' => 'Contes et légendes itchas',
            'slug' => 'contes-et-legendes-itchas',
            'statut' => 'publie',
        ]);
        Contenu::create([
            'rubrique' => 'journal',
            'titre' => 'N°9 (En 2022)',
            'slug' => 'journal-2022',
            'statut' => 'publie',
            'document' => 'media/journal.pdf',
            'document_nom' => 'journal.pdf',
        ]);

        $this->getJson('/api/site/settings')
            ->assertOk()
            ->assertJsonPath('data.accueil.hero_titre', "LA VOIX D'ABOULOUSSI")
            ->assertJsonPath('data.journal.journal_telechargement_titre', 'Télécharger les journaux')
            ->assertJsonPath('data.jda.jda_objectifs_titre', 'Objetifs')
            ->assertJsonPath('data.parlement.pjk_commission_1_titre', '1- La Commission Environnement')
            ->assertJsonPath('data.histoire.dgp_intro_titre', 'Contes, Legendes & Proverbes');

        $this->getJson('/api/home')
            ->assertOk()
            ->assertJsonPath('data.plans.0.titre', 'DEMOGRAPHIE')
            ->assertJsonPath('data.galerie.0.image', config('app.url').'/storage/media/galerie.jpg')
            ->assertJsonPath('data.contes.0.slug', 'contes-et-legendes-itchas')
            ->assertJsonPath('data.journaux.0.document', config('app.url').'/storage/media/journal.pdf');
    }

    public function test_le_contact_et_le_marche_sont_disponibles(): void
    {
        Rayon::create(['nom' => 'local', 'taux' => 0.18]);

        $this->postJson('/api/contact', [
            'nom' => 'Visiteur test',
            'email' => 'visiteur@example.test',
            'message' => 'Bonjour, je souhaite obtenir des informations.',
        ])->assertCreated();

        $this->assertDatabaseHas('contact_messages', ['email' => 'visiteur@example.test']);

        $this->getJson('/api/market/rayons')
            ->assertOk()
            ->assertJsonPath('data.0.nom', 'local')
            ->assertJsonPath('data.0.taux', 0.18);
    }
}
