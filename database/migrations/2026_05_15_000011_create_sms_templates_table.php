<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique();
            $table->text('sms_body');
            $table->string('template_id')->nullable(); // DLT template ID for India
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });

        // Seed default templates
        $templates = [
            [
                'identifier' => 'phone_number_verification',
                'sms_body'   => 'Your [[site_name]] verification code is [[code]]. Valid for 2 minutes. Do not share.',
                'template_id' => null,
                'status'     => 1,
            ],
            [
                'identifier' => 'password_reset',
                'sms_body'   => 'Your [[site_name]] password reset code is [[code]]. Valid for 10 minutes.',
                'template_id' => null,
                'status'     => 1,
            ],
            [
                'identifier' => 'account_opening',
                'sms_body'   => 'Welcome to [[site_name]]! Your account has been created. Login: [[code]]',
                'template_id' => null,
                'status'     => 1,
            ],
        ];

        foreach ($templates as $t) {
            DB::table('sms_templates')->insert(array_merge($t, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
