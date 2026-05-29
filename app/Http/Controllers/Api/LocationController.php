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
}
