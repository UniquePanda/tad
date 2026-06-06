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
        Schema::create('playback_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('show_id')->nullable()->constrained()->nullOnDelete();
            $table->string('spotify_album_id');
            $table->string('context_uri')->nullable();
            $table->string('track_uri');
            $table->string('track_name')->nullable();
            $table->unsignedInteger('position_ms');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'spotify_album_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playback_progress');
    }
};
