<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GhnService
{
    private $apiUrl;
    private $token;
    private $shopId;

    public function __construct()
    {
        $config = $this->getGhnConfig();

        $this->apiUrl = $config['api_url'];
        $this->token = $config['api_token'];
        $this->shopId = $config['shop_id'];
    }

    private function getGhnConfig(): array
    {
        $settings = Cache::rememberForever('config.integrations.ghn', function () {
            return Setting::where('group', 'integrations')
                ->where('key', 'ghn')
                ->first()?->value;
        });

        if (empty($settings['is_active'])) {
            return [
                'api_url' => env('GHN_API_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api'),
                'api_token' => env('GHN_API_TOKEN'),
                'shop_id' => env('GHN_SHOP_ID'),
            ];
        }

        return [
            'api_url' => ($settings['api_url'] ?? '') ?: env('GHN_API_URL', 'https://dev-online-gateway.ghn.vn/shiip/public-api'),
            'api_token' => !empty($settings['api_token'])
                ? decrypt($settings['api_token'])
                : env('GHN_API_TOKEN'),
            'shop_id' => ($settings['shop_id'] ?? '') ?: env('GHN_SHOP_ID'),
        ];
    }

    public function getProvinces()
    {
        try {
            $response = Http::withHeaders([
                'Token' => $this->token
            ])
                ->get($this->apiUrl . '/master-data/province');

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error('GHN Get Provinces Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getDistricts($provinceId)
    {
        try {
            $response = Http::withHeaders(['Token' => $this->token])
                ->get($this->apiUrl . '/master-data/district', [
                    'province_id' => (int) $provinceId
                ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error('GHN Get Districts Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getWards($districtId)
    {
        try {
            $response = Http::withHeaders(['Token' => $this->token])
                ->get($this->apiUrl . '/master-data/ward', [
                    'district_id' => (int) $districtId
                ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error('GHN Get Wards Error: ' . $e->getMessage());
            return [];
        }
    }

    public function calculateFee($toDistrictId, $toWardCode, $weightInGrams = 1000)
    {
        try {
            $response = Http::withHeaders([
                'Token' => $this->token,
                'ShopId' => $this->shopId
            ])->acceptJson()
                ->post($this->apiUrl . '/v2/shipping-order/fee', [
                    'service_type_id' => 2,
                    'to_district_id' => (int) $toDistrictId,
                    'to_ward_code' => (string) $toWardCode,

                    'weight' => (int) $weightInGrams,
                    'length' => 10, // cm
                    'width' => 10,
                    'height' => 10,

                    'insurance_value' => 0,
                ]);
            $result = $response->json();

            if ($response->successful() && isset($result['code']) && $result['code'] === 200) {
                return $result['data'];
            }

            Log::error('GHN Calculate Fee Error: ', $result);
            return null;

        } catch (\Exception $e) {
            Log::error('GHN Calculate Fee Exception: ' . $e->getMessage());
            return null;
        }
    }
}
