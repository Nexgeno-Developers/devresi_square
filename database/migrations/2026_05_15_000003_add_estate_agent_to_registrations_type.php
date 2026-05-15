<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL: modify the enum to add estate_agent
        DB::statement("ALTER TABLE registrations MODIFY COLUMN type ENUM('landlord','owner','freelancing_agent','contractor','estate_agent') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE registrations MODIFY COLUMN type ENUM('landlord','owner','freelancing_agent','contractor') NOT NULL");
    }
};
