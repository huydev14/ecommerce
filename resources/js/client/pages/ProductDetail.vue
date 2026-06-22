<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import api from '@/services/api';
import { APP_CONFIG } from '@/config';
import { useCartStore } from '@/stores/cart';
import { useLocationStore } from '@/stores/location';

const route = useRoute();
const cartStore = useCartStore();
const locationStore = useLocationStore();
const { t } = useI18n();

const product = ref(null);
const isLoading = ref(false);
const errorMessage = ref('');
const selectedImage = ref('');
const selectedVariantId = ref(null);
const quantity = ref(1);
const isAddingToCart = ref(false);
const cartMessage = ref('');
const cartError = ref('');
const shippingFee = ref(null);
const isShippingFeeLoading = ref(false);
const shippingFeeError = ref('');

const variants = computed(() => product.value?.variants || []);
const selectedVariant = computed(
    () => variants.value.find((variant) => variant.id === selectedVariantId.value) || variants.value[0] || null,
);
const activePrice = computed(() => selectedVariant.value?.price || product.value?.price || 0);
const brandName = computed(() => product.value?.brand?.name || APP_CONFIG.appName);
const brandSlug = computed(() => product.value?.brand?.slug || '');
const categoryName = computed(() => product.value?.category?.name || t('productDetail.fallback_category'));
const totalSold = computed(() => Number(product.value?.total_sold || product.value?.sold_count || 0));
const deliveryLocationName = computed(
    () => locationStore.currentLocationName || localStorage.getItem('current_location_name') || t('productDetail.fallback_location'),
);
const productImages = computed(() => {
    const image = product.value?.thumbnail;

    return image ? [image, image, image, image] : [];
});

const formatCount = (value) => {
    const count = Number(value || 0);

    if (count >= 1000) {
        return `${(count / 1000).toFixed(1)}K`;
    }

    return String(count);
};

const soldCountLabel = computed(() => t('productDetail.boughtInPastMonth', { count: formatCount(totalSold.value) }));

const getVariantLabel = (variant, fallback = t('productDetail.fallback_defaultVariant')) => {
    const attributes = variant?.attributes || {};

    return attributes.variant_name || attributes.name || attributes.size || attributes.color || variant?.sku || fallback;
};

const variantLabels = computed(() => {
    return variants.value.map((variant, index) => ({
        id: variant.id,
        label: getVariantLabel(variant, t('productDetail.fallback_option', { number: index + 1 })),
        price: variant.price,
    }));
});
const descriptionItems = computed(() => {
    const text = product.value?.description || '';
    const normalized = text
        .replace(/<[^>]*>/g, '')
        .split(/\r?\n|\. /)
        .map((item) => item.trim())
        .filter(Boolean);

    return normalized.length ? normalized.slice(0, 5) : [t('productDetail.fallback_noDescription')];
});
const specifications = computed(() => [
    [t('productDetail.specs_brand'), brandName.value],
    [t('productDetail.specs_category'), categoryName.value],
    ['SKU', selectedVariant.value?.sku || t('productDetail.fallback_updating')],
    [t('productDetail.specs_variant'), getVariantLabel(selectedVariant.value)],
]);

const fetchProduct = async () => {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get(`/products/${route.params.slug}`);

        if (response.data?.success) {
            product.value = response.data.data;
            selectedImage.value = response.data.data.thumbnail || '';
            selectedVariantId.value = response.data.data.variants?.[0]?.id || null;
            fetchShippingFee();
        } else {
            product.value = null;
            errorMessage.value = t('productDetail.errors_fetchProduct');
        }
    } catch (error) {
        product.value = null;
        errorMessage.value = t('productDetail.errors_notFound');
        console.error(t('productDetail.errors_fetchProductLog'), error);
    } finally {
        isLoading.value = false;
    }
};

const formatCurrency = (price) => {
    const numericPrice = Number(price || 0);

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(numericPrice);
};

const formatPrice = (price) => {
    const numericPrice = Number(price || 0);

    if (!numericPrice) {
        return t('productDetail.contact');
    }

    return formatCurrency(numericPrice);
};

const fetchShippingFee = async () => {
    const provinceId = locationStore.currentProvinceId || localStorage.getItem('current_location_province_id');

    shippingFee.value = null;
    shippingFeeError.value = '';

    if (!provinceId) {
        shippingFeeError.value = t('productDetail.shippingUnavailable');
        return;
    }

    isShippingFeeLoading.value = true;

    try {
        const response = await api.get('/locations/shipping-fee', {
            params: {
                province_id: provinceId,
                weight: 1000,
            },
        });

        shippingFee.value = response.data?.data?.total ?? null;

        if (shippingFee.value === null) {
            shippingFeeError.value = t('productDetail.shippingUnavailable');
        }
    } catch (error) {
        shippingFeeError.value = t('productDetail.shippingUnavailable');
        console.error(t('productDetail.errors_fetchShippingFeeLog'), error);
    } finally {
        isShippingFeeLoading.value = false;
    }
};

