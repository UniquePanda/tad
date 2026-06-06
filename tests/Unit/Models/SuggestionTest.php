<?php

namespace Tests\Unit\Models;

use App\Enums\SuggestionStatus;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_defaults_to_open(): void
    {
        $suggestion = Suggestion::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Maybe a series',
        ]);

        $this->assertSame(SuggestionStatus::Open, $suggestion->status);
        $this->assertSame(SuggestionStatus::Open, $suggestion->fresh()->status);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $suggestion = Suggestion::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Maybe a series',
            'status' => SuggestionStatus::Confirmed,
        ]);

        $this->assertSame(SuggestionStatus::Confirmed, $suggestion->fresh()->status);
    }

    public function test_score_is_cast_to_float(): void
    {
        $suggestion = Suggestion::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Maybe a series',
            'score' => '0.75',
        ]);

        $this->assertSame(0.75, $suggestion->fresh()->score);
    }

    public function test_user_relation(): void
    {
        $user = User::factory()->create();

        $suggestion = Suggestion::create([
            'user_id' => $user->id,
            'name' => 'Maybe a series',
        ]);

        $this->assertInstanceOf(User::class, $suggestion->user);
        $this->assertSame($user->id, $suggestion->user->id);
    }
}
