<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 40)->default('redacteur')->after('email');
            $table->boolean('actif')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'actif', 'last_login_at']);
        });
    }
};