const shippingFeeLabel = computed(() => {
    if (isShippingFeeLoading.value) {
        return t('productDetail.shippingCalculating');
    }

    if (shippingFee.value !== null) {
        return formatCurrency(shippingFee.value);
    }

    return shippingFeeError.value || t('productDetail.shippingUnavailable');
});

const addToCart = async () => {
    cartMessage.value = '';
    cartError.value = '';

    if (!selectedVariant.value?.id) {
        cartError.value = t('productDetail.errors_selectVariant');
        return;
    }

    isAddingToCart.value = true;

    try {
        await cartStore.addItem(selectedVariant.value.id, Number(quantity.value || 1));
        cartMessage.value = t('productDetail.messages_addedToCart');
    } catch (error) {
        cartError.value = error.response?.data?.message || t('productDetail.errors_addToCart');
    } finally {
        isAddingToCart.value = false;
    }
};

onMounted(fetchProduct);

watch(() => route.params.slug, fetchProduct);

watch(() => locationStore.currentProvinceId, fetchShippingFee);
</script>

<template>
    <section class="pdp-page">
        <div v-if="isLoading" class="pdp-state">{{ t('productDetail.loading') }}</div>
        <div v-else-if="errorMessage" class="pdp-state is-error">{{ errorMessage }}</div>

        <div v-else-if="product" class="pdp-shell">
            <aside class="pdp-gallery" :aria-label="t('productDetail.aria_images')">
                <div class="pdp-gallery__main">
                    <img v-if="selectedImage" :src="selectedImage" :alt="product.name" />
                </div>

                <div class="pdp-gallery__thumbs">
                    <button
                        v-for="(image, index) in productImages"
                        :key="`${image}-${index}`"
                        type="button"
                        class="pdp-thumb"
                        :class="{ 'is-active': selectedImage === image && index === 0 }"
                        @click="selectedImage = image"
                    >
                        <img :src="image" :alt="`${product.name} ${index + 1}`" />
                    </button>
                </div>

                <a href="#" class="pdp-gallery__link">{{ t('productDetail.fullView') }}</a>
            </aside>

            <div class="pdp-details">
                <div class="pdp-rating-row">
                    <span class="rating-score">4.9</span>
                    <span class="pdp-stars">★★★★★</span>
                    <a href="#" class="rating-reviews">{{ t('productDetail.ratings', { count: 67 }) }}</a>
                </div>

                <RouterLink v-if="brandSlug" class="pdp-store" :to="{ name: 'ProductList', query: { brand: brandSlug } }">
                    {{ t('productDetail.visitStore', { brand: brandName }) }}
                </RouterLink>
                <span v-else class="pdp-store">{{ t('productDetail.visitStore', { brand: brandName }) }}</span>
                <h1 class="pdp-title">{{ product.name }}</h1>
                <p class="pdp-short-desc">
                    {{ descriptionItems[0] || t('productDetail.fallback_noDescription') }}
                </p>

                <section v-if="variantLabels.length" class="pdp-options">
                    <h3 class="pdp-options-title">{{ t('productDetail.options') }}</h3>
                    <div class="pdp-color-grid">
                        <button
                            v-for="variant in variantLabels"
                            :key="variant.id"
                            type="button"
                            class="pdp-color-swatch"
                            :class="{ 'is-selected': selectedVariantId === variant.id }"
                            @click="selectedVariantId = variant.id"
                        >
                            {{ variant.label }}
                        </button>
                    </div>
                </section>

                <div class="pdp-accordions">
                    <details class="pdp-accordion" open>
                        <summary>Details <span class="icon-plus">+</span></summary>
                        <div class="accordion-content">
                            <ul>
                                <li v-for="item in descriptionItems" :key="item">{{ item }}</li>
                            </ul>
                        </div>
                    </details>
                    <details class="pdp-accordion" open>
                        <summary>Dimension <span class="icon-plus">+</span></summary>
                        <div class="accordion-content">
                            <dl class="pdp-specs">
                                <template v-if="selectedVariant?.unit">
                                    <dt>Unit</dt>
                                    <dd>{{ selectedVariant.unit }}</dd>
                                </template>
                                <template v-for="[label, value] in specifications" :key="label">
                                    <dt>{{ label }}</dt>
                                    <dd>{{ value }}</dd>
                                </template>
                            </dl>
                        </div>
                    </details>
                    <details class="pdp-accordion" open>
                        <summary>Shipping & Returns <span class="icon-plus">+</span></summary>
                        <div class="accordion-content">
                            <p>Shipper: {{ APP_CONFIG.appName }}</p>
                            <p>Free Returns within 60 days.</p>
                        </div>
                    </details>
                </div>
            </div>

            <div class="pdp-buybox">
                <div class="pdp-price-row">
                    <span class="pdp-price-current">{{ formatPrice(activePrice) }}</span>
                    <div class="pdp-price-old-group" v-if="selectedVariant?.compare_at_price">
                        <span class="pdp-price-old">{{ formatPrice(selectedVariant.compare_at_price) }}</span>
                        <div class="pdp-price-discount">
                            <span class="discount-value">-{{ Math.round((1 - activePrice / selectedVariant.compare_at_price) * 100) }}%</span>
                        </div>
                    </div>
                </div>

                <div class="pdp-shipping-info">
                    <h3 class="pdp-shipping-title">Standard Shipping (GHN)</h3>
                    <div class="pdp-shipping-details">
                        <svg class="icon-shipping" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        <div class="pdp-shipping-text">
                            <p class="pdp-delivery-est" v-html="t('productDetail.delivery', { date: 'Aug 22-Aug 25' })"></p>
                            <p class="pdp-delivery-location">
                                {{ t('productDetail.deliverTo', { location: deliveryLocationName }) }}
                            </p>
                            <p class="pdp-delivery-fee">
                                {{ t('productDetail.shippingFee', { fee: shippingFeeLabel }) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pdp-action-row">
                    <div class="pdp-qty-stepper">
                        <button type="button" @click="quantity > 1 ? quantity-- : null">-</button>
                        <input type="number" v-model="quantity" min="1" />
                        <button type="button" @click="quantity++">+</button>
                    </div>
                    
                    <button type="button" class="pdp-add-cart" :disabled="isAddingToCart" @click="addToCart">
                        {{ isAddingToCart ? t('productCard.actions_adding') : 'Add to cart' }}
                        <svg class="icon-bag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                    </button>
                </div>

                <p v-if="cartMessage" class="pdp-cart-message">{{ cartMessage }}</p>
                <p v-if="cartError" class="pdp-cart-message is-error">{{ cartError }}</p>

              

                <div class="pdp-confidence">
                    <h4>Buy with confidence</h4>
                    <div class="confidence-grid">
                        <div class="conf-item">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.514" /></svg>
                            Best Price Guaranteed
                        </div>
                        <div class="conf-item">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            60-Day Returns
                        </div>
                        <div class="conf-item">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            3-Year Warranty
                        </div>
                        <div class="conf-item">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Fully Assembled Design
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.pdp-page {
    min-height: 100vh;
    background: #ffffff;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.pdp-shell {
    display: grid;
    grid-template-columns: minmax(600px, 1fr) minmax(280px, 1fr) minmax(280px, 1fr);
    gap: 32px;
    max-width: 1500px;
    margin: 0 auto;
    padding: 32px 24px 64px;
}

.pdp-gallery {
    position: sticky;
    top: 24px;
    align-self: start;
}

.pdp-gallery__main {
    display: grid;
    place-items: center;
    background: #f9fafb;
    border-radius: 16px;
    overflow: hidden;
    aspect-ratio: 1;
}

.pdp-gallery__main img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 20px;
}

.pdp-gallery__thumbs {
    display: flex;
    gap: 12px;
    margin-top: 16px;
    overflow-x: auto;
}

.pdp-thumb {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
    border: 2px solid transparent;
    border-radius: 12px;
    background: #f9fafb;
    cursor: pointer;
    overflow: hidden;
    transition: border-color 0.2s;
}

.pdp-thumb.is-active {
    border-color: #111827;
}

.pdp-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
}

