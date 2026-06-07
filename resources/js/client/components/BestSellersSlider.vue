<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ProductCard from '@/components/ProductCard.vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    isProductAdding: {
        type: Function,
        default: () => false,
    },
});

const emit = defineEmits(['add-to-cart']);

const { t } = useI18n();
const bestSellersSlider = ref(null);

const productKey = (product) => product.id || product.slug || product.name;

const scrollBestSellers = (direction) => {
    bestSellersSlider.value?.scrollBy({
        left: direction * 360,
        behavior: 'smooth',
    });
};
</script>

<template>
    <section class="rail best-sellers" :aria-label="t('home.bestSellers_aria')">
        <div class="section-heading">
            <div>
                <h2 class="tw-text-lg tw-font-bold">{{ t('home.bestSellers_title') }}</h2>
            </div>

            <div class="slider-controls" :aria-label="t('home.bestSellers_aria')">
                <button type="button" :aria-label="t('home.actions_scrollLeft')" @click="scrollBestSellers(-1)">‹</button>
                <button type="button" :aria-label="t('home.actions_scrollRight')" @click="scrollBestSellers(1)">›</button>
            </div>
        </div>

        <div ref="bestSellersSlider" class="best-sellers-slider">
            <ProductCard
                v-for="product in props.products"
                :key="productKey(product)"
                :product="product"
                :is-adding="props.isProductAdding(product)"
                compact
                @add-to-cart="emit('add-to-cart', product)"
            />
        </div>

        <p v-if="props.products.length === 0" class="best-sellers__empty">{{ t('home.empty_bestSellers') }}</p>
    </section>
</template>

<style scoped>
.rail {
    margin-top: 20px;
    padding: 18px 20px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.best-sellers {
    padding: 18px 18px 20px;
}

.section-heading {
    display: flex;
    align-items: baseline;
    gap: 14px;
    justify-content: space-between;
    margin-bottom: 14px;
}

.slider-controls {
    display: flex;
    flex: none;
    gap: 8px;
}

.slider-controls button {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border: 1px solid #d5d9d9;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.12);
    color: #0f1111;
    cursor: pointer;
    font-size: 28px;
    line-height: 1;
}

.slider-controls button:hover {
    background: #f7fafa;
}

.best-sellers-slider {
    display: grid;
    grid-auto-columns: 220px;
    grid-auto-flow: column;
    gap: 16px;
    overflow-x: auto;
    scrollbar-width: none;
    scroll-behavior: smooth;
    scroll-snap-type: x proximity;
    padding: 2px;
}

.best-sellers-slider::-webkit-scrollbar {
    display: none;
}

.best-sellers :deep(.client-product-card) {
    display: flex;
    min-width: 0;
    flex-direction: column;
    overflow: hidden;
    scroll-snap-align: start;
    border: 1px solid #f0f2f2;
    border-radius: 2px;
    background: #fff;
}

.best-sellers :deep(.client-product-card__media) {
    display: grid;
    width: 100%;
    height: 190px;
    place-items: center;
    overflow: hidden;
    background: #f7f7f7;
}

.best-sellers :deep(.client-product-card__image) {
    width: 100%;
    height: 100%;
}

.best-sellers :deep(.client-product-card__image img) {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.best-sellers :deep(.client-product-card__body) {
    display: flex;
    min-height: 236px;
    flex: 1;
    flex-direction: column;
    padding: 12px 12px 14px;
}

.best-sellers :deep(.client-product-card__brand) {
    margin-bottom: 3px;
    color: #0f1111;
    font-size: 14px;
    font-weight: 700;
    line-height: 18px;
}

.best-sellers :deep(.client-product-card__name) {
    display: -webkit-box;
    min-height: 40px;
    overflow: hidden;
    color: #0f1111;
    font-size: 14px;
    line-height: 20px;
    text-decoration: none;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.best-sellers :deep(.client-product-card__name:hover) {
    color: #c7511f;
    text-decoration: underline;
}

.best-sellers :deep(.client-product-card__rating) {
    display: flex;
    align-items: center;
    gap: 3px;
    margin-top: 8px;
    color: #0f1111;
    font-size: 12px;
    line-height: 16px;
}

.best-sellers :deep(.client-product-card__stars) {
    color: #ff8f00;
    font-size: 13px;
    letter-spacing: 0;
}

.best-sellers :deep(.client-product-card__reviews) {
    color: #007185;
}

.best-sellers :deep(.client-product-card__meta),
.best-sellers :deep(.client-product-card__shipping) {
    color: #565959;
    font-size: 13px;
    line-height: 18px;
}

.best-sellers :deep(.client-product-card__price) {
    display: flex;
    align-items: flex-start;
    gap: 3px;
    margin-top: 10px;
    color: #0f1111;
}

.best-sellers :deep(.client-product-card__price span) {
    padding-top: 4px;
    font-size: 12px;
    line-height: 14px;
}

.best-sellers :deep(.client-product-card__price strong) {
    font-size: 26px;
    font-weight: 700;
    line-height: 30px;
}

.best-sellers :deep(.client-product-card__cart) {
    min-height: 38px;
    font-size: 14px;
    line-height: 18px;
}

.best-sellers__empty {
    margin: 10px 0 0;
    color: #565959;
    font-size: 14px;
    line-height: 20px;
}

@media (max-width: 768px) {
    .slider-controls button {
        width: 34px;
        height: 34px;
    }

    .best-sellers-slider {
        grid-auto-columns: 76%;
    }
}
</style>
