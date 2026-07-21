<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
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
                ->with('items.product');

            if ($request->filled('status')) {
                $orders->where('orders.status', $request->status);
            }

            if ($request->filled('payment_status')) {
                $orders->where('orders.payment_status', $request->payment_status);
            }

            if ($request->filled('payment_method')) {
                $orders->where('orders.payment_method', $request->payment_method);
            }

            return DataTables::of($orders)
                ->editColumn('order_number', function ($order) {
                    return '<span class="tw-text-gray-900">#' . e($order->order_number) . '</span>';
                })
                ->addColumn('customer', function ($order) {
                    return view('orders._customer', compact('order'))->render();
                })
                ->editColumn('status', function ($order) {
                    $classes = [
                        'pending' => 'tw-bg-gray-100 tw-text-gray-600',
                        'processing' => 'tw-bg-amber-50 tw-text-amber-700',
                        'shipping' => 'tw-bg-indigo-50 tw-text-indigo-700',
                        'completed' => 'tw-bg-emerald-50 tw-text-emerald-700',
                        'cancelled' => 'tw-bg-red-50 tw-text-red-700',
                    ];
                    $icons = [
                        'pending' => 'fas fa-clock',
                        'processing' => '',
                        'shipping' => 'fas fa-truck',
                        'completed' => 'fas fa-circle-check',
                        'cancelled' => 'fas fa-circle-xmark',
                    ];
                    $labels = [
                        'pending' => 'order.statuses.pending',
                        'processing' => 'order.statuses.processing',
                        'shipping' => 'order.statuses.shipping',
                        'completed' => 'order.statuses.completed',
                        'cancelled' => 'order.statuses.cancelled',
                    ];

                    return '<span class="show-order-status tw-inline-flex tw-cursor-pointer tw-items-center tw-gap-1.5 tw-rounded tw-px-1 tw-py-1 tw-text-xs tw-font-medium tw-capitalize tw-transition-opacity hover:tw-opacity-70 ' . ($classes[$order->status] ?? 'tw-bg-gray-100 tw-text-gray-600') . '" data-show-url="' . route('orders.show', $order->id) . '"><i class="' . ($icons[$order->status] ?? 'fas fa-circle-info') . '"></i>' . e(__($labels[$order->status] ?? 'order.unknown')) . '</span>';
                })
                ->editColumn('total_amount', function ($order) {
                    return number_format((float) $order->total_amount, 0, ',', '.') . ' ₫';
                })
                ->addColumn('order_items', function ($order) {
                    $maxVisible = 4;
                    $remaining = max(0, $order->items->count() - $maxVisible);

                    $rows = $order->items->take($maxVisible)->map(function ($item) {
                        $productName = $item->product?->name ?? $item->product_name;
                        $variantName = $item->product_name;
                        $hasVariant = $variantName !== $productName;

                        $html = '<div class="tw-flex tw-items-center tw-justify-between tw-gap-4">'
                            . '<span class="tw-truncate tw-font-medium tw-text-gray-900">' . e($productName) . '</span>'
                            . '<div class="tw-flex tw-items-center tw-gap-4 tw-whitespace-nowrap tw-text-gray-500">'
                            . '<span class="tw-flex tw-items-center tw-gap-1.5">'
                            . '<span class="tw-inline-flex tw-min-w-[18px] tw-items-center tw-justify-center tw-rounded tw-bg-gray-100 tw-px-1 tw-py-0.5 tw-text-[11px] tw-font-semibold tw-text-gray-700">' . $item->quantity . '</span>'
                            . '<span>x ' . number_format((float) $item->price, 0, ',', '.') . ' ₫</span>'
                            . '</span>'
                            . '<span class="tw-text-gray-400">SKU: ' . e($item->product_sku ?: '---') . '</span>'
                            . '</div>'
                            . '</div>';

                        if ($hasVariant) {
                            $html .= '<div class="tw-truncate tw-pl-3 tw-text-[11px] tw-italic tw-text-gray-400">↳ ' . e($variantName) . '</div>';
                        }

                        return $html;
                    })->implode('<div class="tw-h-1"></div>');

                    if ($remaining > 0) {
                        $rows .= '<div class="tw-h-1"></div><span class="show-order-status tw-cursor-pointer tw-pt-0.5 tw-text-[11px] tw-font-medium tw-text-gray-400 hover:tw-text-gray-600" data-show-url="' . route('orders.show', $order->id) . '">+' . $remaining . ' ' . __('order.more_items') . '</span>';
                    }

                    return '<div class="tw-flex tw-max-w-sm tw-flex-col tw-text-xs">' . $rows . '</div>';
                })
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? $order->created_at->format('d/m/Y H:i') : '';
                })
                ->editColumn('action', function ($order) {
                    return view('orders._orders-action', compact('order'))->render();
                })
                ->rawColumns(['order_number', 'customer', 'status', 'order_items', 'action'])
                ->make(true);
        }
    }

    public function getFilterData()
    {
        return response()->json([
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
