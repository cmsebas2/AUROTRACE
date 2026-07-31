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
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_code')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('warehouse')->default('PT'); // PT o RZ
            $table->decimal('quantity', 12, 2)->default(0);
            $table->enum('uom', ['KIL', 'UND'])->default('UND');
            $table->timestamps();
            
            $table->foreign('item_id')->references('id')->on('items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
