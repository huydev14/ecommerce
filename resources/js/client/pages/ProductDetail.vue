<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import api from '@/services/api';
import { APP_CONFIG } from '@/config';
import { useCartStore } from '@/stores/cart';

const route = useRoute();
const cartStore = useCartStore();
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

const variants = computed(() => product.value?.variants || []);
const selectedVariant = computed(
    () => variants.value.find((variant) => variant.id === selectedVariantId.value) || variants.value[0] || null,
);
const activePrice = computed(() => selectedVariant.value?.price || product.value?.price || 0);
const brandName = computed(() => product.value?.brand?.name || APP_CONFIG.appName);
const brandSlug = computed(() => product.value?.brand?.slug || '');
const categoryName = computed(() => product.value?.category?.name || t('productDetail.fallback_category'));
const productImages = computed(() => {
    const image = product.value?.thumbnail;

    return image ? [image, image, image, image] : [];
});

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
            selectedImage.value = response.data.data.thumbnail;
            selectedVariantId.value = response.data.data.variants?.[0]?.id || null;
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

const formatPrice = (price) => {
    const numericPrice = Number(price || 0);

    if (!numericPrice) {
        return t('productDetail.contact');
    }

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(numericPrice);
};

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
</script>

<template>
    <section class="pdp-page">
        <div v-if="isLoading" class="pdp-state">{{ t('productDetail.loading') }}</div>
        <div v-else-if="errorMessage" class="pdp-state is-error">{{ errorMessage }}</div>

        <div v-else-if="product" class="pdp-shell">
            <aside class="pdp-gallery" :aria-label="t('productDetail.aria_images')">
                <div class="pdp-gallery__main">
                    <img :src="selectedImage" :alt="product.name" />
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

            <main class="pdp-details">
                <RouterLink v-if="brandSlug" class="pdp-store" :to="{ name: 'ProductList', query: { brand: brandSlug } }">
                    {{ t('productDetail.visitStore', { brand: brandName }) }}
                </RouterLink>
                <span v-else class="pdp-store">{{ t('productDetail.visitStore', { brand: brandName }) }}</span>
                <h1>{{ product.name }}</h1>

                <div class="pdp-rating">
                    <span>4.7</span>
                    <span class="pdp-stars">★★★★★</span>
                    <a href="#">{{ t('productDetail.ratings', { count: 121 }) }}</a>
                    <span>|</span>
                    <a href="#">{{ t('productDetail.searchThisPage') }}</a>
                </div>

                <p class="pdp-bought" v-html="t('productDetail.boughtInPastMonth', { count: '10K+' })"></p>

                <hr />

                <div class="pdp-price">{{ formatPrice(activePrice) }}</div>
                <p class="pdp-shipping">{{ t('productDetail.freeReturns') }}</p>
                <p class="pdp-muted">{{ t('productDetail.shippingFee', { fee: '$55.87' }) }}</p>

                <section v-if="variantLabels.length" class="pdp-options">
                    <h2>{{ t('productDetail.options') }}</h2>
                    <div class="pdp-option-grid">
                        <button
                            v-for="variant in variantLabels"
                            :key="variant.id"
                            type="button"
                            class="pdp-option"
                            :class="{ 'is-selected': selectedVariantId === variant.id }"
                            @click="selectedVariantId = variant.id"
                        >
                            <span>{{ variant.label }}</span>
                            <small>{{ formatPrice(variant.price) }}</small>
                        </button>
                    </div>
                </section>

                <dl class="pdp-specs">
                    <template v-for="[label, value] in specifications" :key="label">
                        <dt>{{ label }}</dt>
                        <dd>{{ value }}</dd>
                    </template>
                </dl>

                <section class="pdp-about">
                    <h2>{{ t('productDetail.about') }}</h2>
                    <ul>
                        <li v-for="item in descriptionItems" :key="item">{{ item }}</li>
                    </ul>
                </section>
            </main>

            <aside class="pdp-buybox" :aria-label="t('productDetail.aria_purchaseOptions')">
                <div class="pdp-buybox__price">{{ formatPrice(activePrice) }}</div>
                <p class="pdp-muted">{{ t('productDetail.shipping', { fee: '$55.87' }) }}</p>
                <p class="pdp-delivery" v-html="t('productDetail.delivery', { date: 'Wednesday, June 24' })"></p>
                <a href="#" class="pdp-location">{{ t('productDetail.deliverTo') }}</a>
                <p class="pdp-stock">{{ t('productDetail.inStock') }}</p>

                <label class="pdp-qty">
                    <span>{{ t('productDetail.quantity') }}</span>
                    <select v-model="quantity">
                        <option v-for="value in 10" :key="value" :value="value">{{ value }}</option>
                    </select>
                </label>

                <button type="button" class="pdp-cart" :disabled="isAddingToCart" @click="addToCart">
                    {{ isAddingToCart ? t('productCard.actions_adding') : t('productCard.actions_addToCart') }}
                </button>
                <button type="button" class="pdp-buy">{{ t('productDetail.buyNow') }}</button>

                <p v-if="cartMessage" class="pdp-cart-message">{{ cartMessage }}</p>
                <p v-if="cartError" class="pdp-cart-message is-error">{{ cartError }}</p>

                <dl class="pdp-seller">
                    <dt>{{ t('productDetail.seller_shipperSeller') }}</dt>
                    <dd>{{ APP_CONFIG.appName }}</dd>
                    <dt>{{ t('productDetail.seller_returns') }}</dt>
                    <dd>{{ t('productDetail.seller_freeRefund') }}</dd>
                </dl>

                <button type="button" class="pdp-secondary">{{ t('productDetail.addToList') }}</button>
            </aside>
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
    grid-template-columns: minmax(300px, 42vw) minmax(360px, 1fr) 300px;
    gap: 28px;
    max-width: 1520px;
    margin: 0 auto;
    padding: 24px 28px 56px;
}

