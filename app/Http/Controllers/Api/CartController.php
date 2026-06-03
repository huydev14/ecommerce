<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * API: GET /api/v1/cart
     * Get list from redis
     */
    public function index(Request $request)
    {
        $cartKey = $this->cartService->getCartKey($request);

        $cartItems = Redis::hGetAll($cartKey);
        // return ['variant_id' => 'quantity']

        if (empty($cartItems)) {
            return response()->json([
                'items' => [],
                'subtotal' => 0,
                'total_items' => 0
            ]);
        }

        // Get variants info from DB
        $variantIds = array_keys($cartItems);
        $variants = ProductVariant::with(['product', 'stocks'])
            ->whereIn('id', $variantIds)
            ->get();

        $formattedItems = [];
        $subtotal = 0;
        $totalItems = 0;

        foreach ($variants as $variant) {
            $quantity = (int) $cartItems[$variant->id];
            $availableStock = $variant->available_stock;
            $lineTotal = $variant->price * $quantity;

            $formattedItems[] = [
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->attributes['variant_name'] ?? $variant->sku,
                'thumbnail' => $variant->product->thumbnail,
                'price' => (float) $variant->price,
                'quantity' => $quantity,
                'max_stock' => $availableStock,
                'line_total' => $lineTotal,
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
    public function store(Request $request)
    {
       $request->validate([
            'product_variant_id' => 'required|integer',
            'quantity' => 'required|integer|min:1'
        ]);

        $cartKey = $this->cartService->getCartKey($request);

        $variantId = $request->input('product_variant_id');
        $quantity = $request->input('quantity');

        $result = $this->cartService->addToCart($cartKey, $variantId, $quantity);
        return response()->json($result['data'], $result['status']);
    }

    /**
     * API: PUT /api/v1/cart/items/{variant_id}
     * Update quantity
     */
    public function update(Request $request, $variantId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cartKey = $this->cartService->getCartKey($request);
        $quantity = $request->input('quantity');

        $result = $this->cartService->updateItemQuantity($cartKey,$variantId,$quantity);

        return response()->json($result['data'], $result['status']);
    }

    /**
     * API: DELETE /api/v1/cart/items/{variant_id}
     * Delete item from cart
     */
    public function destroy(Request $request, $variantId): JsonResponse
    {
        $cartKey = $this->cartService->getCartKey($request);

        // remove item from main hash
        Redis::hDel($cartKey, $variantId);

        return response()->json(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng.']);
    }
}
