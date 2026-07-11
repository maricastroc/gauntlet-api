<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->boolean('is_demo_template')->default(false);
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('demo_token_id')->nullable()->index();
            $table->timestamp('demo_expires_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['is_demo_template', 'template_id', 'demo_token_id', 'demo_expires_at']);
        });
    }
};
