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
        Schema::create('macro_processes', function (Blueprint $row) {
            $row->id();
            $row->string('name');
            $row->timestamps();
        });

        Schema::create('processes', function (Blueprint $row) {
            $row->id();
            $row->foreignId('macro_process_id')->constrained('macro_processes')->onDelete('cascade');
            $row->string('name');
            $row->timestamps();
        });

        Schema::create('sub_processes', function (Blueprint $row) {
            $row->id();
            $row->foreignId('process_id')->constrained('processes')->onDelete('cascade');
            $row->string('name');
            $row->timestamps();
        });

        Schema::create('activities', function (Blueprint $row) {
            $row->id();
            $row->foreignId('sub_process_id')->constrained('sub_processes')->onDelete('cascade');
            $row->string('name');
            $row->string('status_key')->nullable()->unique();
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('sub_processes');
        Schema::dropIfExists('processes');
        Schema::dropIfExists('macro_processes');
    }
};
