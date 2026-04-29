<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\JwtService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private JwtService $jwtService) {}

    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        $accessToken  = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        // Save refresh token to DB
        $user->refresh_token = $refreshToken;
        $user->saveQuietly();

        AuditLogService::log('Đã đăng nhập vào hệ thống', $user, 'auth', $user);

        return redirect()->intended(route('dashboard', absolute: false))
            ->withCookies([
                cookie('admin_access_token', $accessToken, config('jwt.ttl')),
                cookie('admin_refresh_token', $refreshToken, config('jwt.refresh_ttl'))
            ]);
    }

    /**
     * Logout user
     * Blacklist token and remove cookies
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            $accessToken = $request->cookie('access_token');
            $user = Auth::user();

            if ($accessToken) {
                JWTAuth::setToken($accessToken)->invalidate();
            }

            if ($user instanceof User) {
                // Remove refresh token to DB
                $user->refresh_token = null;
                $user->saveQuietly();

                AuditLogService::log('Đã đăng xuất khỏi hệ thống', $user, 'auth', $user);
            }
        } catch (Exception $e) {
            Log::error("Logout error: " . $e->getMessage());
        }

        return redirect()->route('login')
            ->withCookies([
                cookie()->forget('access_token'),
                cookie()->forget('refresh_token')
            ])
            ->with(['status' => 'Đăng xuất thành công']);
    }
}
