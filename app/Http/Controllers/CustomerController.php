<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class CustomerController extends Controller
{
    public function index()
    {
        return view('customers.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $customers = Customer::query()->select('customers.*');

            if ($request->filled('is_active')) {
                $customers->where('customers.is_active', $request->is_active);
            }

            if ($request->filled('membership_tier')) {
                $customers->where('customers.membership_tier', $request->membership_tier);
            }

            return DataTables::of($customers)
                ->editColumn('fullname', function ($customer) {
                    return e($customer->fullname ?: __('customer.unknown_name'));
                })
                ->editColumn('email_verified_at', function ($customer) {
                    return $customer->email_verified_at
                        ? $this->renderVerifiedBadge(true)
                        : $this->renderVerifiedBadge(false);
                })
                ->editColumn('membership_tier', function ($customer) {
                    return '<span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-blue-50 tw-px-1 tw-text-xs tw-font-medium tw-text-blue-700 tw-capitalize">' . e(__('customer.tiers.' . $customer->membership_tier)) . '</span>';
                })
                ->editColumn('points', function ($customer) {
                    return number_format((int) $customer->points);
                })
                ->editColumn('is_active', function ($customer) {
                    return $this->renderStatusBadge((bool) $customer->is_active);
                })
                ->editColumn('updated_at', function ($customer) {
                    return $customer->updated_at ? $customer->updated_at->format('d/m/Y') : '';
                })
                ->editColumn('action', function ($customer) {
                    return view('customers._customers-action', compact('customer'))->render();
                })
                ->rawColumns(['email_verified_at', 'membership_tier', 'is_active', 'action'])
                ->make(true);
        }
    }

    public function getFilterData()
    {
        return response()->json([
            'status' => collect([
                ['id' => 1, 'text' => __('customer.active')],
                ['id' => 0, 'text' => __('customer.inactive')],
            ]),
            'tiers' => collect($this->tierOptions())->map(fn($label, $id) => [
                'id' => $id,
                'text' => $label,
            ])->values(),
        ]);
    }

    public function create()
    {
        if (!auth()->user()?->can('customers.create')) {
            abort(403, 'Bạn không có quyền tạo khách hàng.');
        }

        $tiers = $this->tierOptions();

        return view('customers.create', compact('tiers'));
    }

    public function store(Request $request)
    {
        if (!$request->user()?->can('customers.store')) {
            abort(403, 'Bạn không có quyền tạo khách hàng.');
        }

        $validated = $this->validateCustomer($request);
        $validated['is_active'] = $request->boolean('is_active');

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        try {
            Customer::create($validated);

            return response()->json([
                'success' => true,
                'message' => __('customer.create_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Create customer failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => __('customer.system_error_description'),
            ], 500);
        }
    }

    public function edit(Customer $customer)
    {
        if (!auth()->user()?->can('customers.edit')) {
            abort(403, 'Bạn không có quyền mở form sửa khách hàng.');
        }

        $tiers = $this->tierOptions();

        return view('customers.edit', compact('customer', 'tiers'));
    }

    public function update(Request $request, Customer $customer)
    {
        if (!$request->user()?->can('customers.update')) {
            abort(403, 'Bạn không có quyền cập nhật khách hàng.');
        }

        $validated = $this->validateCustomer($request, $customer);
        $validated['is_active'] = $request->boolean('is_active');

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        try {
            $customer->update($validated);

            return response()->json([
                'success' => true,
                'message' => __('customer.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update customer failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => __('customer.system_error_description'),
            ], 500);
        }
    }

    public function destroy(Customer $customer)
    {
        if (!auth()->user()?->can('customers.remove')) {
            abort(403, 'Bạn không có quyền xóa khách hàng.');
        }

        try {
            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => __('customer.delete_success'),
            ]);
        } catch (Exception $e) {
            Log::error('Delete customer failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => __('customer.system_error_description'),
            ], 500);
        }
    }

    public function restore($id)
    {
        if (!auth()->user()?->can('customers.remove')) {
            abort(403, 'Bạn không có quyền khôi phục khách hàng.');
        }

        try {
            $customer = Customer::withTrashed()->findOrFail($id);
            $customer->restore();

            return response()->json([
                'success' => true,
                'message' => __('customer.undo_success_description'),
            ]);
        } catch (Exception $e) {
            Log::error('Restore customer failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => __('customer.restore_error_description'),
            ], 500);
        }
    }

    private function validateCustomer(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'fullname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$customer ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'membership_tier' => ['required', Rule::in(array_keys($this->tierOptions()))],
            'points' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'email.required' => __('customer.email_required'),
            'email.email' => __('customer.email_invalid'),
            'email.unique' => __('customer.email_unique'),
            'password.required' => __('customer.password_required'),
            'password.min' => __('customer.password_min'),
            'membership_tier.required' => __('customer.tier_required'),
        ]);
    }

    private function tierOptions(): array
    {
        return [
            'standard' => __('customer.tiers.standard'),
            'silver' => __('customer.tiers.silver'),
            'gold' => __('customer.tiers.gold'),
            'premium' => __('customer.tiers.premium'),
        ];
    }

    private function renderStatusBadge(bool $isActive): string
    {
        if ($isActive) {
            return '<span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-emerald-50 tw-px-1 tw-text-xs tw-font-medium tw-text-emerald-700"><i class="fas fa-circle-check"></i>' . e(__('customer.active')) . '</span>';
        }

        return '<span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-gray-100 tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-text-gray-600"><i class="fas fa-circle-xmark"></i>' . e(__('customer.inactive')) . '</span>';
    }

    private function renderVerifiedBadge(bool $isVerified): string
    {
        if ($isVerified) {
            return '<span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-emerald-50 tw-px-1 tw-text-xs tw-font-medium tw-text-emerald-700"><i class="fas fa-circle-check"></i>' . e(__('customer.verified')) . '</span>';
        }

        return '<span class="tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded tw-bg-amber-50 tw-px-1 tw-text-xs tw-font-semibold tw-text-amber-700"><i class="fas fa-clock"></i>' . e(__('customer.unverified')) . '</span>';
    }
}
