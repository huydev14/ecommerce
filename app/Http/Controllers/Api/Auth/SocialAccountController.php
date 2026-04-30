<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;

class SocialAccountController extends Controller
{

    public function redirect(Request $request, $provider)
    {
        if (!$this->loadOauthConfig($provider)) {
            return view('auth.callback', ['error' => "Dịch vụ " . ucfirst($provider) . " chưa được hỗ trợ."]);
        }
        $type = $request->query('type', 'user');

        return Socialite::driver($provider)
            ->with(['state' => 'type=' . $type])
            ->stateless()
            ->redirect();
    }

    public function callback($provider)
    {
        if (!$this->loadOauthConfig($provider)) {
            return view('auth.callback', ['error' => 'Cấu hình không hợp lệ.']);
        }
        try {
            $socialAuthService = new \App\Services\SocialAuthService();
            [$user, $type] = $socialAuthService->handleProviderCallback($provider);

            //Login user and generate token
            $guard = ($type === 'customer') ? 'api_customer' : 'api';
            $token = auth($guard)->login($user);

            //Set refresh token in cookie
            $cookie = cookie('refresh_token', $token, config('jwt.refresh_ttl'));

            return response()
                ->view('auth.callback', compact('token', 'user'))
                ->withCookie($cookie);

        } catch (\Exception $e) {
            \Log::error('Social login error: ', ['provider' => $provider, 'error' => $e->getMessage()]);
            return view('auth.callback', [
                'error' => 'Đăng nhập thất bại: ' . $e->getMessage()
            ]);
        }
    }

    public function loadOauthConfig($provider)
    {
        try {
            $config = Cache::rememberForever("config.oauth.{$provider}", function () use ($provider) {
                return Setting::where('key', $provider)
                    ->where('group', 'oauth')
                    ->first();
            });

            if (!$config || empty($config->value)) {
                return false;
            }

            $settings = $config->value;

            if (!($settings['is_active'])) {
                return false;
            }

            Setting::set("services.{$provider}", [
                'client_id' => $settings['client_id'] ?? null,
                'client_secret' => decrypt($settings['client_secret']) ?? null,
                'redirect' => $settings['redirect_uri'] ?? null,
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error("Failed to load OAuth config for {$provider}: " . $e->getMessage());
            return false;
        }
    }
}
