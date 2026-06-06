<?php

namespace Tests\Unit\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisteredUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_new_user_can_register(): void
    {
        $response = $this->post(route('register'), [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);
    }

    public function test_the_first_registered_user_becomes_admin(): void
    {
        $this->post(route('register'), [
            'username' => 'first',
            'email' => 'first@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertTrue(User::where('email', 'first@example.com')->first()->is_admin);
    }

    public function test_subsequent_registered_users_are_not_admins(): void
    {
        User::factory()->create();

        $this->post(route('register'), [
            'username' => 'second',
            'email' => 'second@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertFalse(User::where('email', 'second@example.com')->first()->is_admin);
    }

    public function test_registration_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Notification::assertSentTo(User::where('email', 'test@example.com')->first(), VerifyEmail::class);
    }
}
