<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CartController extends Controller
{
    private const CART_TTL = 2592000;

    /**
     * API: GET /api/v1/cart
     * Get list from redis
     */
    public function index(Request $request)
    {
        $cartKey = $this->getRedisCartKey($request);

        $redisItems = Redis::hGetAll($cartKey);
        // return ['variant_id' => 'quantity']

        if (empty($redisItems)) {
            return response()->json([
                'items' => [],
                'subtotal' => 0,
                'total_items' => 0
            ]);
        }

        // Get variants info from DB
        $variantIds = array_keys($redisItems);
        $variants = ProductVariant::with(['product', 'stocks'])
            ->whereIn('id', $variantIds)
            ->get();

        $formattedItems = [];
        $subtotal = 0;
        $totalItems = 0;

        foreach ($variants as $variant) {
            $quantity = (int) $redisItems[$variant->id];
            $availableStock = $variant->available_stock;
            $lineTotal = $variant->price * $quantity;

            $formattedItems[] = [
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name ?? null,
                'thumbnail'    => $variant->product->thumbnail,
                'price'    => (float) $variant->price,
                'quantity' => $quantity,
                'max_stock'=> $availableStock,
                'line_total'   => $lineTotal,
                'is_available' => $availableStock > 0,
            ];

            $subtotal += $lineTotal;
            $totalItems += $quantity;
        }

        return response()->json([
            'items' => $formattedItems,
            'subtotal' => $subtotal,
            'total_items' => $totalItems
        ]);
    }

    /**
     * API: POST /api/v1/cart/items
     * Add to Cart
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_variant_id' => 'required|integer',
            'quantity' => 'required|integer|min:1'
        ]);

        $variantId = $request->input('product_variant_id');
        $quantity = $request->input('quantity');

        // Check stocks
        $variant = ProductVariant::with('stocks')->find($variantId);

        if (!$variant) {
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 404);
        }

        $availableStock = $variant->available_stock;

        if ($availableStock <= 0) {
            return response()->json(['message' => 'Sản phẩm này hiện tại đã hết hàng.'], 400);
        }

        $cartKey = $this->getRedisCartKey($request);

        // Check current quantity before increment
        $currentQuantity = (int) Redis::hGet($cartKey, $variantId);

        // --- Increase --------------
        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > $availableStock) {
            return response()->json([
                'message' => "Bạn chỉ có thể thêm tối đa {$availableStock} sản phẩm này vào giỏ hàng.",
                'current_stock' => $availableStock
            ], 400);
        }
        Redis::hIncrBy($cartKey, $variantId, $quantity);

        Redis::expire($cartKey, self::CART_TTL);

        return response()->json(['message' => 'Đã thêm sản phẩm vào giỏ hàng thành công.'], 201);
    }

    /**
     * API: PUT /api/v1/cart/items/{variant_id}
     * Update quantity
     */
    public function update(Request $request, $variantId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $requestedQuantity = $request->input('quantity');

        // Check stocks
        $variant = ProductVariant::with('stocks')->find($variantId);

        if (!$variant) {
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 404);
        }

        if ($requestedQuantity > $variant->available_stock) {
            return response()->json(['message' => 'Số lượng yêu cầu vượt quá tồn kho cho phép.'], 400);
        }

        $cartKey = $this->getRedisCartKey($request);

        // Check if item still exists in cart
        if (!Redis::hExists($cartKey, $variantId)) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ.'], 404);
        }

        // Overwrite quantity with new value
        Redis::hSet($cartKey, $variantId, $requestedQuantity);
        Redis::expire($cartKey, self::CART_TTL);

        return response()->json(['message' => 'Cập nhật số lượng thành công.']);
    }

    /**
     * API: DELETE /api/v1/cart/items/{variant_id}
     * Delete item from cart
     */
    public function destroy(Request $request, $variantId): JsonResponse
    {
        $cartKey = $this->getRedisCartKey($request);

        // remove item from main hash
        Redis::hDel($cartKey, $variantId);

        return response()->json(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng.']);
    }

    /* --- HELPER ------------------ */

    /**
     * Get the Redis key based on user auth or guest token.
     */
    private function getRedisCartKey(Request $request): string
    {
        // Case 1: user is authenticated
        if (auth('api')->check()) {
            return 'cart:user:' . auth('api')->id();
        }
        // Case 2: guest user - use token from cookie
        $guestToken = $request->cookie('guest_cart_token') ?? $request->input('guest_cart_token');
        return 'cart:guest:' . $guestToken;
    }
}