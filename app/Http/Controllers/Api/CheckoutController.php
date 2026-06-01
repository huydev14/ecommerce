<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\GhnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected $ghnService;
    public function __construct(GhnService $ghnService)
    {
        $this->ghnService = $ghnService;
    }

    public function checkout(CheckoutRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $cart = Cart::where('user_id', $user->id)->get();
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'Bạn chưa có giỏ hàng.'], 400);
        }

        $cartItems = CartItem::where('cart_id', $cart->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 400);
        }

        $address = CustomerAddress::find($validated['customer_address_id']);
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy địa chỉ.'], 404);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $orderItemsData = [];

            foreach ($cartItems as $item) {
                $variant = ProductVariant::with('product')
                    ->where('id', $item->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (!$variant || $variant->stock < $item->quantity) {
                    $name = $variant->product->name ?? 'Sản phẩm';
                    throw new \Exception("Sản phẩm '{$name}' không đủ số lượng trong kho.");
                }

                $variant->stock -= $item->quantity;
                $variant->save();

                $price = $variant->price;
                $lineTotal = $price * $item->quantity;
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product->name ?? 'Sản phẩm',
                    'product_sku' => $variant->sku ?? null,
                    'price' => $price,
                    'quantity' => $item->quantity,
                    'total_price' => $lineTotal,
                ];
            }
            $totalWeight = $cartItems->sum('quantity') * 200;

            $shippingFeeResponse = $this->ghnService->calculateFee($address->district_id, $address->ward_code, $totalWeight);
            $shippingFee = $shippingFeeResponse['total'] ?? 30000;

            $finalTotal = $subtotal + $shippingFee;

            $orderNumber = strtoupper(Str::random(8));

            $order = Order::create([
                'order_number'     => $orderNumber,
                'customer_id'      => $user->id,   
                'customer_name'    => $address->receiver_name ?? $user->name,
                'customer_phone'   => $address->receiver_phone ?? $user->phone,
                'customer_email'   => $user->email,
                'shipping_address' => json_encode([
                    'name' => $address->receiver_name,
                    'phone' => $address->receiver_phone,
                    'full_address' => $address->specific_address . ', ' . $address->ward_name . ', ' . $address->district_name . ', ' . $address->province_name
                ]),
                'notes'            => $validated['note'] ?? null,
                'subtotal'         => $subtotal,
                'shipping_fee'     => $shippingFee,
                'total_amount'     => $finalTotal,
                'status'           => 'processing',
                'payment_method'   => $validated['payment_method'],
                'payment_status'   => 'pending',
            ]);

            foreach ($orderItemsData as $data) {
                $order->items()->create($data);
            }

            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            if ($validated['payment_method'] === 'cod') {
                return response()->json([
                    'success' => true,
                    'message' => 'Đặt hàng thành công!',
                    'data' => [
                        'order_code' => $order->order_code,
                        'redirect_url' => '/checkout/success?order=' . $order->order_code
                    ]
                ]);
            } else if ($validated['payment_method'] === 'vnpay') {
                $paymentUrl = 'https://sandbox.vnpayment.vn/...';

                return response()->json([
                    'success' => true,
                    'message' => 'Đang chuyển hướng sang cổng thanh toán...',
                    'data' => [
                        'order_code' => $order->order_code,
                        'payment_url' => $paymentUrl
                    ]
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lỗi Checkout: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}