<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debit_note_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('debit_note_id')->index();
            $table->string('transaction_number')->nullable()->unique();
            $table->date('refund_date');
            $table->unsignedBigInteger('payment_method_id')->nullable()->index();
            $table->unsignedBigInteger('bank_account_id')->nullable()->index();
            $table->decimal('amount', 14, 2);
            $table->enum('status', ['pending','completed','cancelled'])->default('pending')->index();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable()->index();
            $table->timestamps();

            // Optional DB constraint - uncomment if you want enforced FK:
            // $table->foreign('debit_note_id')->references('id')->on('debit_notes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_note_refunds');
    }
};
