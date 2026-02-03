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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('phase');
            $table->unsignedTinyInteger('dealer_index');
            $table->unsignedTinyInteger('current_player_index');
            $table->unsignedInteger('round_number');
            $table->unsignedTinyInteger('winner_player_index')->nullable();
            $table->string('trump_card_id')->nullable(); // CardCodec string, e.g., "H-12"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
