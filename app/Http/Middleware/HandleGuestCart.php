<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class HandleGuestCart
{
    public function handle(Request $request, Closure $next): Response
    {
        // Case 1: User is authenticated or already has guest cart cookie
        if (auth('api')->check() || $request->hasCookie('guest_cart_token')) {
            return $next($request);
        }

        // Case 2: User is not authenticated and no guest_cart_token cookie
        $guestToken = Str::uuid()->toString();
        $request->merge(['guest_cart_token' => $guestToken]);

        $response = $next($request);

        return $response->cookie(
            'guest_cart_token', $guestToken,60 * 24 * 30, // 30 days
        );
    }
}