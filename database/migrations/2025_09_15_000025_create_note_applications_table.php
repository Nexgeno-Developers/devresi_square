<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_applications', function (Blueprint $table) {
            $table->id();

            // which note (credit_notes or debit_notes)
            $table->nullableMorphs('note'); // note_id, note_type

            // where applied (invoices or purchase_invoices)
            $table->nullableMorphs('applied_to'); // applied_to_id, applied_to_type

            $table->decimal('applied_amount', 14, 2);
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->dateTime('applied_at')->nullable();
            $table->timestamps();

            $table->index(['note_id','note_type']);
            $table->index(['applied_to_id','applied_to_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_applications');
    }
};
