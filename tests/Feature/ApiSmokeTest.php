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
