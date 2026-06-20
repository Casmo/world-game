<?php

use App\Models\User;
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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('type');

            // Optional target for activities that act on something (e.g. a Building).
            // Null for self-directed activities like Sleep.
            $table->nullableMorphs('target');

            $table->string('status')->default('active');
            $table->timestamp('started_at');
            $table->timestamp('completes_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // The sweep scans for due, still-active activities.
            $table->index(['status', 'completes_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
