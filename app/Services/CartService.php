<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Redis;

class CartService
{
    private const CART_TTL = 2592000; // 30 days
    public function mergeCartAfterLogin(string $guestCartKey, string $userCartKey)
    {
        $guestItems = Redis::hGetAll($guestCartKey);

        if (empty($guestItems)) {
            return;
        }

        $userItems = Redis::hGetAll($userCartKey);

        $allVariantIds = array_unique(array_merge(array_keys($guestItems), array_keys($userItems)));

        $variants = ProductVariant::whereIn('id', $allVariantIds)->get()->keyBy('id');

        foreach ($guestItems as $variantId => $guestQty) {
            if (!$variants->has($variantId)) {
                continue;
            }

            $availableStock = $variants->get($variantId)->available_stock ?? 0;
            if ($availableStock < 0) {
                continue;
            }

            $userQty = $userItems[$variantId] ?? 0;

            $desiredQty = (int) $guestQty + $userQty;
            $finalQty = min($desiredQty, $availableStock);

            Redis::hSet($userCartKey, $variantId, $finalQty);
            Redis::expire($userCartKey, self::CART_TTL);
        }
        Redis::del($guestCartKey);
    }

    public function addToCart(string $cartKey, int $variantId, int $quantity): array
    {
        // Check stocks
        $variant = ProductVariant::with('stocks')->find($variantId);

        if (!$variant) {
            return [
                'status' => 404,
                'data' => ['message' => 'Sản phẩm không tồn tại.']
            ];
        }

        $availableStock = $variant->available_stock;

        if ($availableStock <= 0) {
            return [
                'status' => 400,
                'data' => ['message' => 'Sản phẩm này hiện tại đã hết hàng.']
            ];
        }

        // Check current quantity
        $currentQuantity = (int) Redis::hGet($cartKey, $variantId);

        // --- Increase --------------
        $newQuantity = $currentQuantity + $quantity;

        if ($newQuantity > $availableStock) {
            return [
                'status' => 400,
                'data' => [
                    'message' => "Bạn chỉ có thể thêm tối đa {$availableStock} sản phẩm này vào giỏ hàng.",
                    'current_stock' => $availableStock
                ]
            ];
        }

        Redis::hIncrBy($cartKey, $variantId, $quantity);
        Redis::expire($cartKey, self::CART_TTL);

        return [
            'status' => 201,
            'data' => ['message' => 'Đã thêm sản phẩm vào giỏ hàng thành công.']
        ];
    }

    public function updateItemQuantity(string $cartKey, int $variantId, int $requestedQuantity): array
    {
        // Check stocks
        $variant = ProductVariant::with('stocks')->find($variantId);

        if (!$variant) {
            return [
                'status' => 404,
                'data' => ['message' => 'Sản phẩm không tồn tại.']
            ];
        }

        if ($requestedQuantity > $variant->available_stock) {
            return [
                'status' => 400,
                'data' => ['message' => 'Số lượng yêu cầu vượt quá tồn kho cho phép.']
            ];
        }

        // Check if item still exists in cart
        if (!Redis::hExists($cartKey, $variantId)) {
            return [
                'status' => 404,
                'data' => ['message' => 'Không tìm thấy sản phẩm trong giỏ.']
            ];
        }

        // Overwrite quantity with new value
        Redis::hSet($cartKey, $variantId, $requestedQuantity);
        Redis::expire($cartKey, self::CART_TTL);

        return [
            'status' => 200,
            'data' => ['message' => 'Cập nhật số lượng thành công.']
        ];
    }

    /* --- HELPER ------------------ */

    /**
     * Get the Redis key based on user auth or guest token.
     */
    public function getCartKey($request): string
    {
        // Case 1: user is authenticated
        if (auth('api')->check()) {
            return 'cart:user:' . auth('api')->id();
        }
        // Case 2: guest user -> use token from cookie
        $guestToken = $request->cookie('guest_cart_token') ?? $request->input('guest_cart_token');
        return 'cart:guest:' . $guestToken;
    }
}