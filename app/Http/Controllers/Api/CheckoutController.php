<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\GhnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected $ghnService;
    public function __construct(GhnService $ghnService)
    {
        $this->ghnService = $ghnService;
    }

    public function reviewCheckout(Request $request)
    {
        $user = $request->user();
        $cartKey = $this->getRedisCartKey($request);

        $cartItems = Redis::hGetAll($cartKey);

        if (empty($cartItems)) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 400);
        }

        $selectedAddress = null;

        $addresses = CustomerAddress::where('customer_id', $user->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();
        $selectedAddressId = $request->input('customer_address_id');
        $selectedAddress = $selectedAddressId
            ? $addresses->firstWhere('id', (int) $selectedAddressId)
            : $addresses->first();

        $variantIds = array_keys($cartItems);
        $variants = ProductVariant::with(['product'])
            ->whereIn('id', $variantIds)
            ->get();

        $subtotal = 0;
        $totalWeight = 0;
        $formattedItems = [];
        $estimate_shipping_date = now()->addDays(3)->format('d M Y') . ' - ' . now()->addDays(7)->format('d M Y');

        foreach ($variants as $variant) {
            $product = $variant->product ?? null;
            if (!$product) {
                continue;
            }
            $quantity = (int) $cartItems[$variant->id];
            $price = $variant->price;

            $lineTotal = $price * $quantity;
            $subtotal += $lineTotal;
            $totalWeight += $quantity * 200;

            $formattedItems[] = [
                'product_variant_id' => $variant->id,
                'product_name' => $product->name,
                'brand' => $variant->product->brand,
                'sku' => $variant->sku,
                'price' => $price,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'stock' => $variant->stocks ?? 0,
                'thumbnail' => $product->thumbnail,
                'estimate_shipping_date' => $estimate_shipping_date,
            ];
        }

        $shippingFee = 0;
        if ($selectedAddress) {
            $shippingResponse = $this->ghnService->calculateFee(
                $selectedAddress->district_id,
                $selectedAddress->ward_code,
                $totalWeight
            );
            $shippingFee = $shippingResponse['total'] ?? 30000;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $formattedItems,
                'address' => $selectedAddress,
                'addresses' => $addresses->values(),
                'summary' => [
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount' => 0,
                    'total' => $subtotal + $shippingFee
                ],
                'has_address' => $addresses->isNotEmpty(),
            ]
        ]);
    }

    public function processCheckout(CheckoutRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $cartKey = $this->getRedisCartKey($request);
        $cartItems = Redis::hGetAll($cartKey);

        if (empty($cartItems)) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 400);
        }

        $address = CustomerAddress::find($validated['customer_address_id']);
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy địa chỉ.'], 404);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $totalWeight = 0;
            $orderItemsData = [];

            $variantIds = array_keys($cartItems);

            sort($variantIds);

            foreach ($variantIds as $variantId) {
                $quantity = (int) $cartItems[$variantId];

                $variant = ProductVariant::with([
                    'product',
                    'stocks' => function ($query) {
                        $query->lockForUpdate();
                    }
                ])->where('id', $variantId)->first();

                if (!$variant || $variant->available_stock < $quantity) {
                    $name = $variant->product->name ?? 'Sản phẩm';
                    throw new \Exception("Sản phẩm '{$name}' không đủ số lượng trong kho.");
                }

                $remainingToDeduct = $quantity;

                foreach ($variant->stocks as $stock) {
                    $availableInThisStock = $stock->quantity - $stock->reserved_quantity;
                    if ($availableInThisStock > 0) {
                        $deduct = min($availableInThisStock, $remainingToDeduct);

                        $stock->quantity -= $deduct;
                        $stock->save();

                        $remainingToDeduct -= $deduct;
                    }

                    if ($remainingToDeduct <= 0) {
                        break;
                    }
                }
                $variant->save();

                $price = $variant->price;
                $lineTotal = $price * $quantity;
                $subtotal += $lineTotal;
                $totalWeight += $quantity * 200;

                $orderItemsData[] = [
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product->name ?? 'Sản phẩm',
                    'product_sku' => $variant->sku ?? null,
                    'price' => $price,
                    'quantity' => $quantity,
                    'total_price' => $lineTotal,
                ];
            }

            $shippingFeeResponse = $this->ghnService->calculateFee($address->district_id, $address->ward_code, $totalWeight);
            $shippingFee = $shippingFeeResponse['total'] ?? 30000;

            $finalTotal = $subtotal + $shippingFee;
            $orderNumber = strtoupper(Str::random(8));

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $user->id,
                'customer_name' => $address->receiver_name ?? $user->name,
                'customer_phone' => $address->receiver_phone ?? $user->phone,
                'customer_email' => $user->email,
                'shipping_address' => json_encode([
                    'name' => $address->receiver_name,
                    'phone' => $address->receiver_phone,
                    'full_address' => $address->specific_address . ', ' . $address->ward_name . ', ' . $address->district_name . ', ' . $address->province_name
                ]),
                'notes' => $validated['note'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $finalTotal,
                'status' => 'processing',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
            ]);

            foreach ($orderItemsData as $data) {
                $order->items()->create($data);
            }

            Redis::del($cartKey);

            DB::commit();

            if ($validated['payment_method'] === 'cod') {
                return response()->json([
                    'success' => true,
                    'message' => 'Đặt hàng thành công!',
                    'data' => [
                        'order_number' => $order->order_number,
                        'redirect_url' => '/checkout/success?order=' . $order->order_number
                    ]
                ]);
            } else if ($validated['payment_method'] === 'vnpay') {
                $paymentUrl = 'https://sandbox.vnpayment.vn/...';

                return response()->json([
                    'success' => true,
                    'message' => 'Đang chuyển hướng sang cổng thanh toán...',
                    'data' => [
                        'order_number' => $order->order_number,
                        'payment_url' => $paymentUrl
                    ]
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi Checkout: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function showOrder(Request $request, string $orderNumber)
    {
        $order = Order::with(['items.product'])
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order || ($order->customer_id && (int) $order->customer_id !== (int) $request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.',
            ], 404);
        }

        $shippingAddress = json_decode($order->shipping_address, true) ?: [];
        $paymentLabels = [
            'cod' => 'Thanh toán khi nhận hàng',
            'vnpay' => 'VNPay',
            'momo' => 'MoMo',
        ];
        $deliveryStart = $order->created_at?->copy()->addDays(3)->format('d M Y');
        $deliveryEnd = $order->created_at?->copy()->addDays(7)->format('d M Y');

        return response()->json([
            'success' => true,
            'data' => [
                'order_number' => $order->order_number,
                'placed_at' => optional($order->created_at)->format('d M Y, H:i'),
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'delivery_address' => collect([
                    $shippingAddress['name'] ?? null,
                    $shippingAddress['phone'] ?? null,
                    $shippingAddress['full_address'] ?? null,
                ])->filter()->values(),
                'estimate_shipping_date' => $deliveryStart && $deliveryEnd ? "{$deliveryStart} - {$deliveryEnd}" : null,
                'payment_method' => $paymentLabels[$order->payment_method] ?? $order->payment_method,
                'total' => $order->total_amount,
                'items' => $order->items->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->total_price,
                    'image' => $item->product->thumbnail,
                ])->values(),
            ],
        ]);
    }

    public function listOrders(Request $request)
    {
        $orders = Order::with(['items.product'])
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->get();

        $paymentLabels = [
            'cod' => 'Thanh toán khi nhận hàng',
            'vnpay' => 'VNPay',
            'momo' => 'MoMo',
        ];

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn($order) => [
                'order_number' => $order->order_number,
                'placed_at' => optional($order->created_at)->format('d M Y, H:i'),
                'status' => $order->status,
                'payment_method' => $paymentLabels[$order->payment_method] ?? $order->payment_method,
                'payment_status' => $order->payment_status,
                'total' => $order->total_amount,
                'item_count' => $order->items->sum('quantity'),
                'items' => $order->items->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->total_price,
                    'image' => $item->product->thumbnail,
                ])->values(),
            ])->values(),
        ]);
    }

    private function getRedisCartKey(Request $request): string
    {
        if ($request->user()) {
            return 'cart:user:' . $request->user()->id;
        }
        $guestToken = $request->cookie('guest_cart_token');
        return 'cart:guest:' . $guestToken;
    }
}
