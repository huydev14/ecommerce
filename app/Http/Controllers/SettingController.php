<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function index()
    {
        $configs = Setting::all()->groupBy('group');
        return view('settings.index', compact('configs'));
    }

    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
            Cache::forget("configs.{$key}");
        }
        return redirect()->back()->with('success', 'Cập nhật cấu hình thành công!');
    }

    public function updateOAuth(Request $request, $provider)
    {
        try {
            $value = $request->validate([
                'client_id' => 'required|string',
                'client_secret' => 'required|string',
                'redirect_uri' => 'required|url',
            ]);

            if ($request->filled('client_secret')) {
                $value['client_secret'] = encrypt($request->client_secret);
            } else {
                $oldConfig = Setting::where('key', $provider)->where('group', 'oauth')->first();
                $value['client_secret'] = $oldConfig->value['client_secret'] ?? null;
            }

            $value['is_active'] = $request->has('is_active');

            Setting::updateOrCreate(
                ['key' => $provider, 'group' => 'oauth'],
                ['value' => $value]
            );

            Cache::forget("config.oauth.{$provider}");

            return redirect()
                ->to(route('settings.index') . '?tab=' . $provider)
                ->with('success', "Đã lưu cấu hình " . ucfirst($provider) . " OAuth.");
        } catch (\Exception $e) {
            Log::error("OAuth Update Error: " . $e->getMessage());
            return redirect()->back()->with('error', "Có lỗi xảy ra khi lưu cấu hình.");
        }
    }

    public function updateMail(Request $request)
    {
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
            return redirect()
                ->to(route('settings.index') . '?tab=mail')
                ->with('success', "Cấu hình Mail đã được cập nhật.");
        } catch (\Exception $e) {
            Log::error("Mail config update error: " . $e->getMessage());
            return redirect()->back()->with('error', "Có lỗi xảy ra khi lưu cấu hình Mail.");
        }
    }
}
