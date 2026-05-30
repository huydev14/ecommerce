<script setup>
import { computed, onMounted, ref } from 'vue';
import { useCartStore } from '@/stores/cart';

const cartStore = useCartStore();
const updatingItems = ref(new Set());

const hasItems = computed(() => cartStore.items.length > 0);

const formatPrice = (price) => {
    const numericPrice = Number(price || 0);

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(numericPrice);
};

const clampQuantity = (quantity, maxStock) => {
    const normalizedQuantity = Number(quantity || 1);
    const normalizedStock = Number(maxStock || 1);

    return Math.max(1, Math.min(normalizedQuantity, normalizedStock));
};

const updateQuantity = async (item, event) => {
    const nextQuantity = clampQuantity(event.target.value, item.max_stock);

    event.target.value = nextQuantity;
    updatingItems.value = new Set(updatingItems.value).add(item.product_variant_id);

    try {
        await cartStore.updateItem(item.product_variant_id, nextQuantity);
    } finally {
        const nextUpdatingItems = new Set(updatingItems.value);
        nextUpdatingItems.delete(item.product_variant_id);
        updatingItems.value = nextUpdatingItems;
    }
};

const removeItem = async (item) => {
    updatingItems.value = new Set(updatingItems.value).add(item.product_variant_id);

    try {
        await cartStore.removeItem(item.product_variant_id);
    } finally {
        const nextUpdatingItems = new Set(updatingItems.value);
        nextUpdatingItems.delete(item.product_variant_id);
        updatingItems.value = nextUpdatingItems;
    }
};

const isUpdating = (item) => updatingItems.value.has(item.product_variant_id);

onMounted(() => {
    cartStore.fetchCart().catch(() => {});
});
</script>

<template>
    <section class="cart-page">
        <div class="cart-shell">
            <main class="cart-main">
                <header class="cart-header">
                    <h1>Giỏ hàng</h1>
                    <span>Giá</span>
                </header>

                <div v-if="cartStore.isLoading && !hasItems" class="cart-state">Đang tải giỏ hàng...</div>
                <div v-else-if="cartStore.errorMessage && !hasItems" class="cart-state is-error">{{ cartStore.errorMessage }}</div>
                <div v-else-if="!hasItems" class="cart-empty">
                    <h2>Giỏ hàng của bạn đang trống</h2>
                    <RouterLink :to="{ name: 'ProductList' }" class="cart-primary">Tiếp tục mua sắm</RouterLink>
                </div>

                <template v-else>
                    <article v-for="item in cartStore.items" :key="item.product_variant_id" class="cart-item">
                        <RouterLink :to="{ name: 'ProductList' }" class="cart-item__image">
                            <img :src="item.thumbnail" :alt="item.product_name" />
                        </RouterLink>

                        <div class="cart-item__details">
                            <h2>{{ item.product_name }}</h2>
                            <p v-if="item.variant_name" class="cart-muted">{{ item.variant_name }}</p>
                            <p class="cart-stock" :class="{ 'is-out': !item.is_available }">
                                {{ item.is_available ? 'Còn hàng' : 'Hết hàng' }}
                            </p>

                            <div class="cart-actions">
                                <label>
                                    <span>Số lượng</span>
                                    <input
                                        type="number"
                                        min="1"
                                        :max="item.max_stock"
                                        :value="item.quantity"
                                        :disabled="isUpdating(item)"
                                        @change="updateQuantity(item, $event)"
                                    />
                                </label>
                                <button type="button" :disabled="isUpdating(item)" @click="removeItem(item)">Xóa</button>
                            </div>
                        </div>

                        <div class="cart-item__price">{{ formatPrice(item.line_total) }}</div>
                    </article>

                    <div class="cart-mobile-subtotal">
                        Tạm tính ({{ cartStore.totalItems }} sản phẩm): <strong>{{ formatPrice(cartStore.subtotal) }}</strong>
                    </div>
                </template>
            </main>

            <aside v-if="hasItems" class="cart-summary">
                <h2>Tạm tính ({{ cartStore.totalItems }} sản phẩm)</h2>
                <div class="cart-summary__total">{{ formatPrice(cartStore.subtotal) }}</div>
                <RouterLink :to="{ name: 'Checkout' }" class="cart-checkout">Tiến hành thanh toán</RouterLink>
                <RouterLink :to="{ name: 'ProductList' }" class="cart-secondary">Tiếp tục mua sắm</RouterLink>
            </aside>
        </div>
    </section>
