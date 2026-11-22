<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('cle', 120)->unique();
            $table->string('libelle', 180);
            $table->text('valeur')->nullable();
            $table->string('groupe', 60)->default('general');
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 150);
            $table->string('email', 180);
            $table->string('telephone', 40)->nullable();
            $table->string('objet', 180)->nullable();
            $table->text('message');
            $table->string('statut', 20)->default('nouveau');
            $table->ipAddress('ip')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('site_settings');
    }
};
