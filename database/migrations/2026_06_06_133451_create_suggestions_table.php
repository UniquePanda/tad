<?php

use App\Enums\SuggestionStatus;
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
        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('spotify_artist_id')->nullable();
            $table->string('spotify_album_id')->nullable();
            $table->string('name');
            $table->string('reason')->nullable();
            $table->float('score')->nullable();
            $table->string('status')->default(SuggestionStatus::Open->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
