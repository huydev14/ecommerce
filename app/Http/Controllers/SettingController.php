<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $configs = Setting::all()->keyBy('key');
        return view('settings.index', compact('configs'));
    }

    public function update(Request $request)
    {
        if (! $request->user()?->can('settings.update')) {
            abort(403, 'Bạn không có quyền cập nhật cấu hình.');
        }

        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
            Cache::forget("configs.{$key}");
        }
        return redirect()->back()->with('success', 'Cập nhật cấu hình thành công!');
    }

    public function updateOAuth(Request $request, $provider)
    {
        if (! $request->user()?->can('settings.update')) {
            abort(403, 'Bạn không có quyền cập nhật cấu hình OAuth.');
        }

         $rules = [
                'client_id' => 'required|string',
                'client_secret' => 'nullable|string',
                'redirect_uri' => 'required|url',
            ];

            if($provider === 'microsoft'){
                $rules['tenant'] = 'required|string';
            }

            $validated = $request->validate($rules);

        try {
            if ($request->filled('client_secret')) {
                $validated['client_secret'] = encrypt($request->client_secret);
            } else {
                $oldConfig = Setting::where('key', $provider)->where('group', 'oauth')->first();
                $validated['client_secret'] = $oldConfig->value['client_secret'] ?? null;
            }

            $validated['is_active'] = $request->boolean('is_active');

            Setting::updateOrCreate(
                ['key' => $provider, 'group' => 'oauth'],
                ['value' => $validated]
            );

            Cache::forget("config.oauth.{$provider}");

            return redirect()
                ->to(route('settings.index') . '?tab=' . $provider)
                ->with('success', "Đã lưu cấu hình " . ucfirst($provider) . " OAuth.");

        } catch (\Exception $e) {
            Log::error("OAuth Update Error [{$provider}]", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "Có lỗi xảy ra khi lưu cấu hình.");
        }
    }

    public function updateMail(Request $request)
    {
        if (! $request->user()?->can('settings.update')) {
            abort(403, 'Bạn không có quyền cập nhật cấu hình mail.');
        }

        $value = [
            'host' => $request->host,
            'port' => $request->port,
            'username' => $request->username,
            'encryption' => $request->encryption,
            'from_address' => $request->from_address,
            'from_name' => $request->from_name,
            'is_active' => $request->has('is_active'),
        ];

        if ($request->filled('password')) {
            $value['password'] = encrypt($request->password);
        } else {
            $oldConfig = Setting::where('key', 'smtp')->where('group', 'mail')->first();
            $value['password'] = $oldConfig->value['password'] ?? null;
        }

        try {
            Setting::updateOrCreate(
                ['key' => 'smtp', 'group' => 'mail'],
                ['value' => $value]
            );

            Cache::forget("config.mail.smtp");
            Artisan::call('queue:restart');

            return redirect()
                ->to(route('settings.index') . '?tab=mail')
                ->with('success', "Cấu hình Mail đã được cập nhật.");
        } catch (\Exception $e) {
            Log::error("Mail config update error: " . $e->getMessage());
            return redirect()->back()->with('error', "Có lỗi xảy ra khi lưu cấu hình Mail.");
        }
    }

    public function updateVnpay(Request $request)
    {
        if (! $request->user()?->can('settings.update')) {
            abort(403, 'Bạn không có quyền cập nhật cấu hình VNPAY.');
        }

        $validated = $request->validate([
            'tmn_code' => 'nullable|string|max:255',
            'hash_secret' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:500',
            'return_url' => 'nullable|url|max:500',
        ]);

        $oldConfig = Setting::where('key', 'vnpay')->where('group', 'integrations')->first();

        if ($request->filled('hash_secret')) {
            $validated['hash_secret'] = encrypt($request->hash_secret);
        } else {
            $validated['hash_secret'] = $oldConfig->value['hash_secret'] ?? null;
        }

        try {
            Setting::updateOrCreate(
                ['key' => 'vnpay', 'group' => 'integrations'],
                ['value' => $validated]
            );

            Cache::forget('config.integrations.vnpay');

            return redirect()
                ->to(route('settings.index') . '?tab=vnpay')
                ->with('success', 'Cấu hình VNPAY đã được cập nhật.');
        } catch (\Exception $e) {
            Log::error('VNPAY config update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi lưu cấu hình VNPAY.');
        }
    }

    public function testMail(Request $request)
    {
        if (! $request->user()?->can('settings.update')) {
            abort(403, 'Bạn không có quyền gửi email kiểm tra.');
        }

        try {
            Mail::raw('email test', function ($message) {
                $message->to('giahuy.codes@gmail.com')
                    ->subject('Test Brevo SMTP');
            });

            return 'Gửi email thành công';
        } catch (\Exception $e) {
            return 'Lỗi gửi mail: ' . $e->getMessage();
        }
    }
}
