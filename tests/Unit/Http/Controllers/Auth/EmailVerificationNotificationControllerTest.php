<?php

namespace Tests\Unit\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_notification_can_be_resent_to_unverified_user(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post(route('verification.send'));

        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_already_verified_user_is_redirected_without_sending(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('verification.send'));

        Notification::assertNothingSent();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
