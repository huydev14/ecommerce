<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAddressRequest;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = CustomerAddress::where('customer_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    public function store(CustomerAddressRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $validated['delivery_note'] ??= '';

        DB::beginTransaction();
        try {
            if (isset($validated['is_default']) && $validated['is_default']) {
                CustomerAddress::where('customer_id', $user->id)->update(['is_default' => false]);
            }

            $isFirstAddress = CustomerAddress::where('customer_id', $user->id)->count() === 0;
            if ($isFirstAddress) {
                $validated['is_default'] = true;
            }

            $validated['customer_id'] = $user->id;
            $address = CustomerAddress::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thêm địa chỉ thành công.',
                'data' => $address
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(CustomerAddressRequest $request, $id)
    {
        $user = $request->user();
        $address = CustomerAddress::where('id', $id)->where('customer_id', $user->id)->firstOrFail();

        $validated = $request->validated();
        $validated['delivery_note'] ??= '';

        DB::beginTransaction();
        try {
            if (isset($validated['is_default']) && $validated['is_default']) {
                CustomerAddress::where('customer_id', $user->id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $address->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật địa chỉ thành công.',
                'data' => $address
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $address = CustomerAddress::where('id', $id)->where('customer_id', $user->id)->firstOrFail();

        $address->delete();

        if ($address->is_default) {
            $nextAddress = CustomerAddress::where('customer_id', $user->id)->oldest()->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa địa chỉ.'
        ]);
    }

    public function setDefault(Request $request, $id)
    {
        $user = $request->user();
        $address = CustomerAddress::where('id', $id)->where('customer_id', $user->id)->firstOrFail();

        DB::beginTransaction();
        try {
            CustomerAddress::where('customer_id', $user->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật địa chỉ mặc định.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
