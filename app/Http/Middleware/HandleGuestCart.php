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
        $response = $next($request);

        // Skip if user is authenticated or already has cookie
        if (auth()->check() || $request->hasCookie('guest_cart_token')) {
            return $response;
        }

        $guestToken = Str::uuid()->toString();
        return $response->cookie('guest_cart_token', $guestToken, 60 * 24 * 30, '/', null, false, true);
    }
}