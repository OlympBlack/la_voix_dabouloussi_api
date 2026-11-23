<?php

namespace Tests\Feature;

use Database\Seeders\ProductionDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_seeder_reproduit_toutes_les_donnees_actuelles(): void
    {
        $this->seed(ProductionDataSeeder::class);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('permissions', 2);
        $this->assertDatabaseCount('categories', 7);
        $this->assertDatabaseCount('contenus', 28);
        $this->assertDatabaseCount('medias', 187);
        $this->assertDatabaseCount('albums', 5);
        $this->assertDatabaseCount('album_photos', 62);
        $this->assertDatabaseCount('site_settings', 68);
        $this->assertDatabaseCount('rayons', 3);
        $this->assertDatabaseCount('contact_messages', 1);

        $this->assertDatabaseHas('contenus', [
            'slug' => '2eme-assemblee-a-la-maison-des-jeunes-akpon-makoro-a-koko-2-2019',
            'rubrique' => 'parlement',
            'statut' => 'publie',
        ]);

        $this->assertDatabaseHas('site_settings', [
            'cle' => 'jda_telephone',
            'valeur' => '01 45 79 32 39',
        ]);
    }
}