.pdp-gallery__link {
    display: block;
    margin-top: 16px;
    text-align: center;
    font-size: 14px;
    color: #4b5563;
    text-decoration: underline;
}

/* Info Columns */
.pdp-details, .pdp-buybox {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #111827;
}

.pdp-buybox {
    padding: 24px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    align-self: start;
    position: sticky;
    top: 24px;
}

.pdp-rating-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 500;
}

.pdp-stars {
    color: #f90;
    font-size: 12px;
}

.rating-reviews {
    color: #6b7280;
    text-decoration: underline;
    margin-left: 4px;
    font-weight: 400;
}

.pdp-store {
    display: block;
    font-size: 14px;
    color: #007185;
    text-decoration: none;
    margin-top: 16px;
    margin-bottom: -4px;
}
.pdp-store:hover {
    text-decoration: underline;
}

.pdp-title {
    font-size: 32px;
    font-weight: 600;
    margin: 12px 0 16px;
    line-height: 1.2;
}

.pdp-short-desc {
    color: #4b5563;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 24px;
}

.pdp-price-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    margin-bottom: 24px;
}

.pdp-price-current {
    font-size: 38px;
    font-weight: 700;
    color: #e55a3b;
    line-height: 1;
}

.pdp-price-old-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pdp-price-old {
    font-size: 16px;
    color: #9ca3af;
    text-decoration: line-through;
    font-weight: 400;
}

