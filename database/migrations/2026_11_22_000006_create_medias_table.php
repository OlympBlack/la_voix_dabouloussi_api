<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nom', 220);
            $table->string('chemin', 500);
            $table->string('type', 20)->index();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('taille')->default(0);
            $table->string('alt', 220)->nullable();
            $table->string('rubrique', 40)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medias');
    }
};
