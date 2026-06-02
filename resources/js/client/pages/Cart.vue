<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCartStore } from '@/stores/cart';

const { t } = useI18n();
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
    await setItemQuantity(item, nextQuantity);
};

const changeQuantity = async (item, amount) => {
    const nextQuantity = clampQuantity(Number(item.quantity || 1) + amount, item.max_stock);

    if (nextQuantity === Number(item.quantity || 1)) {
        return;
    }

    await setItemQuantity(item, nextQuantity);
};

const setItemQuantity = async (item, quantity) => {
    updatingItems.value = new Set(updatingItems.value).add(item.product_variant_id);

    try {
        await cartStore.updateItem(item.product_variant_id, quantity);
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
                    <div>
                        <h1>{{ t('cart.title') }}</h1>
                        <p v-if="hasItems">{{ t('cart.subtotalWithCount', { count: cartStore.totalItems }) }}</p>
                    </div>
                    <RouterLink v-if="hasItems" :to="{ name: 'ProductList' }" class="cart-header__link">
                        {{ t('cart.continueShopping') }}
                    </RouterLink>
                </header>

                <div v-if="cartStore.isLoading && !hasItems" class="cart-state">{{ t('cart.loading') }}</div>
                <div v-else-if="cartStore.errorMessage && !hasItems" class="cart-state is-error">{{ cartStore.errorMessage }}</div>
                <div v-else-if="!hasItems" class="cart-empty">
                    <div class="cart-empty__icon" aria-hidden="true">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h2>{{ t('cart.emptyTitle') }}</h2>
                    <RouterLink :to="{ name: 'ProductList' }" class="cart-primary">{{ t('cart.continueShopping') }}</RouterLink>
                </div>

                <template v-else>
                    <div class="cart-list">
                        <article v-for="item in cartStore.items" :key="item.product_variant_id" class="cart-item">
                            <RouterLink :to="{ name: 'ProductList' }" class="cart-item__image">
                                <img v-if="item.thumbnail" :src="item.thumbnail" :alt="item.product_name" />
                            </RouterLink>

                            <div class="cart-item__content">
                                <div class="cart-item__top">
                                    <div class="cart-item__details">
                                        <RouterLink :to="{ name: 'ProductList' }" class="cart-item__name">
                                            {{ item.product_name }}
                                        </RouterLink>
                                        <div class="cart-item__meta">
                                            <span v-if="item.variant_name" class="cart-variant">{{ item.variant_name }}</span>
                                            <span class="cart-stock" :class="{ 'is-out': !item.is_available }">
                                                {{ item.is_available ? t('cart.stock_in') : t('cart.stock_out') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="cart-item__price">
                                        <span>{{ t('cart.price') }}</span>
                                        <strong>{{ formatPrice(item.line_total) }}</strong>
                                    </div>
                                </div>

                                <div class="cart-actions">
                                    <label class="cart-quantity">
                                        <span>{{ t('cart.quantity') }}</span>
                                        <button
                                            type="button"
                                            class="cart-quantity__button hover:tw-bg-yellow-500"
                                            :disabled="isUpdating(item) || Number(item.quantity || 1) <= 1"
                                            :aria-label="`Decrease ${item.product_name}`"
                                            @click="changeQuantity(item, -1)"
                                        >
                                            <i class="fa-solid fa-minus" aria-hidden="true"></i>
                                        </button>
                                        <input
                                            type="number"
                                            min="1"
                                            :max="item.max_stock"
                                            :value="item.quantity"
                                            :disabled="isUpdating(item)"
                                            @change="updateQuantity(item, $event)"
                                        />
                                        <button
                                            type="button"
                                            class="cart-quantity__button hover:tw-bg-yellow-500"
                                            :disabled="isUpdating(item) || Number(item.quantity || 1) >= Number(item.max_stock || 1)"
                                            :aria-label="`Increase ${item.product_name}`"
                                            @click="changeQuantity(item, 1)"
                                        >
                                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                        </button>
                                    </label>
                                    <button type="button" class="cart-remove" :disabled="isUpdating(item)" @click="removeItem(item)">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        <span>{{ t('cart.remove') }}</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="cart-mobile-subtotal">
                        {{ t('cart.subtotalWithCount', { count: cartStore.totalItems }) }}: <strong>{{ formatPrice(cartStore.subtotal) }}</strong>
                    </div>
                </template>
            </main>

            <aside v-if="hasItems" class="cart-summary">
                <div class="cart-summary__panel">
                    <h2>{{ t('cart.subtotalWithCount', { count: cartStore.totalItems }) }}</h2>
                    <dl>
                        <div>
                            <dt>{{ t('cart.price') }}</dt>
                            <dd>{{ formatPrice(cartStore.subtotal) }}</dd>
                        </div>
                    </dl>
                    <div class="cart-summary__total">{{ formatPrice(cartStore.subtotal) }}</div>
                </div>
                <RouterLink :to="{ name: 'Checkout' }" class="cart-checkout">{{ t('cart.checkout') }}</RouterLink>
                <RouterLink :to="{ name: 'ProductList' }" class="cart-secondary">{{ t('cart.continueShopping') }}</RouterLink>
            </aside>
        </div>
    </section>
</template>

<style scoped>
.cart-page {
    min-height: 100vh;
    background: #f3f4f6;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.cart-shell {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    align-items: start;
    gap: 18px;
    max-width: 1480px;
    margin: 0 auto;
    padding: 24px 22px;
}

.cart-main {
    min-width: 0;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
}

.cart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #e5e7eb;
    padding: 20px 22px 16px;
}

.cart-header h1 {
    margin: 0;
    font-size: 26px;
    font-weight: 700;
    line-height: 32px;
}

.cart-header p {
    margin: 4px 0 0;
    color: #565959;
    font-size: 14px;
    line-height: 20px;
}

.cart-header__link {
    flex: none;
    color: #007185;
    font-size: 14px;
    line-height: 20px;
    text-decoration: none;
}

.cart-list {
    display: grid;
}

.cart-item {
    display: grid;
    grid-template-columns: 156px minmax(0, 1fr);
    gap: 18px;
    border-bottom: 1px solid #eef0f2;
    padding: 20px 22px;
}

.cart-item:last-child {
    border-bottom: 0;
}

.cart-item__image {
    display: grid;
    width: 156px;
    height: 156px;
    place-items: center;
    border: 1px solid #edf0f2;
    border-radius: 6px;
    background: #f7f7f7;
    overflow: hidden;
    padding: 12px;
    text-decoration: none;
}

.cart-item__image img {
    display: block;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.cart-item__content {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 18px;
}

.cart-item__top {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 140px;
    gap: 18px;
}

.cart-item__details {
    min-width: 0;
}

.cart-item__name {
    display: -webkit-box;
    overflow: hidden;
    color: #0f1111;
    font-size: 18px;
    font-weight: 600;
    line-height: 25px;
    text-decoration: none;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.cart-item__name:hover,
.cart-header__link:hover,
.cart-secondary:hover,
.cart-remove:hover:not(:disabled) {
    color: #c45500;
    text-decoration: underline;
}

.cart-item__meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.cart-variant {
    display: inline-flex;
    max-width: 100%;
    align-items: center;
    min-height: 24px;
    border: 1px solid #d5d9d9;
    border-radius: 999px;
    background: #fff;
    padding: 3px 9px;
    color: #565959;
    font-size: 13px;
    line-height: 18px;
}

.cart-stock {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    border-radius: 999px;
    background: #eefbf3;
    padding: 3px 9px;
    color: #007600;
    font-size: 13px;
    font-weight: 600;
    line-height: 18px;
}

.cart-stock.is-out {
    background: #fff1f1;
    color: #b42318;
}

.cart-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.cart-quantity {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid #d5d9d9;
    border-radius: 999px;
    background: #ffffff;
    padding: 4px;
    font-size: 13px;
    line-height: 18px;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.cart-quantity > span {
    padding: 0 6px 0 8px;
    color: #565959;
    font-weight: 600;
}

.cart-quantity__button {
    display: grid;
    width: 30px;
    height: 30px;
    flex: none;
    place-items: center;
    border: 1px solid transparent;
    border-radius: 999px;
    background: #f3f4f6;
    color: #111827;
    cursor: pointer;
    font-size: 11px;
    transition:
        background 0.15s ease,
        border-color 0.15s ease,
        color 0.15s ease,
        transform 0.15s ease;
}

.cart-quantity__button:hover{
    transform: translateY(-1px);
}

.cart-quantity__button:focus-visible {
    outline: 2px solid #007185;
    outline-offset: 2px;
}

.cart-quantity input {
    width: 42px;
    height: 30px;
    border: 0;
    background: transparent;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
}

.cart-quantity input:focus {
    outline: 0;
}

.cart-quantity input::-webkit-outer-spin-button,
.cart-quantity input::-webkit-inner-spin-button {
    margin: 0;
    appearance: none;
}

.cart-quantity input[type='number'] {
    appearance: textfield;
}

.cart-remove {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 0;
    background: transparent;
    color: #007185;
    cursor: pointer;
    font-size: 13px;
    line-height: 18px;
}

.cart-remove:disabled,
.cart-quantity input:disabled,
.cart-quantity__button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.cart-item__price {
    text-align: right;
}

.cart-item__price span {
    display: block;
    margin-bottom: 5px;
    color: #6b7280;
    font-size: 12px;
    line-height: 16px;
}

.cart-item__price strong {
    display: block;
    color: #0f1111;
    font-size: 20px;
    font-weight: 700;
    line-height: 26px;
}

.cart-summary {
    position: sticky;
    top: 16px;
    align-self: start;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    padding: 18px;
}

.cart-summary h2 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    line-height: 24px;
}

.cart-summary dl {
    display: grid;
    gap: 8px;
    margin: 14px 0;
}

.cart-summary dl div {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: #565959;
    font-size: 14px;
    line-height: 20px;
}

.cart-summary dd {
    margin: 0;
    color: #0f1111;
    font-weight: 600;
}

.cart-summary__total {
    border-top: 1px solid #e5e7eb;
    padding-top: 14px;
    color: #0f1111;
    font-size: 24px;
    font-weight: 700;
    line-height: 30px;
}

.cart-checkout,
.cart-primary,
.cart-secondary {
    display: block;
    width: 100%;
    border-radius: 999px;
    padding: 10px 16px;
    text-align: center;
    text-decoration: none;
    font-size: 14px;
    line-height: 20px;
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
    margin: 14px 0 18px;
    font-size: 24px;
    font-weight: 700;
    line-height: 30px;
}

.cart-empty__icon {
    display: grid;
    width: 58px;
    height: 58px;
    place-items: center;
    border-radius: 999px;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 24px;
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
        position: static;
    }

    .cart-item {
        grid-template-columns: 120px minmax(0, 1fr);
        padding: 16px;
    }

    .cart-item__image {
        width: 120px;
        height: 120px;
    }

    .cart-item__top {
        display: block;
    }

    .cart-item__price {
        text-align: left;
        margin-top: 12px;
    }

    .cart-item__price span {
        display: none;
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

    .cart-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .cart-item {
        grid-template-columns: 96px minmax(0, 1fr);
        gap: 12px;
        padding: 14px;
    }

    .cart-item__image {
        width: 96px;
        height: 96px;
        padding: 8px;
    }

    .cart-item__name {
        font-size: 15px;
        line-height: 21px;
    }

    .cart-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .cart-quantity {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
