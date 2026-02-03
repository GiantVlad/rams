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
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->unsignedTinyInteger('dealer_index');
            $table->unsignedBigInteger('seed');

            $table->json('hands'); // 5 players, 5 cards each
            $table->json('exchanged'); // per-player: number of cards exchanged with dealer (0–5)
            $table->json('taken'); // tricks won per player this round

            $table->unsignedTinyInteger('trick_number');
            $table->json('current_trick');

            // New fields for special rules
            $table->json('five_same_suit_declared')->nullable(); // player_index who declared, if any
            $table->json('partiya_declared_by')->nullable(); // player_index who declared partiya this round, if any

            $table->timestamps();

            $table->unique(['game_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
