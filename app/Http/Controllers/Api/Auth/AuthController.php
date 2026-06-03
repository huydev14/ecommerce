<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CustomerOTPMail;
use App\Models\Customer;
use App\Services\CartService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    const MAX_ATTEMPTS = 5;

    protected function guard()
    {
        return Auth::guard('api');
    }

    public function checkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $exists = Customer::where('email', $validated['email'])->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $exists
            ]
        ]);
    }

    public function login(Request $request, CartService $cartService)
    {
        $guestCartKey = $cartService->getCartKey($request);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $key = 'login-attempts:' . Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau.",
                'retry_after' => $seconds
            ], 429);
        }

        $token = $this->guard()->attempt($credentials);

        if (!$token) {
            RateLimiter::hit($key, 60);
            $remaining = RateLimiter::remaining($key, self::MAX_ATTEMPTS);

            return response()->json([
                'success' => false,
                'message' => "Email hoặc mật khẩu không chính xác. Bạn còn $remaining lần thử."
            ], 401);
        }

        $customer = $this->guard()->user();

        RateLimiter::clear($key);

        if (!$customer->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được xác minh.',
                'require_verify' => true,
            ], 403);
        }

        $userCartKey = $cartService->getCartKey($request);
        $cartService->mergeCartAfterLogin($guestCartKey, $userCartKey);

        return $this->responseWithToken($token);
    }

    public function register(Request $request)
    {
        $email = Str::lower(trim($request->input('email', '')));
        $key = 'register-attempts:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Bạn đã thử quá nhiều lần!",
                'retry_after' => $seconds
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email',
            'password' => 'required|min:6|confirmed',
        ], [
            'email.unique' => 'Địa chỉ email này đã được sử dụng.',
        ]);

        if ($validator->fails()) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $customer = Customer::create([
            'fullname' => $validated['fullname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        //Create OTP and send mail
        $otp = rand(100000, 999999);

        Cache::put('otp_' . $email, $otp, now()->addMinutes(10));

        Mail::to($customer->email)->send(new CustomerOTPMail($otp, $customer->fullname));

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để lấy mã OTP.',
        ]);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $email = Str::lower(trim($request->email));
        $key = 'verify-otp:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning("verifyOTP: too many attempts for {$email}");
            return response()->json([
                'success' => false,
                'message' => "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau.",
                'retry_after' => $seconds
            ], 429);
        }

        $userOtp = $request->otp;
        $cachedOtp = Cache::get('otp_' . $email);

        if (!$cachedOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.'
            ], 400);
        }

        if ($userOtp != $cachedOtp) {
            $remaining = RateLimiter::remaining($key, 5);

            RateLimiter::hit($key, 300);

            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không chính xác. Bạn còn ' . $remaining . ' lần thử.'
            ], 400);
        }

        $customer = Customer::where('email', $email)->first();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin khách hàng.'
            ], 404);
        }

        $customer->update(['email_verified_at' => now()]);

        // Clear OTP cache and rate limit keys
        Cache::forget('otp_' . $email);
        RateLimiter::clear($key);

        return response()->json([
            'success' => true,
            'message' => 'Xác thực email thành công! Bây giờ bạn có thể đăng nhập.'
        ]);
    }

    public function resendOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = Str::lower(trim($request->email));
        $key = "resend-otp:" . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning("resendOTP: too many attempts for {$email}");
            return response()->json([
                'success' => false,
                'message' => "Bạn đã gửi OTP quá nhiều lần. Vui lòng thử lại sau.",
                'retry_after' => $seconds
            ], 429);
        }

        $customer = Customer::where('email', $email)->first();
        $otp = rand(100000, 999999);
        if ($customer) {
            Cache::put('otp_' . $email, $otp, now()->addMinutes(10));
            Mail::to($customer->email)->send(new CustomerOTPMail($otp, $customer->fullname));
        }

        RateLimiter::hit($key, 300);

        return response()->json([
            'success' => true,
            'message' => 'Nếu email tồn tại, mã OTP mới đã được gửi vào email.'
        ]);
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->guard()->user()
            ]
        ]);
    }

    public function logout()
    {
        try {
            $this->guard()->logout();

            $cookie = cookie()->forget('refresh_token');

            return response()->json([
                'success' => true,
                'message' => 'Đăng xuất thành công'
            ])->withCookie($cookie);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể vô hiệu hóa token'
            ], 500);
        }
    }

    protected function responseWithToken($token)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'user' => $this->guard()->user(),
            ]
        ])->withCookie(cookie('refresh_token', $token, config('jwt.refresh_ttl')));
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');
        try {
            if (!$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy phiên làm việc. Vui lòng đăng nhập lại.'
                ], 401);
            }

            $payload = $this->guard()->setToken($refreshToken)->getPayload();
            $customerId = $payload->get('sub');

            //Check if customer still exists
            $customer = Customer::find($customerId);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng không còn tồn tại.'
                ], 401);
            }

            // Validate and refresh token
            $newToken = $this->guard()->setToken($refreshToken)->refresh();

            return $this->responseWithToken($newToken);

        } catch (TokenBlacklistedException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập đã bị thu hồi. Vui lòng đăng nhập lại.'
            ], 401);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.'
            ], 401);
        } catch (Exception $e) {
            Log::error('Refresh token error: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.'
            ], 401);
        }
    }
}
