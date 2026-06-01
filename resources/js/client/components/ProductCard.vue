<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { APP_CONFIG } from '@/config';

const { t } = useI18n();

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    isAdding: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },

    badgeText: {
        type: String,
        default: '',
    },
    cartLabel: {
        type: String,
        default: '',
    },
    addingLabel: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    messageType: {
        type: String,
        default: 'success',
    },
});

const emit = defineEmits(['add-to-cart']);

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

const boughtCount = computed(() => {
    const count = Number(props.product.sold_count || props.product.orders_count || props.product.total_sold || 500);
    return `${count}+ bought in past month`;
});

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

const imageUrl = computed(() => {
    const thumbnail = props.product.thumbnail;

    if (!thumbnail) {
        return '/img/default-image.jpg';
    }

    if (/^https?:\/\//i.test(thumbnail) || thumbnail.startsWith('/')) {
        return thumbnail;
    }

    return `/storage/${thumbnail}`;
});

const displayBadgeText = computed(() => props.badgeText || t('productCard.badge_new'));
const displayCartLabel = computed(() => props.cartLabel || t('productCard.actions_addToCart'));
const displayAddingLabel = computed(() => props.addingLabel || t('productCard.actions_adding'));
const freeshipLabel = computed(() => t('productCard.badge_freeship'));
</script>

<template>
    <article class="client-product-card" :class="{ 'client-product-card--compact': compact }">
    

        <div class="client-product-card__media">
            <RouterLink :to="productRoute" class="client-product-card__image">
                <img :src="imageUrl" :alt="product.name" loading="lazy" />
            </RouterLink>
        </div>

        <div class="client-product-card__body">
            <RouterLink :to="productRoute" class="client-product-card__name">{{ product.name }}</RouterLink>
            <div class="client-product-card__brand tw-capitalize tw-text-sm tw-text-gray-500">Brand: {{ brandName }}</div>
            <div class="client-product-card__rating">
                <span>{{ rating }}</span>
                <span class="client-product-card__stars">★★★★★</span>
                <span class="client-product-card__reviews">({{ reviewCount }})</span>
            </div>

            <div class="client-product-card__meta">{{ boughtCount }}</div>

            <div v-if="hasCompareAtPrice" class="client-product-card__discount">
                <span>-{{ discountPercent }}%</span>
                <del>VND {{ formatPrice(compareAtPrice) }}</del>
            </div>

            <div class="client-product-card__price">
                <span>VND</span>
                <strong>{{ formatPrice(price) }}</strong>
            </div>

            <p v-if="message" class="client-product-card__message" :class="{ 'is-error': messageType === 'error' }">
                {{ message }}
            </p>
        </div>

        <button
            type="button"
            class="client-product-card__cart"
            :disabled="!variantId || isAdding"
            :aria-label="isAdding ? displayAddingLabel : displayCartLabel"
            @click="emit('add-to-cart', product)"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 3h2l2.4 12.2a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L21 8H6"
                />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM18 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />
            </svg>
        </button>
    </article>
</template>

<style scoped>
.client-product-card {
    position: relative;
    display: flex;
    min-width: 0;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #f0f2f2;
    border-radius: 2px;
    background: #fff;
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        transform 0.2s ease;
}

.client-product-card:hover {
    border-color: #d5d9d9;
    box-shadow: 0 8px 24px rgba(15, 17, 17, 0.12);
    transform: translateY(-2px);
}

.client-product-card__media {
    position: relative;
    width: 100%;
    height: 230px;
    overflow: hidden;
    background: #f7f7f7;
}

.client-product-card__image {
    display: grid;
    width: 100%;
    height: 100%;
    place-items: center;
    padding: 16px;
    text-decoration: none;
}

.client-product-card__image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
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

.client-product-card--compact .client-product-card__image {
    padding: 14px;
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

@media (max-width: 560px) {
    .client-product-card__media {
        height: 190px;
    }

    .client-product-card__body {
        min-height: auto;
    }
}
</style>
