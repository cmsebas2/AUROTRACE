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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('presentation')->nullable();
            $table->string('pharmaceutical_form')->nullable();
            $table->string('ica_license')->nullable();
            $table->string('image')->nullable();
            $table->integer('vigencia_meses')->nullable();
            $table->decimal('base_batch_size', 10, 2)->default(100);
            $table->string('base_unit')->default('KG');
            $table->string('status')->default('ACTIVO');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
