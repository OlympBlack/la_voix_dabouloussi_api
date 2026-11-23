<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@lavoixdabouloussi.org');
        $motDePasse = env('ADMIN_PASSWORD');

        if (! $motDePasse) {
            throw new RuntimeException('Définissez ADMIN_PASSWORD dans .env avant de lancer db:seed.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrateur principal',
                'password' => Hash::make($motDePasse),
                'role' => 'admin',
                'actif' => true,
            ],
        );
    }
}
