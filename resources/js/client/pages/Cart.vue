<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCartStore } from '@/stores/cart';

const { t } = useI18n();
const cartStore = useCartStore();
const updatingItems = ref(new Set());

const hasItems = computed(() => cartStore.items.length > 0);

const cartItemsGroupedByBrand = computed(() => {
    const groups = {};
    cartStore.items.forEach((item) => {
        const brand = item.brand_name || 'No Brand';
        if (!groups[brand]) {
            groups[brand] = [];
        }
        groups[brand].push(item);
    });
    return groups;
});

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

const deliveryFee = 30000;
const orderTotal = computed(() => cartStore.subtotal + (cartStore.subtotal > 0 ? deliveryFee : 0));

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
                    <div class="cart-list tw-flex tw-flex-col tw-gap-6">
                        <div v-for="(items, brand) in cartItemsGroupedByBrand" :key="brand" class="tw-flex tw-flex-col tw-gap-4">
                            <h2
                                class="tw-m-0 tw-border-y tw-border-gray-200 tw-bg-gray-50 tw-px-4 tw-py-3 tw-text-[14px] tw-font-bold tw-uppercase tw-text-gray-800"
                            >
                                {{ brand }}
                            </h2>
                            <article v-for="item in items" :key="item.product_variant_id" class="cart-item">
                                <RouterLink
                                    :to="
                                        item.product_slug
                                            ? { name: 'ProductDetail', params: { slug: item.product_slug } }
                                            : { name: 'ProductList' }
                                    "
                                    class="cart-item__image tw-border tw-border-gray-200"
                                >
                                    <img v-if="item.thumbnail" :src="item.thumbnail" :alt="item.product_name" />
                                </RouterLink>

                                <div class="tw-flex tw-w-full tw-min-w-0 tw-flex-row tw-justify-between">
                                    <div class="tw-flex tw-min-w-0 tw-flex-col tw-justify-between">
                                        <div class="cart-item__details tw-min-w-0">
                                            <RouterLink
                                                :to="
                                                    item.product_slug
                                                        ? { name: 'ProductDetail', params: { slug: item.product_slug } }
                                                        : { name: 'ProductList' }
                                                "
                                                class="cart-item__name tw-text-base"
                                            >
                                                {{ item.product_name }}
                                            </RouterLink>
                                            <p v-if="item.brand_name" class="tw-m-0 tw-text-sm tw-capitalize tw-text-gray-500">
                                                Brand: {{ item.brand_name }}
                                            </p>
                                            <div class="cart-item__meta tw-flex tw-flex-wrap tw-items-center tw-gap-2">
                                                <span v-if="item.variant_name" class="cart-variant tw-block">{{ item.variant_name }}</span>
                                            </div>
                                        </div>

                                        <div class="tw-mt-4 tw-text-left">
                                            <div class="tw-flex tw-items-end tw-gap-2">
                                                <strong class="tw-text-[20px] tw-font-bold tw-text-gray-900">{{
                                                    formatPrice(item.line_total)
                                                }}</strong>
                                                <span
                                                    v-if="item.compare_at_price"
                                                    class="tw-mb-[3px] tw-text-[12px] tw-font-normal tw-text-gray-400 tw-line-through"
                                                >
                                                    {{ formatPrice(item.compare_at_price * item.quantity) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tw-ml-4 tw-flex tw-shrink-0 tw-flex-col tw-items-end tw-justify-between">
                                        <button
                                            type="button"
                                            class="tw--mr-2 tw--mt-2 tw-p-2 tw-text-red-500 tw-transition-colors hover:tw-text-red-700"
                                            @click="removeItem(item)"
                                        >
                                            <i class="fa-solid fa-trash-can tw-text-[18px]" aria-hidden="true"></i>
                                        </button>

                                        <div>
                                            <p v-if="item.unit_name" class="tw-block tw-text-xs tw-text-gray-500 tw-text-center">
                                                Đơn vị: {{ item.unit_name }}
                                            </p>
                                            <label class="cart-quantity tw-mt-2">
                                                <button
                                                    type="button"
                                                    class="cart-quantity__button hover:tw-bg-yellow-500"
                                                    :disabled="Number(item.quantity || 1) <= 1"
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
                                                    @change="updateQuantity(item, $event)"
                                                />
                                                <button
                                                    type="button"
                                                    class="cart-quantity__button hover:tw-bg-yellow-500"
                                                    :disabled="Number(item.quantity || 1) >= Number(item.max_stock || 1)"
                                                    :aria-label="`Increase ${item.product_name}`"
                                                    @click="changeQuantity(item, 1)"
                                                >
                                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                </button>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="cart-mobile-subtotal">
                        {{ t('cart.subtotalWithCount', { count: cartStore.totalItems }) }}:
                        <strong>{{ formatPrice(cartStore.subtotal) }}</strong>
                    </div>
                </template>
            </main>

            <aside v-if="hasItems" class="cart-summary">
                <div class="cart-summary__panel tw-mb-4">
                    <h2 class="tw-mb-4">{{ t('cart.orderSummary') }}</h2>
                    <dl class="tw-flex tw-flex-col tw-gap-3">
                        <div class="tw-flex tw-justify-between">
                            <dt class="tw-text-gray-500">{{ t('cart.subtotalWithCount', { count: cartStore.totalItems }) }}</dt>
                            <dd class="tw-font-bold tw-text-gray-900">{{ formatPrice(cartStore.subtotal) }}</dd>
                        </div>
                        <div class="tw-flex tw-justify-between tw-pb-4 tw-border-b tw-border-gray-200">
                            <dt class="tw-text-gray-500">{{ t('cart.deliveryFee') }}</dt>
                            <dd class="tw-font-bold tw-text-gray-900">{{ formatPrice(deliveryFee) }}</dd>
                        </div>
                    </dl>
                    <div class="cart-summary__total tw-flex tw-justify-between tw-items-center tw-mt-4">
                        <span class="tw-text-lg tw-font-bold tw-text-gray-900">{{ t('cart.total') }}</span>
                        <span class="tw-text-[24px] tw-font-bold tw-text-gray-900">{{ formatPrice(orderTotal) }}</span>
                    </div>
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
    max-width: 1200px;
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
    border-radius: 4px;
    background: #f7f7f7;
    overflow: hidden;
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
    overflow: hidden;
    color: #0f1111;
    font-size: 18px;
    font-weight: 600;
    line-height: 25px;
    text-decoration: none;
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

.cart-quantity__button:hover {
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
