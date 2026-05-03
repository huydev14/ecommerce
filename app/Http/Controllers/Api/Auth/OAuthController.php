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

class OAuthController extends Controller
{
    public function redirect(Request $request, $provider)
    {
        try {
            $type = $request->query('type', 'user');

            return Socialite::driver($provider)
                ->with(['state' => 'type=' . $type])
                ->stateless()
                ->redirect();

        } catch (\Exception $e) {
            Log::error("OAuth Redirect Error: " . $e->getMessage());
            return view('auth.callback', ['error' => "Dịch vụ " . ucfirst($provider) . " hiện chưa được hỗ trợ."]);
        }
    }

    public function callback($provider)
    {
        try {
            [$user, $type] = $this->handleProviderCallback($provider);

            // If user type is admin
            if ($type === 'admin') {
                auth('web')->login($user);
                request()->session()->regenerate();

                return redirect()->intended(route('dashboard', absolute: false));
            }

            // If user type is customer
            $token = auth('api_customer')->login($user);

            $cookie = cookie('refresh_token', $token, config('jwt.refresh_ttl'));

            return response()
                ->view('auth.callback', compact('token', 'user'))
                ->withCookie($cookie);

        } catch (\Exception $e) {
            Log::error('Social login error: ', ['provider' => $provider, 'error' => $e->getMessage()]);
            return view('auth.callback', [
                'error' => 'Đăng nhập thất bại: Vui lòng thử lại sau.'
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
