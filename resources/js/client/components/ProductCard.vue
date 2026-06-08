<script>
const shippingFeeCache = new Map();
const shippingFeeRequests = new Map();
</script>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { APP_CONFIG } from '@/config';
import api from '@/services/api';
import { useLocationStore } from '@/stores/location';

const { t } = useI18n();
const locationStore = useLocationStore();

const props = defineProps({
    product: { type: Object, required: true },
    isAdding: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
    badgeText: { type: String, default: '' },
    cartLabel: { type: String, default: '' },
    addingLabel: { type: String, default: '' },
    message: { type: String, default: '' },
    messageType: { type: String, default: 'success' },
});

const emit = defineEmits(['add-to-cart']);
const shippingFee = ref(null);
const isShippingFeeLoading = ref(false);
const shippingFeeError = ref('');
const showCartIncrement = ref(false);
let cartIncrementTimer = null;

const productRoute = computed(() =>
    props.product.slug ? { name: 'ProductDetail', params: { slug: props.product.slug } } : { name: 'ProductList' },
);

const variantId = computed(
    () =>
        props.product.product_variant_id || props.product.default_variant_id || props.product.variant_id || props.product.variants?.[0]?.id,
);

const brandName = computed(() => props.product.brand?.name || APP_CONFIG.appName);

const rating = computed(() => Number(props.product.rating || props.product.average_rating || 4.6).toFixed(1));

const reviewCount = computed(() => {
    const count = Number(props.product.reviews_count || props.product.review_count || props.product.total_reviews || 5500);
    return count >= 1000 ? `${(count / 1000).toFixed(1)}K` : String(count);
});

const formatCount = (value) => {
    const count = Number(value || 0);

    if (count >= 1000) {
        return `${(count / 1000).toFixed(1)}K`;
    }

    return String(count);
};

const totalSold = computed(() => Number(props.product.total_sold || props.product.sold_count || props.product.orders_count || 0));
const soldCountLabel = computed(() => t('productCard.soldCount', { count: formatCount(totalSold.value) }));

const price = computed(() => Number(props.product.price || 0));
const compareAtPrice = computed(() => Number(props.product.compare_at_price || 0));
const hasCompareAtPrice = computed(() => compareAtPrice.value > price.value && price.value > 0);
const discountPercent = computed(() => {
    if (!hasCompareAtPrice.value) {
        return 0;
    }

    return Math.round(((compareAtPrice.value - price.value) / compareAtPrice.value) * 100);
});

