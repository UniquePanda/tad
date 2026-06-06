<?php

namespace Tests\Unit\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationPromptControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_sees_the_prompt(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk();
    }

    public function test_verified_user_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('dashboard', absolute: false));
    }
}
