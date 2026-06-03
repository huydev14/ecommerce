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
const featuredSlider = ref(null);

const productKey = (product) => product.id || product.slug || product.name;

const scrollFeatured = (direction) => {
    featuredSlider.value?.scrollBy({
        left: direction * 360,
        behavior: 'smooth',
    });
};
</script>

<template>
    <section class="rail featured-products" :aria-label="t('home.featuredProducts_aria')">
        <div class="section-heading">
            <div>
                <h2 class="tw-text-lg tw-font-bold">{{ t('home.featuredProducts_title') }}</h2>
            </div>

            <div class="slider-controls" :aria-label="t('home.featuredProducts_controls')">
                <button type="button" :aria-label="t('home.actions_scrollLeft')" @click="scrollFeatured(-1)">‹</button>
                <button type="button" :aria-label="t('home.actions_scrollRight')" @click="scrollFeatured(1)">›</button>
            </div>
        </div>

        <div ref="featuredSlider" class="featured-slider">
            <ProductCard
                v-for="product in props.products"
                :key="productKey(product)"
                :product="product"
                :is-adding="props.isProductAdding(product)"
                compact
                @add-to-cart="emit('add-to-cart', product)"
            />
        </div>

        <p v-if="props.products.length === 0" class="featured-products__empty">{{ t('home.empty_featuredProducts') }}</p>
    </section>
</template>

<style scoped>
.rail {
    margin-top: 20px;
    padding: 18px 20px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
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

.featured-slider {
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

.featured-slider::-webkit-scrollbar {
    display: none;
}

.featured-products :deep(.client-product-card) {
    scroll-snap-align: start;
}

.featured-products__empty {
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

    .featured-slider {
        grid-auto-columns: 76%;
    }
}
</style>
