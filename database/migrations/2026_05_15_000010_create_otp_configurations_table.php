<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('otp_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('type');          // provider key: fast2sms, twillo, nexmo, etc.
            $table->tinyInteger('value')->default(0); // 1 = active, 0 = inactive
            $table->timestamps();
        });

        // Seed all providers — fast2sms active by default
        $providers = [
            ['type' => 'fast2sms',      'value' => 1],
            ['type' => 'twillo',        'value' => 0],
            ['type' => 'nexmo',         'value' => 0],
            ['type' => 'mimsms',        'value' => 0],
            ['type' => 'mimo',          'value' => 0],
            ['type' => 'msegat',        'value' => 0],
            ['type' => 'smsgatewayhub', 'value' => 0],
            ['type' => 'sparrow',       'value' => 0],
            ['type' => 'ssl_wireless',  'value' => 0],
            ['type' => 'zender',        'value' => 0],
        ];

        foreach ($providers as $p) {
            DB::table('otp_configurations')->insert(array_merge($p, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_configurations');
    }
};
