<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('note_number')->unique();
            $table->date('note_date');
            $table->unsignedBigInteger('party_id')->index(); // user id
            $table->enum('party_role', ['client','vendor'])->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->string('currency', 12)->default('GBP');
            $table->enum('status', ['draft','applied','refunded','cancelled'])->default('draft')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // optional foreign
            // $table->foreign('party_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
