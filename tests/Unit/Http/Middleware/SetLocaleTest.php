<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    public function test_it_applies_the_users_locale(): void
    {
        app()->setLocale('en');
        $user = User::factory()->make(['locale' => 'de']);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        (new SetLocale)->handle($request, fn () => new Response);

        $this->assertSame('de', app()->getLocale());
    }

    public function test_it_ignores_unsupported_locales(): void
    {
        app()->setLocale('en');
        $user = User::factory()->make(['locale' => 'xx']);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        (new SetLocale)->handle($request, fn () => new Response);

        $this->assertSame('en', app()->getLocale());
    }

    public function test_it_does_nothing_for_guests(): void
    {
        app()->setLocale('en');
        $request = Request::create('/');

        (new SetLocale)->handle($request, fn () => new Response);

        $this->assertSame('en', app()->getLocale());
    }
}
