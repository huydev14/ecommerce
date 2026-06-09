<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Laravel\Socialite\Facades\Socialite;

use App\Models\User;
use App\Models\Customer;
use App\Models\OAuthAccount;
use App\Services\AuditLogService;

class OAuthController extends Controller
{
    public function googleRedirect()
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();

        } catch (\Exception $e) {
            Log::error("Google OAuth Redirect Error: " . $e->getMessage());
            return view('auth.callback', ['error' => "Dịch vụ Google hiện chưa được hỗ trợ."]);
        }
    }

    public function googleCallback()
    {
        try {
            [$user, $type] = $this->handleProviderCallback('google', 'customer');
            return $this->loginUser($user, $type);

        } catch (\Exception $e) {
            Log::error('Google login error: ', ['error' => $e->getMessage()]);
            return view('auth.callback', [
                'error' => 'Đăng nhập Google thất bại: Vui lòng thử lại sau.'
            ]);
        }
    }

    public function microsoftRedirect()
    {
        try {
            return Socialite::driver('microsoft')->redirect();

        } catch (\Exception $e) {
            Log::error("Microsoft OAuth Redirect Error: " . $e->getMessage());
            return view('auth.callback', ['error' => "Dịch vụ Microsoft hiện chưa được hỗ trợ."]);
        }
    }

    public function microsoftCallback()
    {
        try {
            [$user, $type] = $this->handleProviderCallback('microsoft', 'admin');
            return $this->loginUser($user, $type);

        } catch (\Exception $e) {
            Log::error('Microsoft login error: ', ['error' => $e->getMessage()]);
            return view('auth.callback', [
                'error' => 'Đăng nhập Microsoft thất bại: Vui lòng thử lại sau.'
            ]);
        }
    }

    private function loginUser($user, $type)
    {
        if ($type === 'admin') {
            auth('web')->login($user);
            request()->session()->regenerate();
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if ($type === 'customer') {
            $token = auth('api')->login($user);
            AuditLogService::log(
                'Khách hàng đăng nhập OAuth: ' . ($user->name ?: $user->email) . " (ID: {$user->id})",
                $user,
                'auth',
                $user,
                ['provider' => 'oauth']
            );
            $cookie = cookie('refresh_token', $token, config('jwt.refresh_ttl'));
            return response()
                ->view('auth.callback', compact('token', 'user'))
                ->withCookie($cookie);
        }
        throw new \Exception('Invalid user type: ' . $type);
    }

    private function handleProviderCallback($provider, $type)
    {
        if ($type === 'customer') {
            $oauth = Socialite::driver($provider)->stateless()->user();
        } else {
            $oauth = Socialite::driver($provider)->user();
        }

        return DB::transaction(function () use ($provider, $oauth, $type) {
            $oauthAccount = OAuthAccount::where('provider', $provider)
                ->where('provider_user_id', $oauth->getId())
                ->first();

            if ($oauthAccount) {
                $user = ($type === 'customer') ? $oauthAccount->customer : $oauthAccount->user;
                return [$user, $type];
            }

            if ($type === 'customer') {
                $user = Customer::firstOrCreate(
                    ['email' => $oauth->getEmail()],
                    [
                        'name' => $oauth->getName(),
                        'avatar' => $oauth->getAvatar(),
                        'email_verified_at' => now(),
                    ]
                );
            } else {
                $user = User::firstOrCreate(
                    ['email' => $oauth->getEmail()],
                    [
                        'name' => $oauth->getName(),
                        'email_verified_at' => now(),
                    ]
                );
            }
            // Create social account link
            OAuthAccount::updateOrCreate(
                [
                    'provider' => $provider,
                    'provider_user_id' => $oauth->getId(),
                ],
                [
                    'user_id' => ($type === 'customer') ? null : $user->id,
                    'customer_id' => ($type === 'customer') ? $user->id : null,
                ]
            );
            return [$user, $type];
        });
    }
}
