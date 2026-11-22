<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_connexion_et_les_permissions_admin_sont_controlees(): void
    {
        $admin = User::create([
            'name' => 'Administrateur',
            'email' => 'admin@example.test',
            'password' => 'MotDePasse123!',
            'role' => 'admin',
            'actif' => true,
        ]);

        $lecteur = User::create([
            'name' => 'Lecteur',
            'email' => 'lecteur@example.test',
            'password' => 'MotDePasse123!',
            'role' => 'lecteur',
            'actif' => true,
        ]);

        $adminToken = $this->postJson('/api/auth/login', [
            'email' => $admin->email,
            'password' => 'MotDePasse123!',
        ])->assertOk()->json('access_token');

        $this->assertNotEmpty($adminToken);

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/admin/dashboard')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/admin/contenus')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/admin/users')
            ->assertOk();

        $lecteurToken = $this->postJson('/api/auth/login', [
            'email' => $lecteur->email,
            'password' => 'MotDePasse123!',
        ])->assertOk()->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$lecteurToken)
            ->getJson('/api/admin/dashboard')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$lecteurToken)
            ->getJson('/api/admin/contenus')
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$lecteurToken)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_la_connexion_refuse_un_compte_inactif(): void
    {
        User::create([
            'name' => 'Inactif',
            'email' => 'inactif@example.test',
            'password' => 'MotDePasse123!',
            'role' => 'redacteur',
            'actif' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'inactif@example.test',
            'password' => 'MotDePasse123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
