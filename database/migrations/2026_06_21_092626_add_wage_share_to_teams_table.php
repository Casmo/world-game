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
        Schema::table('teams', function (Blueprint $table) {
            // The Mayor-set fraction of a production shift's resaleable value paid
            // to the worker as Wages (ADR-0006). Clamped to a system floor/cap.
            $table->decimal('wage_share', 5, 4)->default(0.2000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('wage_share');
        });
    }
};
