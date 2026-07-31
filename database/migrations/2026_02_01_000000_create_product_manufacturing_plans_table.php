<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_manufacturing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('master_code'); // IF-304-1
            $table->string('version'); // 03
            $table->date('issue_date');
            $table->text('objective')->nullable();
            $table->text('equipment')->nullable();
            $table->text('potency_adjustment_logic')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_manufacturing_plans');
    }
};
