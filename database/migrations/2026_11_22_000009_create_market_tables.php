<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rayons', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 100)->unique();
            $table->decimal('taux', 6, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('produits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rayon_id')->constrained()->cascadeOnDelete();
            $table->decimal('prix', 12, 2);
            $table->string('designation', 180)->nullable();
            $table->timestamps();
        });

        Schema::create('livebets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('chiffre');
            $table->decimal('prix_ht', 12, 2);
            $table->string('resultat', 20);
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livebets');
        Schema::dropIfExists('produits');
        Schema::dropIfExists('rayons');
    }
};
