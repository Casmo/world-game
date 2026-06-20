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
        Schema::create('tiles', function (Blueprint $table) {
            // The H3 cell index is the Tile's identity (ADR-0008).
            $table->string('h3_index')->primary();

            // Resolved lazily from open geographic data; null until resolved.
            $table->string('biome')->nullable();
            $table->string('terrain')->nullable();
            $table->json('base_resources')->nullable();

            $table->string('resolution_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiles');
    }
};
