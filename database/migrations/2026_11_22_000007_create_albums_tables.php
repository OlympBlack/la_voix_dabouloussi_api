<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table): void {
            $table->id();
            $table->string('titre', 220);
            $table->string('slug', 250)->unique();
            $table->text('description')->nullable();
            $table->string('rubrique', 40)->default('jda')->index();
            $table->string('couverture', 500)->nullable();
            $table->date('date_album')->nullable();
            $table->string('statut', 20)->default('publie')->index();
            $table->timestamps();
        });

        Schema::create('album_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->string('image', 500);
            $table->string('legende', 220)->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_photos');
        Schema::dropIfExists('albums');
    }
};
