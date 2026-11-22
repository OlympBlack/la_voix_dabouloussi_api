<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contenus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('categorie_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rubrique', 40)->index();
            $table->string('titre', 220);
            $table->string('slug', 250)->unique();
            $table->string('resume', 600)->nullable();
            $table->longText('corps')->nullable();
            $table->string('auteur', 150)->nullable();
            $table->date('date_publication')->nullable()->index();
            $table->string('statut', 20)->default('brouillon')->index();
            $table->string('image', 500)->nullable();
            $table->string('document', 500)->nullable();
            $table->string('document_nom', 220)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('a_la_une')->default(false)->index();
            $table->unsignedBigInteger('vues')->default(0);
            $table->timestamps();

            $table->index(['rubrique', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenus');
    }
};
