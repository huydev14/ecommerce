<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GhnService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $ghnService;

    public function __construct(){
        $this->ghnService = new GhnService();
    }

    public function getProvinces()
    {
        $provinces = $this->ghnService->getProvinces();
        return response()->json(['success' => true, 'data' => $provinces]);
    }

    public function getDistricts(Request $request)
    {
        $request->validate(['province_id' => 'required|numeric']);

        $districts = $this->ghnService->getDistricts($request->province_id);
        return response()->json(['success' => true, 'data' => $districts]);
    }

    public function getWards(Request $request)
    {
        $request->validate(['district_id' => 'required|numeric']);

        $wards = $this->ghnService->getWards($request->district_id);
        return response()->json(['success' => true, 'data' => $wards]);
    }

    public function calculateShippingFee(Request $request)
    {
        $validated = $request->validate([
            'province_id' => 'required|numeric',
            'weight' => 'nullable|numeric|min:1',
        ]);

        $district = collect($this->ghnService->getDistricts($validated['province_id']))->first();

        if (!$district) {
            return response()->json(['success' => false, 'message' => 'District not found.'], 404);
        }

        $districtId = $district['DistrictID'] ?? null;

        if (!$districtId) {
            return response()->json(['success' => false, 'message' => 'District not found.'], 404);
        }

        $ward = collect($this->ghnService->getWards($districtId))->first();

        if (!$ward) {
            return response()->json(['success' => false, 'message' => 'Ward not found.'], 404);
        }

        $wardCode = $ward['WardCode'] ?? null;
        $fee = $wardCode
            ? $this->ghnService->calculateFee($districtId, $wardCode, $validated['weight'] ?? 30000)
            : null;

        if (!$fee) {
            return response()->json(['success' => false, 'message' => 'Unable to calculate shipping fee.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $fee,
        ]);
    }
}
