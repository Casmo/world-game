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
        Schema::create('attacks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Team::class, 'attacker_team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('origin_tile_id');
            $table->string('target_tile_id');
            $table->string('status');
            $table->unsignedInteger('march_seconds');
            $table->timestamp('arrives_at');
            $table->json('report')->nullable();
            $table->timestamps();

            $table->index(['status', 'arrives_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attacks');
    }
};
