<?php

namespace Tests\Unit\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    private function verificationUrl(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => $hash ?? sha1($user->email)],
        );
    }

    public function test_email_can_be_verified(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get($this->verificationUrl($user));

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_an_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get($this->verificationUrl($user, sha1('wrong-email')));

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