</template>

<style scoped>
.cart-page {
    min-height: 100vh;
    background: #eaeded;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.cart-shell {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 20px;
    max-width: 1480px;
    margin: 0 auto;
    padding: 22px;
}

.cart-main,
.cart-summary {
    background: #ffffff;
}

.cart-main {
    min-width: 0;
    padding: 20px;
}

.cart-header {
    display: flex;
    align-items: end;
    justify-content: space-between;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.cart-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 400;
}

.cart-header span {
    color: #565959;
    font-size: 14px;
}

.cart-item {
    display: grid;
    grid-template-columns: 180px minmax(0, 1fr) 140px;
    gap: 18px;
    border-bottom: 1px solid #e7e7e7;
    padding: 18px 0;
}

.cart-item__image {
    display: grid;
    height: 180px;
    place-items: center;
    background: #f7f7f7;
}

.cart-item__image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.cart-item__details h2 {
    margin: 0 0 6px;
    font-size: 18px;
    font-weight: 400;
    line-height: 1.35;
}

.cart-muted {
    color: #565959;
    font-size: 14px;
}

.cart-stock {
    margin: 0 0 12px;
    color: #007600;
    font-size: 13px;
}

.cart-stock.is-out {
    color: #b42318;
}

.cart-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.cart-actions label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background: #f0f2f2;
    padding: 5px 10px;
    font-size: 13px;
}

.cart-actions input {
    width: 64px;
    border: 0;
    background: transparent;
    font-size: 14px;
}

.cart-actions button {
    border: 0;
    background: transparent;
    color: #007185;
    cursor: pointer;
    font-size: 13px;
}

.cart-actions button:hover,
.cart-secondary:hover {
    color: #c45500;
    text-decoration: underline;
}

.cart-item__price {
    text-align: right;
    font-size: 18px;
    font-weight: 700;
}

.cart-summary {
    align-self: start;
    padding: 18px;
}

.cart-summary__success {
    margin: 0 0 12px;
    color: #067d62;
    font-size: 13px;
    line-height: 1.4;
}

.cart-summary h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 400;
}

.cart-summary__total {
    margin: 8px 0 16px;
    font-size: 22px;
    font-weight: 700;
}

.cart-checkout,
.cart-primary,
.cart-secondary {
    display: block;
    width: 100%;
    border-radius: 999px;
    padding: 9px 16px;
    text-align: center;
    text-decoration: none;
    font-size: 14px;
}

.cart-checkout,
.cart-primary {
    border: 1px solid #ffd814;
    background: #ffd814;
    color: #0f1111;
    cursor: pointer;
}

.cart-checkout:hover,
.cart-primary:hover {
    border-color: #f7ca00;
    background: #f7ca00;
}

.cart-secondary {
    margin-top: 10px;
    color: #007185;
}

.cart-state,
.cart-empty {
    display: grid;
    min-height: 260px;
    place-items: center;
    text-align: center;
}

.cart-state {
    color: #565959;
}

.cart-state.is-error {
    color: #b42318;
}

.cart-empty h2 {
    font-size: 24px;
    font-weight: 400;
}

.cart-empty p {
    margin: 0 0 18px;
    color: #565959;
}

.cart-mobile-subtotal {
    display: none;
}

@media (max-width: 920px) {
    .cart-shell {
        display: block;
        padding: 12px;
    }

    .cart-summary {
        margin-top: 14px;
    }

    .cart-item {
        grid-template-columns: 120px minmax(0, 1fr);
    }

    .cart-item__image {
        height: 120px;
    }

    .cart-item__price {
        grid-column: 2;
        text-align: left;
    }

    .cart-mobile-subtotal {
        display: block;
        padding-top: 14px;
        text-align: right;
        font-size: 16px;
    }
}

@media (max-width: 560px) {
    .cart-main {
        padding: 14px;
    }

    .cart-header h1 {
        font-size: 24px;
    }

    .cart-item {
        grid-template-columns: 96px minmax(0, 1fr);
        gap: 12px;
    }

    .cart-item__image {
        height: 96px;
    }

    .cart-actions label {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
