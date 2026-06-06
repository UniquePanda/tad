<?php

namespace Tests\Unit\Models;

use App\Models\SpotifyAuth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SpotifyAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeAuth(): SpotifyAuth
    {
        return SpotifyAuth::create([
            'user_id' => User::factory()->create()->id,
            'refresh_token' => 'secret-refresh',
            'access_token' => 'secret-access',
            'access_token_expires_at' => now()->addHour(),
        ]);
    }

    public function test_user_relation(): void
    {
        $auth = $this->makeAuth();
        $this->assertInstanceOf(User::class, $auth->user);
        $this->assertSame($auth->user_id, $auth->user->id);
    }

    public function test_tokens_are_decrypted_via_model(): void
    {
        $auth = $this->makeAuth()->fresh();
        $this->assertSame('secret-refresh', $auth->refresh_token);
        $this->assertSame('secret-access', $auth->access_token);
    }

    public function test_tokens_are_encrypted_at_rest(): void
    {
        $auth = $this->makeAuth();
        $row = DB::table('spotify_auth')->where('id', $auth->id)->first();

        $this->assertNotSame('secret-refresh', $row->refresh_token);
        $this->assertNotSame('secret-access', $row->access_token);
    }

    public function test_tokens_are_hidden_from_array(): void
    {
        $array = $this->makeAuth()->toArray();
        $this->assertArrayNotHasKey('refresh_token', $array);
        $this->assertArrayNotHasKey('access_token', $array);
    }

    public function test_access_token_expires_at_is_cast_to_datetime(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->makeAuth()->fresh()->access_token_expires_at);
    }
}
