<?php

use App\Models\Team;
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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Team::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status');

            // The Tile this Unit is stationed on (home / garrison), if any.
            $table->string('tile_id')->nullable();
            $table->foreign('tile_id')->references('h3_index')->on('tiles')->nullOnDelete();

            // When a Training Unit becomes Idle (resolved by the sweep).
            $table->timestamp('available_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
