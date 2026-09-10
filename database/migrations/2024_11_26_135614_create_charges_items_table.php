<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('estate_charges_items')) {
            Schema::create('estate_charges_items', function (Blueprint $table) {
                $table->id();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->unsignedBigInteger('charge_id');
                $table->decimal('amount', 65, 2);
                $table->decimal('tax', 10, 2);
                $table->decimal('tax_amount', 65, 2);
                $table->longText('charge_attachment')->nullable();
                $table->string('status', 155);
                $table->timestamps();
            });
        }

        $foreignKeys = collect(Schema::getForeignKeys('estate_charges_items'))
            ->pluck('name')
            ->all();

        if (! in_array('estate_charges_items_charge_id_foreign', $foreignKeys, true)) {
            Schema::table('estate_charges_items', function (Blueprint $table) {
                $table->foreign('charge_id')
                    ->references('id')
                    ->on('estate_charges')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('estate_charges_items');
    }
};
