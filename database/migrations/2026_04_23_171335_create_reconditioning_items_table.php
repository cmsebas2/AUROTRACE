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
        Schema::create('reconditioning_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_code')->nullable(); // In case item_id is not directly related to products but item_code is
            $table->string('manufacturer')->nullable();
            $table->boolean('is_external')->default(false);
            $table->string('lot_number');
            $table->date('expiration_date');
            $table->decimal('quantity', 10, 2);
            $table->enum('uom', ['KIL', 'UND'])->default('UND');
            $table->string('transfer_number');
            $table->string('transfer_pdf_path')->nullable();
            $table->string('location')->nullable();
            $table->integer('req_label')->nullable();
            $table->integer('req_box')->nullable();
            $table->string('req_others')->nullable();
            $table->text('observations')->nullable();
            $table->enum('status', ['Pendiente', 'En Proceso', 'Terminado'])->default('Pendiente');
            $table->integer('used_labels')->nullable();
            $table->integer('used_boxes')->nullable();
            $table->timestamps();
            
            $table->foreign('item_id')->references('id')->on('items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconditioning_items');
    }
};
