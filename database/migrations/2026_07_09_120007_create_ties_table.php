<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round');
            $table->unsignedInteger('slot');
            // topology: where each side comes from — 'seed:A1' or 'winner:12'
            $table->string('home_source');
            $table->string('away_source');
            // optional materialization of the advancement (the BracketResolver remains the authority)
            $table->unsignedBigInteger('feeds_tie_id')->nullable()->index();
            $table->string('feeds_side')->nullable(); // home | away
            $table->unsignedBigInteger('match_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['stage_id', 'round', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ties');
    }
};
