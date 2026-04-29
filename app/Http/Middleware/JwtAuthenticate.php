<?php

namespace App\Http\Middleware;

use App\Models\User;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use App\Services\JwtService;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;


class JwtAuthenticate
{
    public function __construct(private JwtService $jwtService) {}

    /**
     * Handle incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        $accessToken  = $request->cookie('admin_access_token');
        $refreshToken = $request->cookie('admin_refresh_token');

        // Default response
        $response = redirect()->route('login');

        if ($accessToken) {
            try {
                $this->jwtService->validate($accessToken);
                $user = $this->jwtService->getUserFromToken($accessToken);

                // Success
                if ($user instanceof User) {
                    Auth::shouldUse('api');
                    Auth::guard('api')->setUser($user);
                    $response =  $next($request);
                }
            } catch (TokenBlacklistedException) {
                $response = redirect()->route('login')->with(['status' => 'Phiên đăng nhập đã hết hạn.']);
            } catch (TokenExpiredException) {
                return $this->silentRefresh($request, $next, $refreshToken);
            } catch (\Exception $e) {
                Log::error('Lỗi xác thực không xác định: ' . $e->getMessage());
            }
        } elseif ($refreshToken) {
            return $this->silentRefresh($request, $next, $refreshToken);
        }

        return $response;
    }

    /*
    * Generate new access token and login
    */
    private function silentRefresh($request, $next, $refreshToken)
    {
        if (!$refreshToken) {
            return redirect()->route('login')->with(['status' => 'Phiên làm việc hết hạn, vui lòng đăng nhập lại.']);
        }

        try {
            $this->jwtService->validate($refreshToken);

            $user = User::where('refresh_token', $refreshToken)->first();
            if (!$user) {
                throw new AuthenticationException('Refresh token không hợp lệ');
            }

            // Generate new access token
            $newAccessToken = $this->jwtService->generateAccessToken($user);

            Auth::guard('api')->setUser($user);
            Auth::shouldUse('api');

            $response = $next($request);

            return $response->withCookie(
                cookie('access_token', $newAccessToken, config('jwt.ttl'))
            );
        } catch (\Exception $e) {
            Log::error('Silent refresh token error: ' . $e->getMessage());
            return redirect()->route('login')
                ->with(['status' => 'Phiên làm việc hết hạn, vui lòng đăng nhập lại.']);
        }
    }
}
