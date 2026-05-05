<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CustomerOTPMail;
use App\Models\Customer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    protected function guard()
    {
        return Auth::guard('api_customer');
    }

    public function checkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $exists = Customer::where('email', $request->email)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $exists
            ]
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $key = 'login-attempts:' . Str::lower($credentials['email'])  . '|' . $request->ip();

        $token = $this->guard()->attempt($credentials);

        if (!$token) {
            RateLimiter::hit($key, 60);
            
            if (RateLimiter::tooManyAttempts($key, 8)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'success' => false,
                    'message' => "Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau.",
                    'retry_after' => $seconds
                ], 429);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không chính xác'
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
        return $this->responseWithToken($token);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email',
            'password' => 'required|min:6|confirmed',
        ], [
            'email.unique' => 'Địa chỉ email này đã được sử dụng.',
        ]);

        $customer = Customer::create([
            'fullname' => $validated['fullname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        //Create OTP and send mail
        $otp = rand(100000, 999999);

        Cache::put('otp_' . $customer->email, $otp, now()->addMinutes(10));

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

        $email = $request->email;

        $key = 'verify-otp:' . $email;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
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
            RateLimiter::hit($key, 300);

            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không chính xác. Bạn còn ' . RateLimiter::remaining($key, 5) . ' lần thử.'
            ], 400);
        }

        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin khách hàng.'
            ], 404);
        }

        // Update customer verified status
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

        $email = $request->email;
        $key = "resend-otp:" . $email;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Bạn đã gửi OTP quá nhiều lần. Vui lòng thử lại sau.",
                'retry_after' => $seconds
            ], 429);
        }

        $customer = Customer::where('email', $email)->first();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Email không tồn tại.'], 404);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $customer->email, $otp, now()->addMinutes(10));

        Mail::to($customer->email)->send(new CustomerOTPMail($otp, $customer->fullname));

        RateLimiter::hit($key, 300);

        return response()->json([
            'success' => true,
            'message' => 'Mã OTP mới đã được gửi vào email của bạn.'
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
