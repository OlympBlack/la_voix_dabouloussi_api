<?php

use Database\Seeders\ProductionDataSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        (new ProductionDataSeeder)->run();
    }

    public function down(): void
    {
        //
    }
};
