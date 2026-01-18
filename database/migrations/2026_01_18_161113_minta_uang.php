<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mintauang_2210003', function (Blueprint $table) {
            $table->string('noref_2210003', 50)->primary();

            $table->timestamp('tglminta_2210003')->useCurrent();

            $table->unsignedBigInteger('dari_iduser_2210003')->nullable();
            $table->unsignedBigInteger('ke_iduser_2210003')->nullable();

            $table->double('jumlahuang_2210003')->nullable();

            $table->enum('stt_2210003', ['pending', 'sukses'])->nullable();

            $table->timestamp('tglsukses_2210003')->nullable();

            // Foreign keys
            $table->foreign('dari_iduser_2210003')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');

            $table->foreign('ke_iduser_2210003')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