.pdp-gallery {
    position: sticky;
    top: 16px;
    align-self: start;
}

.pdp-gallery__main {
    display: grid;
    min-height: 560px;
    place-items: center;
    background: #ffffff;
}

.pdp-gallery__main img {
    width: min(100%, 520px);
    max-height: 540px;
    object-fit: contain;
}

.pdp-gallery__thumbs {
    display: grid;
    grid-template-columns: repeat(6, 76px);
    justify-content: center;
    gap: 10px;
    margin-top: 18px;
}

.pdp-thumb {
    display: grid;
    width: 76px;
    height: 76px;
    place-items: center;
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background: #ffffff;
    cursor: pointer;
}

.pdp-thumb.is-active {
    border-color: #007185;
    box-shadow: 0 0 0 2px #c8f3fa;
}

.pdp-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.pdp-gallery__link,
.pdp-store,
.pdp-rating a,
.pdp-location {
    color: #007185;
    text-decoration: none;
}

.pdp-gallery__link {
    display: block;
    margin-top: 12px;
    text-align: center;
    font-size: 14px;
}

.pdp-details h1 {
    margin: 4px 0 8px;
    font-size: 26px;
    font-weight: 400;
    line-height: 1.25;
}

.pdp-store,
.pdp-rating,
.pdp-bought,
.pdp-muted,
.pdp-shipping,
.pdp-location,
.pdp-delivery,
.pdp-seller {
    font-size: 14px;
}