.pdp-price-discount {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fee2e2;
    color: #ef4444;
    padding: 2px 8px;
    border-radius: 4px;
}

.pdp-price-discount .discount-value {
    font-size: 13px;
    font-weight: 600;
}

.pdp-options-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
}

.pdp-color-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 24px;
}

.pdp-color-swatch {
    padding: 8px 16px;
    border: 1px solid #d1d5db;
    border-radius: 24px;
    background: #ffffff;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.pdp-color-swatch.is-selected {
    border-color: #111827;
    background: #111827;
    color: #ffffff;
}

.pdp-shipping-info {
    margin-bottom: 24px;
}

.pdp-shipping-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
}

.pdp-shipping-details {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.icon-shipping {
    width: 20px;
    height: 20px;
    color: #6b7280;
    flex-shrink: 0;
    margin-top: 2px;
}

.pdp-shipping-text {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.pdp-delivery-est,
.pdp-delivery-location,
.pdp-delivery-fee {
    font-size: 14px;
    color: #4b5563;
    margin: 0;
    line-height: 1.4;
}

.pdp-action-row {
    display: flex;
    align-items: stretch;
    gap: 16px;
    margin-bottom: 20px;
}

.pdp-qty-stepper {
    display: flex;
    align-items: center;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    width: 120px;
    height: 52px;
    flex-shrink: 0;
}

.pdp-qty-unit {
    font-size: 15px;
    color: #4b5563;
    white-space: nowrap;
}

.pdp-qty-stepper button {
    width: 40px;
    height: 100%;
    background: transparent;
    border: none;
    font-size: 20px;
    color: #4b5563;
    cursor: pointer;
}

.pdp-qty-stepper input {
    width: 40px;
    height: 100%;
    border: none;
    text-align: center;
    font-size: 16px;
    font-weight: 500;
    color: #111827;
    -moz-appearance: textfield;
}
.pdp-qty-stepper input::-webkit-inner-spin-button, 
.pdp-qty-stepper input::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}

.pdp-add-cart {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #111827;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.pdp-add-cart:hover {
    background: #374151;
}

.pdp-add-cart:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.icon-bag {
    width: 20px;
    height: 20px;
}

.pdp-confidence {
    background: #f9fafb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
}

.pdp-confidence h4 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 16px;
}

.confidence-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.conf-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #4b5563;
    font-weight: 500;
}

.conf-item .icon {
    width: 18px;
    height: 18px;
    color: #6b7280;
    flex-shrink: 0;
}

.pdp-accordions {
    border-top: 1px solid #e5e7eb;
}

.pdp-accordion {
    border-bottom: 1px solid #e5e7eb;
}

.pdp-accordion summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    list-style: none;
}

.pdp-accordion summary::-webkit-details-marker {
    display: none;
}

.pdp-accordion .icon-plus {
    font-size: 20px;
    color: #9ca3af;
    font-weight: 400;
    transition: transform 0.2s;
}

.pdp-accordion[open] .icon-plus {
    transform: rotate(45deg);
}

.accordion-content {
    padding-bottom: 20px;
    font-size: 15px;
    color: #4b5563;
    line-height: 1.6;
}

.accordion-content ul {
    margin: 0;
    padding-left: 20px;
}

.pdp-specs {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 12px;
}

.pdp-specs dt {
    font-weight: 600;
    color: #111827;
}

.pdp-specs dd {
    margin: 0;
}

.pdp-cart-message {
    margin: -10px 0 20px;
    color: #059669;
    font-size: 14px;
    font-weight: 500;
}

.pdp-cart-message.is-error {
    color: #dc2626;
}

@media (max-width: 900px) {
    .pdp-shell {
        grid-template-columns: 1fr;
        padding: 24px 16px;
        gap: 32px;
    }

    .pdp-gallery {
        position: static;
    }
}
</style>
