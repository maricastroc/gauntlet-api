<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // owner/organizer
            $table->string('name');
            $table->string('format')->default('groups_knockout');
            $table->json('tiebreak')->nullable(); // tiebreak criteria; null => TiebreakRules::fifa()
            $table->string('status')->default('draft'); // draft | active | finished
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
