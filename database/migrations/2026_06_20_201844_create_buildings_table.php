<?php

use App\Enums\BuildingState;
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
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();

            // The Tile (keyed by H3 index) this Building sits on, and its
            // position in the Tile's fixed 10x10 Plot sub-grid.
            $table->string('tile_id');
            $table->foreign('tile_id')->references('h3_index')->on('tiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('plot_x');
            $table->unsignedTinyInteger('plot_y');

            $table->string('type');
            $table->string('state')->default(BuildingState::UnderConstruction->value);
            $table->unsignedInteger('work_done')->default(0);
            $table->timestamp('built_at')->nullable();
            $table->timestamps();

            // At most one Building per Plot position.
            $table->unique(['tile_id', 'plot_x', 'plot_y']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
