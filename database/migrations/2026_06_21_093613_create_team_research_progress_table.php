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
        Schema::create('team_research_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Team::class)->constrained()->cascadeOnDelete();
            $table->string('building_type');
            $table->unsignedBigInteger('points')->default(0);
            $table->timestamps();

            // Progress is banked per target, so switching targets never discards it.
            $table->unique(['team_id', 'building_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_research_progress');
    }
};
