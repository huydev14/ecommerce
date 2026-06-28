<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { APP_CONFIG } from '@/config';
import { useCartStore } from '@/stores/cart';
import { useLocationStore } from '@/stores/location';

const route = useRoute();
const router = useRouter();
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
const isBuyingNow = ref(false);
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
    cartError.value = '';

    if (!selectedVariant.value?.id) {
        cartError.value = t('productDetail.errors_selectVariant');
        return;
    }

    isAddingToCart.value = true;

    try {
        await cartStore.addItem(selectedVariant.value.id, Number(quantity.value || 1));
    } catch (error) {
        cartError.value = error.response?.data?.message || t('productDetail.errors_addToCart');
    } finally {
        isAddingToCart.value = false;
    }
};

const buyNow = async () => {
    cartError.value = '';

    if (!selectedVariant.value?.id) {
        cartError.value = t('productDetail.errors_selectVariant');
        return;
    }

    isBuyingNow.value = true;

    try {
        await cartStore.addItem(selectedVariant.value.id, Number(quantity.value || 1));
        router.push({ name: 'Cart' });
    } catch (error) {
        cartError.value = error.response?.data?.message || t('productDetail.errors_addToCart');
        isBuyingNow.value = false;
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
                <h1 class="pdp-title !tw-text-[24px] !tw-font-medium !tw-mb-1 !tw-mt-0 !tw-leading-[1.3]">{{ product.name }}</h1>

                <RouterLink v-if="brandSlug" class="pdp-store !tw-mt-0 !tw-mb-2 !tw-text-[14px]" :to="{ name: 'ProductList', query: { brand: brandSlug } }">
                    {{ t('productDetail.visitStore', { brand: brandName }) }}
                </RouterLink>
                <span v-else class="pdp-store !tw-mt-0 !tw-mb-2 !tw-text-[14px]">{{ t('productDetail.visitStore', { brand: brandName }) }}</span>

                <div class="pdp-rating-row tw-mb-3 tw-flex tw-items-center">
                    <span class="rating-score tw-text-sm">4.9</span>
                    <span class="pdp-stars">★★★★★</span>
                    <span class="tw-text-[#007185] hover:tw-underline tw-cursor-pointer tw-mx-1 tw-flex tw-items-center tw-text-sm">
                        <svg viewBox="0 0 1024 1024" class="tw-w-3 tw-h-3 tw-ml-0.5 tw-mr-1" version="1.1" xmlns="http://www.w3.org/2000/svg"><path d="M903.232 256l56.768 50.432L512 768 64 306.432 120.768 256 512 659.072z" fill="currentColor"/></svg>
                        {{ t('productDetail.ratings', { count: 67 }) }}
                    </span>
                    <span class="tw-text-gray-400 tw-mx-1">|</span>
                    <a href="#" class="rating-reviews tw-text-[#007185] hover:tw-underline tw-text-sm !tw-ml-0">Search this page</a>
                </div>
                
                <div class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-mb-4" v-html="soldCountLabel"></div>
                
                <hr class="tw-border-gray-300 tw-mb-4" />

                <div class="tw-mb-4 tw-flex tw-flex-col">
                    <span class="tw-text-3xl tw-font-medium tw-text-black">{{ formatPrice(activePrice) }}</span>
                    <div class="tw-mt-2 tw-text-sm tw-text-gray-600">
                        Giá trên đã bao gồm thuế
                    </div>
                </div>

                <section v-if="variantLabels.length" class="pdp-options tw-mb-5">
                    <h3 class="pdp-options-title tw-text-[15px] tw-font-normal tw-text-gray-900 tw-mb-3">Size: <span class="tw-font-bold">{{ getVariantLabel(selectedVariant) }}</span></h3>
                    <div class="pdp-color-grid tw-gap-2">
                        <button
                            v-for="variant in variantLabels"
                            :key="variant.id"
                            type="button"
                            class="pdp-color-swatch !tw-rounded !tw-border !tw-border-gray-400 !tw-flex !tw-flex-col !tw-items-start !tw-px-3 !tw-py-2 hover:!tw-bg-gray-50"
                            :class="selectedVariantId === variant.id ? '!tw-border-[#007185] !tw-bg-[#f0f8fa] !tw-shadow-[0_0_0_1px_#007185]' : ''"
                            @click="selectedVariantId = variant.id"
                        >
                            <span class="tw-font-bold tw-text-black">{{ variant.label }}</span>
                            <span class="tw-text-[#B12704] tw-text-[13px] tw-font-medium">{{ formatPrice(variant.price) }}</span>
                        </button>
                    </div>
                </section>

                <div class="tw-mb-5">
                    <dl class="pdp-specs tw-text-sm !tw-gap-y-2">
                        <template v-if="selectedVariant?.unit">
                            <dt class="tw-text-black">Unit</dt>
                            <dd class="tw-text-gray-700">{{ selectedVariant.unit }}</dd>
                        </template>
                        <template v-for="[label, value] in specifications" :key="label">
                            <dt class="tw-text-black">{{ label }}</dt>
                            <dd class="tw-text-gray-700">{{ value }}</dd>
                        </template>
                    </dl>
                </div>
                <hr class="tw-border-gray-300 tw-mb-4" />

                <div class="tw-mb-5">
                    <h3 class="tw-text-base tw-font-bold tw-text-gray-900 tw-mb-2">About this item</h3>
                    <ul class="tw-list-disc tw-pl-5 tw-text-sm tw-text-gray-900">
                        <li v-for="item in descriptionItems" :key="item" class="tw-mb-1">{{ item }}</li>
                    </ul>
                </div>
            </div>

            <div class="pdp-buybox">
                <div class="pdp-price-row">
                    <span class="tw-text-2xl tw-font-bold tw-text-red-600">{{ formatPrice(activePrice) }}</span>
                    <div class="pdp-price-old-group" v-if="selectedVariant?.compare_at_price">
                        <span class="pdp-price-old tw-text-sm">{{ formatPrice(selectedVariant.compare_at_price) }}</span>
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

                <div class="tw-text-[#007600] tw-text-lg tw-mb-4 tw-font-medium">
                    {{ selectedVariant?.stock_quantity === 0 ? 'Out of Stock' : 'In Stock' }}
                </div>

                <div class="pdp-action-row tw-flex-col !tw-gap-3">
                    <div class="tw-flex tw-items-center tw-gap-3">
                        <span class="tw-text-sm tw-text-gray-700 tw-font-medium">Quantity:</span>
                        <div class="pdp-qty-stepper !tw-h-9 !tw-w-auto">
                            <button type="button" @click="quantity > 1 ? quantity-- : null">-</button>
                            <input type="number" v-model="quantity" min="1" class="!tw-w-10" />
                            <button type="button" @click="quantity++">+</button>
                        </div>
                    </div>
                    
                    <button type="button" class="pdp-add-cart !tw-rounded-full !tw-border-none tw-bg-[#ffd814] tw-transition-colors hover:tw-bg-[#f7ca00] !tw-py-2.5 !tw-text-[15px]" :disabled="isAddingToCart" @click="addToCart">
                        {{ isAddingToCart ? t('productCard.actions_adding') : 'Add to cart' }}
                    </button>

                    <button type="button" class="pdp-add-cart !tw-rounded-full !tw-border-none tw-bg-[#ffa41c] tw-transition-colors hover:tw-bg-[#fa8900] !tw-py-2.5 !tw-text-[15px]" :disabled="isBuyingNow" @click="buyNow">
                        {{ isBuyingNow ? t('productCard.actions_adding') : 'Buy Now' }}
                    </button>
                </div>

                <div class="pdp-seller-info tw-mt-5 tw-text-[13px] tw-grid tw-grid-cols-[70px_1fr] tw-gap-y-1.5 tw-gap-x-3">
                    <span class="tw-text-gray-500">Ships from</span>
                    <span class="tw-text-gray-900">{{ APP_CONFIG.appName }}</span>
                    
                    <span class="tw-text-gray-500">Sold by</span>
                    <RouterLink v-if="brandSlug" :to="{ name: 'ProductList', query: { brand: brandSlug } }" class="tw-text-[#007185] hover:tw-underline tw-cursor-pointer">
                        {{ brandName }}
                    </RouterLink>
                    <span v-else class="tw-text-[#007185] hover:tw-underline tw-cursor-pointer">{{ brandName }}</span>
                    
                    <span class="tw-text-gray-500">Returns</span>
                    <span class="tw-text-[#007185] hover:tw-underline tw-cursor-pointer">30-day refund / replacement</span>
                </div>

                <p v-if="cartError" class="pdp-cart-message is-error">{{ cartError }}</p>
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
    grid-template-columns: minmax(570px, 1fr) minmax(510px, 1fr) minmax(150px, 1fr);
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
    border-radius:4px;
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
    font-size: 26px;
    font-weight: 600;
    margin: 12px 0 16px;
    line-height: 1.2;
}

.pdp-price-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    margin-bottom: 15px;
}

.pdp-price-old-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pdp-price-old {
    color: #9ca3af;
    text-decoration: line-through;
    font-weight: 400;
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
}

.pdp-qty-stepper {
    display: flex;
    align-items: center;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    width: 110px;
    height: 45px;
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
    font-size: 14px;
    font-weight: 500;
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
    color: #000;
    border: 1px solid #ffd814;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.pdp-add-cart:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.icon-bag {
    width: 20px;
    height: 20px;
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
