<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Services\GhnService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class CustomerAddressController extends Controller
{
    public function __construct(private readonly GhnService $ghnService)
    {
    }

    public function index()
    {
        return view('customer-addresses.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $addresses = CustomerAddress::with('customer')->select('customer_addresses.*');
            $this->applyFilters($addresses, $request);

            return DataTables::of($addresses)
                ->addColumn('customer', function ($address) {
                    return $address->customer?->fullname
                        ?: $address->customer?->email
                        ?: '<span class="tw-text-gray-400 tw-italic tw-text-sm">---</span>';
                })
                ->addColumn('full_address', function ($address) {
                    return e($address->full_address);
                })
                ->editColumn('is_default', function ($address) {
                    return $this->renderDefaultBadge($address->is_default);
                })
                ->editColumn('updated_at', function ($address) {
                    return $address->updated_at ? $address->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($address) {
                    return view('customer-addresses._customer-addresses-action', compact('address'))->render();
                })
                ->rawColumns(['customer', 'is_default', 'action'])
                ->make(true);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('customer_id')) {
            $query->where('customer_addresses.customer_id', $request->customer_id);
        }

        if ($request->filled('is_default')) {
            $query->where('customer_addresses.is_default', $request->is_default);
        }
    }

    private function renderDefaultBadge(bool $isDefault): string
    {
        if ($isDefault) {
            return '<span class="tw-px-1 tw-py-0.5 tw-bg-green-100 tw-text-green-700 tw-text-xs tw-font-medium tw-rounded-sm">' . __('customer_address.default') . '</span>';
        }

        return '<span class="tw-px-2 tw-py-1 tw-bg-gray-100 tw-text-gray-600 tw-text-xs tw-font-medium tw-rounded-full">' . __('customer_address.not_default') . '</span>';
    }

    public function getFilterData()
    {
        $customers = Customer::select('id', DB::raw('COALESCE(fullname, email) as text'))
            ->orderBy('fullname')
            ->orderBy('email')
            ->get();

        $defaultStatus = collect([
            ['id' => 1, 'text' => __('customer_address.default')],
            ['id' => 0, 'text' => __('customer_address.not_default')],
        ]);

        return response()->json([
            'customers' => $customers,
            'default_status' => $defaultStatus,
        ]);
    }

    public function create()
    {
        if (!auth()->user()?->can('customer-addresses.create')) {
            abort(403, 'Bạn không có quyền tạo địa chỉ khách hàng.');
        }

        $customers = $this->getCustomerOptions();
        $provinces = $this->ghnService->getProvinces();

        return view('customer-addresses.create', compact('customers', 'provinces'));
    }

    public function store(Request $request)
    {
        if (!$request->user()?->can('customer-addresses.store')) {
            abort(403, 'Bạn không có quyền tạo địa chỉ khách hàng.');
        }

        $validated = $this->validateAddress($request);
        $validated['is_default'] = $request->has('is_default');

        try {
            DB::transaction(function () use ($validated) {
                $hasAddress = CustomerAddress::where('customer_id', $validated['customer_id'])->exists();
                $validated['is_default'] = $validated['is_default'] || !$hasAddress;

                if ($validated['is_default']) {
                    $this->clearDefaultAddress($validated['customer_id']);
                }

                CustomerAddress::create($validated);
            });

            return response()->json([
                'success' => true,
                'message' => __('customer_address.create_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Create customer address failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('customer_address.system_error'),
            ], 500);
        }
    }

    public function edit(CustomerAddress $customer_address)
    {
        if (!auth()->user()?->can('customer-addresses.edit')) {
            abort(403, 'Bạn không có quyền mở form sửa địa chỉ khách hàng.');
        }

        $customers = $this->getCustomerOptions();
        $provinces = $this->ghnService->getProvinces();

        return view('customer-addresses.edit', [
            'address' => $customer_address,
            'customers' => $customers,
            'provinces' => $provinces,
        ]);
    }

    public function districts(Request $request)
    {
        $validated = $request->validate([
            'province_id' => ['required', 'integer'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->ghnService->getDistricts($validated['province_id']),
        ]);
    }

    public function wards(Request $request)
    {
        $validated = $request->validate([
            'district_id' => ['required', 'integer'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->ghnService->getWards($validated['district_id']),
        ]);
    }

    public function update(Request $request, CustomerAddress $customer_address)
    {
        if (!$request->user()?->can('customer-addresses.update')) {
            abort(403, 'Bạn không có quyền cập nhật địa chỉ khách hàng.');
        }

        $validated = $this->validateAddress($request);
        $validated['is_default'] = $request->has('is_default');

        try {
            DB::transaction(function () use ($customer_address, $validated) {
                $oldCustomerId = $customer_address->customer_id;

                if ($validated['is_default']) {
                    $this->clearDefaultAddress($validated['customer_id'], $customer_address->id);
                }

                $customer_address->update($validated);

                $this->ensureCustomerHasDefault($validated['customer_id']);

                if ($oldCustomerId !== (int) $validated['customer_id']) {
                    $this->ensureCustomerHasDefault($oldCustomerId);
                }
            });

            return response()->json([
                'success' => true,
                'message' => __('customer_address.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update customer address failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('customer_address.system_error'),
            ], 500);
        }
    }

    public function destroy(CustomerAddress $customer_address)
    {
        if (!auth()->user()?->can('customer-addresses.remove')) {
            abort(403, 'Bạn không có quyền xóa địa chỉ khách hàng.');
        }

        try {
            DB::transaction(function () use ($customer_address) {
                $customerId = $customer_address->customer_id;
                $wasDefault = $customer_address->is_default;

                $customer_address->delete();

                if ($wasDefault) {
                    CustomerAddress::where('customer_id', $customerId)
                        ->oldest()
                        ->limit(1)
                        ->update(['is_default' => true]);
                }
            });

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => __('customer_address.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete customer address failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('customer_address.system_error'),
            ], 500);
        }
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:20'],
            'province_id' => ['required', 'integer'],
            'district_id' => ['required', 'integer'],
            'ward_code' => ['required', 'string', 'max:255'],
            'province_name' => ['required', 'string', 'max:255'],
            'district_name' => ['required', 'string', 'max:255'],
            'ward_name' => ['required', 'string', 'max:255'],
            'specific_address' => ['required', 'string', 'max:255'],
        ], [
            'customer_id.required' => __('customer_address.customer_required'),
            'customer_id.exists' => __('customer_address.customer_invalid'),
            'receiver_name.required' => __('customer_address.receiver_name_required'),
            'receiver_phone.required' => __('customer_address.receiver_phone_required'),
            'province_id.required' => __('customer_address.province_required'),
            'district_id.required' => __('customer_address.district_required'),
            'ward_code.required' => __('customer_address.ward_required'),
            'province_name.required' => __('customer_address.province_name_required'),
            'district_name.required' => __('customer_address.district_name_required'),
            'ward_name.required' => __('customer_address.ward_name_required'),
            'specific_address.required' => __('customer_address.specific_address_required'),
        ]);
    }

    private function getCustomerOptions()
    {
        return Customer::orderBy('fullname')
            ->orderBy('email')
            ->get(['id', 'fullname', 'email']);
    }

    private function clearDefaultAddress(int $customerId, ?int $exceptId = null): void
    {
        CustomerAddress::where('customer_id', $customerId)
            ->when($exceptId, fn($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }

    private function ensureCustomerHasDefault(int $customerId): void
    {
        $query = CustomerAddress::where('customer_id', $customerId);

        if (!$query->exists() || (clone $query)->where('is_default', true)->exists()) {
            return;
        }

        (clone $query)->oldest()->limit(1)->update(['is_default' => true]);
    }
}
