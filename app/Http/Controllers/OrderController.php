<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    public function index()
    {
        return view('orders.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $orders = Order::select('orders.*')
                ->withCount('items')
                ->withSum('items as item_quantity', 'quantity');

            if ($request->filled('status')) {
                $orders->where('orders.status', $request->status);
            }

            if ($request->filled('payment_status')) {
                $orders->where('orders.payment_status', $request->payment_status);
            }

            if ($request->filled('payment_method')) {
                $orders->where('orders.payment_method', $request->payment_method);
            }

            if ($request->filled('customer_id')) {
                $orders->where('orders.customer_id', $request->customer_id);
            }

            return DataTables::of($orders)
                ->editColumn('order_number', function ($order) {
                    return '<span class="tw-font-semibold tw-text-gray-900">#' . e($order->order_number) . '</span>';
                })
                ->addColumn('customer', function ($order) {
                    return view('orders._customer', compact('order'))->render();
                })
                ->editColumn('status', function ($order) {
                    $classes = [
                        'pending' => 'tw-bg-amber-100 tw-text-amber-700',
                        'processing' => 'tw-bg-blue-100 tw-text-blue-700',
                        'shipping' => 'tw-bg-indigo-100 tw-text-indigo-700',
                        'completed' => 'tw-bg-green-100 tw-text-green-700',
                        'cancelled' => 'tw-bg-red-100 tw-text-red-700',
                    ];
                    $labels = [
                        'pending' => 'order.statuses.pending',
                        'processing' => 'order.statuses.processing',
                        'shipping' => 'order.statuses.shipping',
                        'completed' => 'order.statuses.completed',
                        'cancelled' => 'order.statuses.cancelled',
                    ];

                    return '<span class="tw-px-2 tw-py-1 tw-text-xs tw-font-medium tw-rounded-full ' . ($classes[$order->status] ?? 'tw-bg-gray-100 tw-text-gray-600') . '">' . e(__($labels[$order->status] ?? 'order.unknown')) . '</span>';
                })
                ->editColumn('payment_status', function ($order) {
                    $classes = [
                        'pending' => 'tw-bg-amber-100 tw-text-amber-700',
                        'unpaid' => 'tw-bg-gray-100 tw-text-gray-600',
                        'paid' => 'tw-bg-green-100 tw-text-green-700',
                    ];
                    $labels = [
                        'pending' => 'order.payment_statuses.pending',
                        'unpaid' => 'order.payment_statuses.unpaid',
                        'paid' => 'order.payment_statuses.paid',
                    ];

                    return '<span class="tw-px-2 tw-py-1 tw-text-xs tw-font-medium tw-rounded-full ' . ($classes[$order->payment_status] ?? 'tw-bg-gray-100 tw-text-gray-600') . '">' . e(__($labels[$order->payment_status] ?? 'order.unknown')) . '</span>';
                })
                ->editColumn('payment_method', function ($order) {
                    return __([
                        'cod' => 'order.payment_methods.cod',
                        'bank_transfer' => 'order.payment_methods.bank_transfer',
                        'vnpay' => 'order.payment_methods.vnpay',
                        'momo' => 'order.payment_methods.momo',
                    ][$order->payment_method] ?? 'order.unknown');
                })
                ->editColumn('total_amount', function ($order) {
                    return number_format((float) $order->total_amount, 0, ',', '.') . ' ₫';
                })
                ->addColumn('item_summary', function ($order) {
                    return __('order.item_summary', [
                        'count' => (int) ($order->items_count ?? 0),
                        'quantity' => (int) ($order->item_quantity ?? 0),
                    ]);
                })
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? $order->created_at->format('d/m/Y H:i') : '';
                })
                ->editColumn('action', function ($order) {
                    return view('orders._orders-action', compact('order'))->render();
                })
                ->rawColumns(['order_number', 'customer', 'status', 'payment_status', 'action'])
                ->make(true);
        }
    }

    public function getFilterData()
    {
        $customers = Customer::select('id', DB::raw('COALESCE(fullname, email) as text'))
            ->whereIn('id', Order::query()->whereNotNull('customer_id')->select('customer_id'))
            ->orderBy('fullname')
            ->orderBy('email')
            ->get();

        return response()->json([
            'customers' => $customers,
            'statuses' => [
                ['id' => 'pending', 'text' => __('order.statuses.pending')],
                ['id' => 'processing', 'text' => __('order.statuses.processing')],
                ['id' => 'shipping', 'text' => __('order.statuses.shipping')],
                ['id' => 'completed', 'text' => __('order.statuses.completed')],
                ['id' => 'cancelled', 'text' => __('order.statuses.cancelled')],
            ],
            'payment_statuses' => [
                ['id' => 'pending', 'text' => __('order.payment_statuses.pending')],
                ['id' => 'unpaid', 'text' => __('order.payment_statuses.unpaid')],
                ['id' => 'paid', 'text' => __('order.payment_statuses.paid')],
            ],
            'payment_methods' => [
                ['id' => 'cod', 'text' => __('order.payment_methods.cod')],
                ['id' => 'bank_transfer', 'text' => __('order.payment_methods.bank_transfer')],
                ['id' => 'vnpay', 'text' => __('order.payment_methods.vnpay')],
                ['id' => 'momo', 'text' => __('order.payment_methods.momo')],
            ],
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'customer']);
        $address = json_decode($order->shipping_address, true) ?: [];

        return view('orders.show', [
            'order' => $order,
            'shippingAddress' => collect([
                $address['name'] ?? $order->customer_name,
                $address['phone'] ?? $order->customer_phone,
                $address['full_address'] ?? $order->shipping_address,
            ])->filter()->values()->all(),
            'statusOptions' => [
                ['id' => 'pending', 'text' => __('order.statuses.pending')],
                ['id' => 'processing', 'text' => __('order.statuses.processing')],
                ['id' => 'shipping', 'text' => __('order.statuses.shipping')],
                ['id' => 'completed', 'text' => __('order.statuses.completed')],
                ['id' => 'cancelled', 'text' => __('order.statuses.cancelled')],
            ],
            'paymentStatusOptions' => [
                ['id' => 'pending', 'text' => __('order.payment_statuses.pending')],
                ['id' => 'unpaid', 'text' => __('order.payment_statuses.unpaid')],
                ['id' => 'paid', 'text' => __('order.payment_statuses.paid')],
            ],
            'paymentMethodLabel' => __([
                'cod' => 'order.payment_methods.cod',
                'bank_transfer' => 'order.payment_methods.bank_transfer',
                'vnpay' => 'order.payment_methods.vnpay',
                'momo' => 'order.payment_methods.momo',
            ][$order->payment_method] ?? 'order.unknown'),
        ]);
    }

    public function update(Request $request, Order $order)
    {
        if (! $request->user()?->can('orders.update')) {
            abort(403, 'HR Demo không được cập nhật đơn hàng.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'shipping', 'completed', 'cancelled'])],
            'payment_status' => ['required', Rule::in(['pending', 'unpaid', 'paid'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'status.required' => __('order.status_required'),
            'status.in' => __('order.status_invalid'),
            'payment_status.required' => __('order.payment_status_required'),
            'payment_status.in' => __('order.payment_status_invalid'),
        ]);

        try {
            $order->update($validated);

            return response()->json([
                'success' => true,
                'message' => __('order.update_success'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Update order failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('order.system_error'),
            ], 500);
        }
    }
}
