<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;
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
                ->with('items.product.brand');

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
                    return '<div class="tw-flex tw-flex-col tw-gap-0.5">'
                        . '<span class="tw-font-medium tw-text-gray-900">' . e($order->customer_name) . '</span>'
                        . '<span class="tw-text-sm tw-text-gray-500">' . e($order->customer_phone) . '</span>'
                        . ($order->customer_email ? '<span class="tw-text-sm tw-text-gray-400">' . e($order->customer_email) . '</span>' : '')
                        . '</div>';
                })
                ->editColumn('status', function ($order) {
                    $meta = Order::statusMeta()[$order->status] ?? Order::statusMeta()[Order::STATUS_NEW];

                    return '<span class="show-order-status tw-inline-flex tw-cursor-pointer tw-items-center tw-gap-1.5 tw-rounded-full tw-px-2.5 tw-py-1 tw-text-xs tw-font-medium tw-transition-opacity hover:tw-opacity-70 ' . $meta['bg'] . ' ' . $meta['text'] . '" data-show-url="' . route('orders.show', $order->id) . '"><span class="tw-h-1.5 tw-w-1.5 tw-rounded-full ' . $meta['dot'] . '"></span>' . e(__($meta['label'])) . '</span>';
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
                        $brandName = $item->product?->brand?->name;
                        $thumbnail = $item->product?->optimized_thumbnail_url;

                        $image = $thumbnail
                            ? '<img src="' . e($thumbnail) . '" alt="' . e($productName) . '" class="tw-h-10 tw-w-10 tw-flex-shrink-0 tw-rounded tw-border tw-border-gray-200 tw-object-cover" />'
                            : '<span class="tw-flex tw-h-10 tw-w-10 tw-flex-shrink-0 tw-items-center tw-justify-center tw-rounded tw-border tw-border-gray-200 tw-bg-gray-50 tw-text-gray-300"><i class="fas fa-image"></i></span>';

                        $html = '<div class="tw-flex tw-items-start tw-gap-2">'
                            . $image
                            . '<div class="tw-min-w-0 tw-flex-1">'
                            . ($brandName ? '<div class="tw-truncate tw-text-xs tw-text-gray-400">' . e($brandName) . '</div>' : '')
                            . '<div class="tw-truncate tw-font-medium tw-text-blue-600">' . e($productName) . '</div>'
                            . ($hasVariant ? '<div class="tw-truncate tw-text-xs tw-italic tw-text-gray-400">↳ ' . e($variantName) . '</div>' : '')
                            . '<div class="tw-mt-0.5 tw-flex tw-items-center tw-justify-between tw-gap-4 tw-whitespace-nowrap tw-text-gray-500">'
                            . '<span class="tw-flex tw-items-center tw-gap-1.5">'
                            . ($item->quantity > 1 ? '<span class="tw-inline-flex tw-h-4 tw-w-4 tw-items-center tw-justify-center tw-rounded-sm tw-bg-amber-300 tw-text-xs tw-font-semibold tw-text-gray-900">' . $item->quantity . '</span><span>x ' . number_format((float) $item->price, 0, ',', '.') . ' ₫</span>' : '<span>' . number_format((float) $item->price, 0, ',', '.') . ' ₫</span>')
                            . '</span>'
                            . '<span class="tw-text-gray-400">SKU: ' . e($item->product_sku ?: '---') . '</span>'
                            . '</div>'
                            . '</div>'
                            . '</div>';

                        return $html;
                    })->implode('<div class="tw-h-2"></div>');

                    if ($remaining > 0) {
                        $rows .= '<div class="tw-h-1"></div><span class="show-order-status tw-cursor-pointer tw-pt-0.5 tw-text-xs tw-font-medium tw-text-gray-400 hover:tw-text-gray-600" data-show-url="' . route('orders.show', $order->id) . '">+' . $remaining . ' ' . __('order.more_items') . '</span>';
                    }

                    return '<div class="tw-flex tw-max-w-sm tw-flex-col tw-text-sm">' . $rows . '</div>';
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
            'statuses' => Order::statusOptions(),
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

        $activities = Activity::where('log_name', 'order')
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->with('causer')
            ->latest()
            ->limit(20)
            ->get();

        return view('orders.show', [
            'order' => $order,
            'activities' => $activities,
            'shippingAddress' => collect([
                $address['name'] ?? $order->customer_name,
                $address['phone'] ?? $order->customer_phone,
                $address['full_address'] ?? $order->shipping_address,
            ])->filter()->values()->all(),
            'statusOptions' => Order::statusOptions(),
            'currentStatusStyle' => Order::statusMeta()[$order->status] ?? Order::statusMeta()[Order::STATUS_NEW],
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
            'status' => ['required', Rule::in(array_keys(Order::statusMeta()))],
        ], [
            'status.required' => __('order.status_required'),
            'status.in' => __('order.status_invalid'),
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