.pdp-rating {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.pdp-stars {
    color: #ff9900;
    letter-spacing: 0;
}

.pdp-bought {
    margin: 14px 0 8px;
}

.pdp-details hr {
    border: 0;
    border-top: 1px solid #d5d9d9;
    margin: 12px 0 18px;
}

.pdp-price,
.pdp-buybox__price {
    font-size: 30px;
    line-height: 1;
}

.pdp-shipping,
.pdp-location {
    margin: 18px 0 6px;
}

.pdp-muted {
    margin: 0 0 8px;
    color: #565959;
    line-height: 1.45;
}

.pdp-options {
    margin-top: 18px;
}

.pdp-options h2,
.pdp-about h2 {
    margin: 0 0 10px;
    font-size: 20px;
}

.pdp-option-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.pdp-option {
    min-width: 112px;
    border: 1px solid #8d9096;
    border-radius: 8px;
    background: #ffffff;
    padding: 9px 12px;
    text-align: left;
    cursor: pointer;
}

.pdp-option span,
.pdp-option small {
    display: block;
}

.pdp-option.is-selected {
    border-color: #007185;
    box-shadow: 0 0 0 2px #c8f3fa;
}

.pdp-keep span {
    display: grid;
    width: 22px;
    height: 22px;
    place-items: center;
    border-radius: 50%;
    background: #008a00;
    color: #ffffff;
}

.pdp-specs {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 10px 18px;
    margin: 20px 0;
    font-size: 15px;
}

.pdp-specs dt {
    font-weight: 700;
}

.pdp-specs dd {
    margin: 0;
}

.pdp-about {
    border-top: 1px solid #d5d9d9;
    padding-top: 18px;
}

.pdp-about ul {
    margin: 0;
    padding-left: 20px;
    line-height: 1.45;
}

.pdp-buybox {
    align-self: start;
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    padding: 18px;
}

.pdp-delivery {
    margin: 8px 0 14px;
}

.pdp-stock {
    margin: 18px 0 10px;
    color: #007600;
    font-size: 20px;
}

.pdp-qty {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #8d9096;
    border-radius: 8px;
    padding: 6px 10px;
}

.pdp-qty select {
    flex: 1;
    border: 0;
    background: transparent;
    font-size: 14px;
}

.pdp-cart,
.pdp-buy,
.pdp-secondary {
    width: 100%;
    min-height: 36px;
    border-radius: 999px;
    padding: 0 16px;
    font-size: 14px;
    cursor: pointer;
}

.pdp-cart {
    margin-top: 12px;
    border: 1px solid #ffd814;
    background: #ffd814;
}

.pdp-cart:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.pdp-buy {
    margin-top: 10px;
    border: 1px solid #ffa41c;
    background: #ffa41c;
}

.pdp-cart-message {
    margin: 10px 0 0;
    color: #007600;
    font-size: 13px;
    line-height: 1.4;
}

.pdp-cart-message.is-error {
    color: #b42318;
}

.pdp-secondary {
    margin-top: 12px;
    border: 1px solid #8d9096;
    background: #ffffff;
}

.pdp-seller {
    display: grid;
    grid-template-columns: 90px 1fr;
    gap: 10px;
    margin: 18px 0;
}

.pdp-seller dt {
    color: #565959;
}

.pdp-seller dd {
    margin: 0;
}

.pdp-state {
    display: grid;
    min-height: 420px;
    place-items: center;
    color: #565959;
    font-size: 16px;
}

.pdp-state.is-error {
    color: #b42318;
}

@media (max-width: 1180px) {
    .pdp-shell {
        grid-template-columns: minmax(260px, 42%) minmax(0, 1fr);
    }

    .pdp-buybox {
        grid-column: 2;
    }
}

@media (max-width: 820px) {
    .pdp-shell {
        display: block;
        padding: 16px;
    }

    .pdp-gallery {
        position: static;
    }

    .pdp-gallery__main {
        min-height: 360px;
    }

    .pdp-gallery__thumbs {
        grid-template-columns: repeat(4, 68px);
    }

    .pdp-thumb {
        width: 68px;
        height: 68px;
    }

    .pdp-details h1 {
        font-size: 22px;
    }

    .pdp-buybox {
        margin-top: 22px;
    }
}
</style>
