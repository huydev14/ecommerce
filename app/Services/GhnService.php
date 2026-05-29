<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GhnService
{
    private $apiUrl;
    private $token;

    public function __construct()
    {
        $this->apiUrl = env('GHN_API_URL');
        $this->token = env('GHN_API_TOKEN');
    }

    public function getProvinces()
    {
        try {
            $response = Http::withHeaders([
                'Token' => $this->token])
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
}