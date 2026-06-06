<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply the authenticated user's preferred locale (if it is supported).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && array_key_exists($user->locale, config('app.available_locales'))) {
            app()->setLocale($user->locale);
        }

        return $next($request);
    }
}
