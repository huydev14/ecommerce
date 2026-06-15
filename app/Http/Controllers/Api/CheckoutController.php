<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\AuditLogService;
use App\Services\CartService;
use App\Services\GhnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected $ghnService;
    protected $cartService;
    public function __construct(GhnService $ghnService, CartService $cartService)
    {
        $this->ghnService = $ghnService;
        $this->cartService = $cartService;
    }

    public function reviewCheckout(Request $request)
    {
        $user = $request->user();
        $cartKey = $this->cartService->getCartKey($request);

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
                'thumbnail' => $product->optimized_thumbnail_url,
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

        $idemKey = $request->header('Idempotency-Key');

        if (!$idemKey) {
            return response()->json(['success' => false, 'message' => 'Thiếu Idempotency-Key trong Header.'], 400);
        }

        $redisIdemKey = 'checkout_idem_' . $user->id . '_' . $idemKey;

        $cachedResponse = Redis::get($redisIdemKey . '_result');
        if ($cachedResponse) {
            return response()->json(json_decode($cachedResponse, true));
        }

        $idemLock = Cache::lock($redisIdemKey . '_processing', 120);
        if (!$idemLock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng đang được xử lý.'
            ], 429);
        }

        try {
            $cartKey = $this->cartService->getCartKey($request);
            $cartItems = Redis::hGetAll($cartKey);

            if (empty($cartItems)) {
                return response()->json(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 400);
            }

            $address = CustomerAddress::find($validated['customer_address_id']);
            if (!$address) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy địa chỉ.'], 404);
            }

            $variantIds = array_keys($cartItems);
            sort($variantIds);

            $variantsForWeight = ProductVariant::whereIn('id', $variantIds)->get();
            $totalWeight = 0;
            foreach ($variantsForWeight as $v) {
                $totalWeight += ((int) $cartItems[$v->id]) * 200;
            }

            $shippingFeeResponse = $this->ghnService->calculateFee($address->district_id, $address->ward_code, $totalWeight);
            $shippingFee = $shippingFeeResponse['total'] ?? 30000;

            $subtotal = 0;
            $orderItemsData = [];
            $orderNumber = strtoupper(Str::random(8));

            DB::beginTransaction();

            $variants = ProductVariant::with(['product', 'stocks' => fn($q) => $q->lockForUpdate()])
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->orderBy('id', 'asc')
                ->get()
                ->keyBy('id');

            foreach ($variantIds as $variantId) {
                $quantity = (int) $cartItems[$variantId];
                $variant = $variants->get($variantId);

                if (!$variant || $variant->available_stock < $quantity) {
                    AuditLogService::log(
                        "Checkout failed due to insufficient stock for product variant ID: {$variantId}",
                        null,'checkout_failure',$user,
                        [
                            'variant_id' => $variantId,
                            'requested_quantity' => $quantity,
                            'available_stock' => $variant?->available_stock ?? 0,
                        ]
                    );
                    throw new \Exception("Sản phẩm '" . ($variant->product->name ?? '') . "' không đủ số lượng trong kho.");
                }

                // Minus stock and record movement
                $remainingToDeduct = $quantity;
                foreach ($variant->stocks as $stock) {
                    if ($stock->available_quantity > 0) {
                        $deduct = min($stock->available_quantity, $remainingToDeduct);
                        $stock->recordMovement('out', -$deduct, 'Customer checkout');
                        $remainingToDeduct -= $deduct;
                    }

                    if ($remainingToDeduct <= 0) {
                        break;
                    }
                }
                $variant->save();

                $lineTotal = $variant->price * $quantity;
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product->name ?? 'Sản phẩm',
                    'product_sku' => $variant->sku ?? null,
                    'price' => $variant->price,
                    'quantity' => $quantity,
                    'total_price' => $lineTotal,
                ];
            }

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
                'total_amount' => $subtotal + $shippingFee,
                'status' => 'processing',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
            ]);

            $order->items()->createMany($orderItemsData);

            DB::commit();

            AuditLogService::log(
                "Customer checkout: {$order->order_number} (ID: {$order->id})",
                $order,
                'checkout',
                $user,
                [
                    'order_number' => $order->order_number,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $validated['payment_method'],
                    'item_count' => array_sum(array_map('intval', $cartItems)),
                ]
            );

            Redis::del($cartKey);

            $responseData = [
                'success' => true,
                'message' => $validated['payment_method'] === 'cod' ? 'Đặt hàng thành công!' : 'Vui lòng thanh toán qua VNPAY.',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'redirect_url' => $validated['payment_method'] === 'cod' ? '/checkout/success?order=' . $order->order_number : null
                ]
            ];

            Redis::setex($redisIdemKey . '_result', 3600, json_encode($responseData));

            return response()->json($responseData);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi Checkout: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } finally {
            $idemLock->release();
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
                    'image' => $item->product?->optimized_thumbnail_url,
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
        ];

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn($order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'placed_at' => optional($order->created_at)->format('d M Y, H:i'),
                'status' => $order->status,
                'payment_method_code' => $order->payment_method,
                'payment_method' => $paymentLabels[$order->payment_method] ?? $order->payment_method,
                'payment_status' => $order->payment_status,
                'total' => $order->total_amount,
                'item_count' => $order->items->sum('quantity'),
                'items' => $order->items->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->total_price,
                    'image' => $item->product?->optimized_thumbnail_url,
                ])->values(),
            ])->values(),
        ]);
    }
}
