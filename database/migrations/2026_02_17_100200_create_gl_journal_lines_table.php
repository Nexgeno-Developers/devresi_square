<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gl_journal_id')->constrained('gl_journals')->onDelete('cascade');
            $table->foreignId('gl_account_id')->constrained('gl_accounts');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('gl_journal_lines');
    }
};