const formatPrice = (value) => {
    const amount = Number(value);

    if (!Number.isFinite(amount) || amount <= 0) {
        return t('productCard.contact');
    }

    return new Intl.NumberFormat('en-US', {
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatCurrency = (value) => `${formatPrice(value)}đ`;

const fetchShippingFee = async () => {
    const provinceId = locationStore.currentProvinceId || localStorage.getItem('current_location_province_id');

    shippingFee.value = null;
    shippingFeeError.value = '';

    if (!provinceId) {
        shippingFeeError.value = t('productCard.shippingUnavailable');
        return;
    }

    if (shippingFeeCache.has(provinceId)) {
        shippingFee.value = shippingFeeCache.get(provinceId);
        return;
    }

    isShippingFeeLoading.value = true;

    try {
        if (!shippingFeeRequests.has(provinceId)) {
            shippingFeeRequests.set(
                provinceId,
                api.get('/locations/shipping-fee', {
                    params: {
                        province_id: provinceId,
                        weight: 1000,
                    },
                }),
            );
        }

        const response = await shippingFeeRequests.get(provinceId);
        const fee = response.data?.data?.total ?? null;

        shippingFeeCache.set(provinceId, fee);
        shippingFee.value = fee;

        if (fee === null) {
            shippingFeeError.value = t('productCard.shippingUnavailable');
        }
    } catch (error) {
        shippingFeeError.value = t('productCard.shippingUnavailable');
    } finally {
        shippingFeeRequests.delete(provinceId);
        isShippingFeeLoading.value = false;
    }
};

const shippingFeeLabel = computed(() => {
    if (isShippingFeeLoading.value) {
        return t('productCard.shippingCalculating');
    }

    if (shippingFee.value !== null) {
        return t('productCard.shippingFee', { fee: formatCurrency(shippingFee.value) });
    }

    return shippingFeeError.value || t('productCard.shippingUnavailable');
});

const imageUrl = computed(() => {
    return props.product.thumbnail;
});

const displayBadgeText = computed(() => props.badgeText || t('productCard.badge_new'));
const displayCartLabel = computed(() => props.cartLabel || t('productCard.actions_addToCart'));
const displayAddingLabel = computed(() => props.addingLabel || t('productCard.actions_adding'));
const freeshipLabel = computed(() => t('productCard.badge_freeship'));

const handleAddToCart = () => {
    showCartIncrement.value = false;

    if (cartIncrementTimer) {
        clearTimeout(cartIncrementTimer);
    }

    requestAnimationFrame(() => {
        showCartIncrement.value = true;
    });

    cartIncrementTimer = setTimeout(() => {
        showCartIncrement.value = false;
        cartIncrementTimer = null;
    }, 700);

    emit('add-to-cart', props.product);
};

watch(() => locationStore.currentProvinceId, fetchShippingFee, { immediate: true });

onBeforeUnmount(() => {
    if (cartIncrementTimer) {
        clearTimeout(cartIncrementTimer);
    }
});
</script>

<template>
    <article class="client-product-card" :class="{ 'client-product-card--compact': compact }">
        <div class="client-product-card__media">
            <RouterLink :to="productRoute" class="client-product-card__image">
                <img v-if="imageUrl" :src="imageUrl" :alt="product.name" loading="lazy" />
            </RouterLink>
        </div>

        <div class="client-product-card__body">
            <RouterLink :to="productRoute" class="client-product-card__name">{{ product.name }}</RouterLink>
            <div class="client-product-card__brand tw-text-sm tw-capitalize tw-text-gray-500">
                {{ t('productCard.brandLabel') }}: {{ brandName }}
            </div>
            <div class="client-product-card__rating">
                <span>{{ rating }}</span>
                <span class="client-product-card__stars">★★★★★</span>
                <span class="client-product-card__reviews">({{ reviewCount }})</span>
            </div>

            <div class="client-product-card__meta">{{ soldCountLabel }}</div>

            <div v-if="hasCompareAtPrice" class="client-product-card__discount">
                <span>-{{ discountPercent }}%</span>
                <del> {{ formatPrice(compareAtPrice) }} VND</del>
            </div>

            <div class="client-product-card__price">
                <strong>{{ formatPrice(price) }}</strong>
                <span>VND</span>
            </div>

            <div class="client-product-card__shipping">{{ shippingFeeLabel }}</div>

            <p v-if="message" class="client-product-card__message" :class="{ 'is-error': messageType === 'error' }">
                {{ message }}
            </p>
        </div>

        <button
            type="button"
            class="client-product-card__cart"
            :disabled="!variantId || isAdding"
            :aria-label="isAdding ? displayAddingLabel : displayCartLabel"
            @click="handleAddToCart"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 3h2l2.4 12.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H6"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM18 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                />
            </svg>
            <span v-if="showCartIncrement" class="client-product-card__cart-increment">+1</span>
        </button>
    </article>
</template>

<style scoped>
.client-product-card {
    border-width: 2px;
    border-style: solid;
    border-color: #f3f4f6;
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 5px;
    background: #fff;
    transition:
        box-shadow 0.2s ease,
        transform 0.2s ease;
}

.client-product-card:hover {
    box-shadow: 0 2px 5px rgba(15, 17, 17, 0.12);
    transform: translateY(-2px);
}

.client-product-card__media {
    position: relative;
    display: grid;
    width: 100%;
    height: 230px;
    overflow: hidden;
    place-items: center;
    background: #f7f7f7;
}

.client-product-card__image {
    display: grid;
    width: 100%;
    height: 100%;
    place-items: center;
    line-height: 0;
    text-decoration: none;
}

.client-product-card__image img {
    display: block;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    object-position: center;
}

.client-product-card__ship-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;

    padding: 4px 9px;
    font-size: 11px;
    font-weight: 700;
    line-height: 14px;
    box-shadow: 0 1px 4px rgba(15, 17, 17, 0.1);
}

.client-product-card__body {
    display: flex;
    min-height: 220px;
    flex: 1;
    flex-direction: column;
    padding: 14px;
}

.client-product-card__brand {
    margin-bottom: 3px;
    line-height: 18px;
}

.client-product-card__name {
    display: -webkit-box;
    overflow: hidden;
    color: #0f1111;
    font-size: 15px;
    line-height: 21px;
    text-decoration: none;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.client-product-card__name:hover {
    color: #c45500;
    text-decoration: underline;
}

.client-product-card__rating {
    display: flex;
    align-items: center;
    gap: 3px;
    margin-top: 9px;
    color: #0f1111;
    font-size: 12px;
    line-height: 16px;
}

.client-product-card__stars {
    color: #ff8f00;
    font-size: 13px;
    letter-spacing: 0;
}

.client-product-card__chevron,
.client-product-card__reviews {
    color: #007185;
}

.client-product-card__meta,
.client-product-card__ship {
    color: #565959;
    font-size: 13px;
    line-height: 18px;
}

.client-product-card__discount {
    display: flex;
    align-items: baseline;
    gap: 7px;
    margin-top: 10px;
    min-height: 18px;
}

.client-product-card__discount span {
    color: #cc0c39;
    font-size: 13px;
    font-weight: 700;
}

.client-product-card__discount del {
    color: #565959;
    font-size: 12px;
}

.client-product-card__price {
    display: flex;
    align-items: flex-start;
    gap: 3px;
    margin-top: 6px;
    color: #0f1111;
}

.client-product-card__price span {
    padding-top: 3px;
    color: #565959;
    font-size: 13px;
    font-weight: 600;
    line-height: 16px;
}

.client-product-card__price strong {
    font-size: 22px;
    font-weight: 700;
    line-height: 28px;
}

.client-product-card__shipping {
    margin-top: 3px;
    color: #565959;
    font-size: 12px;
    line-height: 17px;
}

.client-product-card__cart {
    position: absolute;
    right: 12px;
    bottom: 12px;
    z-index: 3;
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border: 0;
    border-radius: 999px;
    background: transparent;
    padding: 0;
    color: #0f172a;
    cursor: pointer;
    opacity: 1;
    pointer-events: auto;
}

.client-product-card__cart svg {
    width: 18px;
    height: 18px;
}

.client-product-card__cart-increment {
    position: absolute;
    left: 50%;
    bottom: 20px;
    color: #007600;
    font-size: 13px;
    font-weight: 700;
    line-height: 1;
    pointer-events: none;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.9);
    transform: translateX(-50%);
    animation: cart-increment-float 0.7s ease-out forwards;
}

.client-product-card__cart:hover:not(:disabled) {
    color: #007185;
}

.client-product-card__cart:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.client-product-card__message {
    margin: 8px 0 0;
    color: #007600;
    font-size: 12px;
    line-height: 1.35;
}

.client-product-card__message.is-error {
    color: #b42318;
}

.client-product-card--compact .client-product-card__media {
    height: 190px;
}

.client-product-card--compact .client-product-card__body {
    min-height: 198px;
    padding: 12px;
}

.client-product-card--compact .client-product-card__price strong {
    font-size: 21px;
    line-height: 27px;
}

@keyframes cart-increment-float {
    0% {
        opacity: 0;
        transform: translate(-50%, 0) scale(0.92);
    }

    18% {
        opacity: 1;
    }

    100% {
        opacity: 0;
        transform: translate(-50%, -24px) scale(1.08);
    }
}

@media (max-width: 560px) {
    .client-product-card__media {
        height: 190px;
    }

    .client-product-card__body {
        min-height: auto;
    }
}
</style>
