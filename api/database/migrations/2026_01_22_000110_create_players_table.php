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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedTinyInteger('seat_index');
            $table->string('type');
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('pile')->default(20); // New: pile points (25 start)
            $table->unsignedTinyInteger('maltzy_count')->default(0); // New: number of maltzy declared this round
            $table->timestamps();

            $table->unique(['game_id', 'seat_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
