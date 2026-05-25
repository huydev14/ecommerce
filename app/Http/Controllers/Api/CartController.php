<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * API: GET /api/v1/cart
     */
    public function index(Request $request)
    {
        $cart = $this->getCart($request);

        if (!$cart) {
            return response()->json([
                'items' => [],
                'subtotal' => 0,
                'total_items' => 0
            ]);
        }

        $cart->load(['items.variant.product', 'items.variant.stocks']);

        $formattedItems = $cart->items->map(function ($item) {
            $variant = $item->variant;
            $product = $variant->product;

            $availableStock = $variant->available_stock;

            return [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'image_url' => $product->image_url,
                'price' => (float) $variant->price,
                'quantity' => $item->quantity,
                'max_stock' => $availableStock,
                'line_total' => $variant->price * $item->quantity,
                'is_available' => $availableStock > 0,
            ];
        });

        $subtotal = $formattedItems->sum('line_total');
        $totalItems = $formattedItems->sum('quantity');

        return response()->json([
            'items' => $formattedItems,
            'subtotal' => $subtotal,
            'total_items' => $totalItems
        ]);
    }

    /**
     * API: POST /api/v1/cart/items
     * Add product to cart
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $variantId = $request->input('product_variant_id');
        $quantity = $request->input('quantity');

        // Check stock in DB
        $variant = ProductVariant::with('stocks')->find($variantId);
        if (!$variant) {
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 404);
        }

        $availableStock = $variant->available_stock;
        if ($availableStock <= 0) {
            return response()->json(['message' => 'Sản phẩm này hiện tại đã hết hàng.'], 400);
        }

        $cart = $this->getOrCreateCart($request);

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->first();

        $newQuantity = $quantity;
        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;
        }

        // Check stock in DB after increment
        if ($newQuantity > $availableStock) {
            return response()->json([
                'message' => "Bạn chỉ có thể thêm tối đa {$availableStock} sản phẩm này vào giỏ hàng.",
                'current_stock' => $availableStock
            ], 400);
        }

        CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_variant_id' => $variantId],
            ['quantity' => $newQuantity]
        );

        return response()->json(['message' => 'Đã thêm sản phẩm vào giỏ hàng thành công.'], 201);
    }

    /**
     * API: PUT /api/v1/cart/items/{variant_id}
     */
    public function update(Request $request, $variantId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $requestedQuantity = $request->input('quantity');

        // Check stock
        $variant = ProductVariant::with('stocks')->find($variantId);

        if (!$variant) {
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 404);
        }

        $availableStock = $variant->available_stock;
        if ($requestedQuantity > $availableStock) {
            return response()->json(['message' => 'Số lượng yêu cầu vượt quá tồn kho cho phép.'], 400);
        }

        $cart = $this->getCart($request);
        if (!$cart) {
            return response()->json(['message' => 'Giỏ hàng không tồn tại.'], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($cartItem) {
            $cartItem->update(['quantity' => $requestedQuantity]);
            return response()->json(['message' => 'Cập nhật số lượng thành công.']);
        }

        return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ.'], 404);
    }

    /**
     * API: DELETE /api/v1/cart/items/{variant_id}
     */
    public function destroy(Request $request, $variantId)
    {
        $cart = $this->getCart($request);

        if ($cart) {
            CartItem::where('cart_id', $cart->id)
                ->where('product_variant_id', $variantId)
                ->delete();
        }

        return response()->json(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng.']);
    }

    /* --- HELPER ------------------ */

    private function getCart(Request $request)
    {
        if (auth()->check('api')) {
            return Cart::where('user_id', auth()->id())->first();
        }

        $guestToken = $request->cookie('guest_cart_token');
        if ($guestToken) {
            return Cart::where('guest_token', $guestToken)->first();
        }

        return null;
    }

    private function getOrCreateCart(Request $request): Cart
    {
        if (auth()->check('api')) {
            return Cart::firstOrCreate(['user_id' => auth()->id()]);
        }

        $guestToken = $request->cookie('guest_cart_token');
        return Cart::firstOrCreate(['guest_token' => $guestToken]);
    }
}