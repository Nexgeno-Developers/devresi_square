<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Make created_by nullable since we want to set it to null on user deletion
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Foreign key with explicit name for easier management
            $table->foreign('created_by', 'companies_created_by_foreign')
                ->references('id')->on('users')
                ->onDelete('set null');
                // ->onUpdate('cascade');
            $table->foreign('updated_by', 'companies_updated_by_foreign')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Drop foreign key before dropping the table to avoid constraint issues
            $table->dropForeign('companies_created_by_foreign');
        });

        Schema::dropIfExists('companies');
    }
};
