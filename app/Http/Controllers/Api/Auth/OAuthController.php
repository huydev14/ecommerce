<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Customer;

use App\Models\OAuthAccount;
use App\Models\Setting;


class OAuthController extends Controller
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
            [$user, $type] = $this->handleProviderCallback($provider);

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

    public function handleProviderCallback($provider)
    {
        $oauth = Socialite::driver($provider)->stateless()->user();

        parse_str(request()->input('state'), $stateParams);
        $type = $stateParams['type'] ?? 'user';

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
                        'fullname' => $oauth->getName(),
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
            //Create social account link
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

            Config::set("services.{$provider}", [
                'client_id' => $settings['client_id'] ?? null,
                'client_secret' => decrypt($settings['client_secret']) ?? null,
                'redirect' => $settings['redirect_uri'] ?? null,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to load OAuth config for {$provider}: " . $e->getMessage());
            return false;
        }
    }
}
