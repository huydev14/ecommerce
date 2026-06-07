<script setup>
import { computed } from 'vue';
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

const productKey = (product) => product.id || product.slug || product.name;

const productRows = computed(() => {
    const chunkSize = 6;
    const rows = [];

    for (let index = 0; index < props.products.length; index += chunkSize) {
        rows.push(props.products.slice(index, index + chunkSize));
    }

    return rows;
});
</script>

<template>
    <section class="rail new-products" :aria-label="t('home.newProducts_aria')">
        <div class="section-heading">
            <h2 class="tw-text-lg tw-font-bold">{{ t('home.newProducts_title') }}</h2>
            <a href="#" class="link">{{ t('home.links_seeNewArrivals') }}</a>
        </div>

        <div class="new-products__rows">
            <div v-for="(row, rowIndex) in productRows" :key="rowIndex" class="new-products__row">
                <ProductCard
                    v-for="product in row"
                    :key="productKey(product)"
                    :product="product"
                    :is-adding="props.isProductAdding(product)"
                    compact
                    @add-to-cart="emit('add-to-cart', product)"
                />
            </div>

            <p v-if="productRows.length === 0" class="new-products__empty">{{ t('home.empty_newProducts') }}</p>
        </div>
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

.section-heading .link {
    margin-top: 0;
}

.link {
    display: inline-flex;
    width: fit-content;
    margin-top: auto;
    color: #007185;
    font-size: 13px;
    line-height: 18px;
    text-decoration: none;
}

.link:hover {
    color: #c7511f;
    text-decoration: underline;
}

.new-products {
    padding: 18px 18px 20px;
}

.new-products__rows {
    display: grid;
    gap: 14px;
}

.new-products__row {
    display: grid;
    grid-template-columns: repeat(6, minmax(170px, 1fr));
    gap: 14px;
}

.new-products :deep(.client-product-card) {
    border: 1px solid #f0f2f2;
    border-radius: 2px;
}

.new-products :deep(.client-product-card__media) {
    height: 190px;
}


.new-products :deep(.client-product-card__body) {
    min-height: 236px;
    padding: 12px 12px 14px;
}

.new-products :deep(.client-product-card__brand) {
    margin-bottom: 3px;
    font-size: 14px;
    line-height: 18px;
}

.new-products :deep(.client-product-card__name) {
    min-height: 40px;
    font-size: 14px;
    line-height: 20px;
    -webkit-line-clamp: 2;
}

.new-products :deep(.client-product-card__rating) {
    margin-top: 8px;
    font-size: 12px;
    line-height: 16px;
}

.new-products :deep(.client-product-card__stars) {
    font-size: 13px;
}

.new-products :deep(.client-product-card__meta),
.new-products :deep(.client-product-card__shipping) {
    font-size: 13px;
    line-height: 18px;
}

.new-products :deep(.client-product-card__price) {
    margin-top: 10px;
}

.new-products :deep(.client-product-card__price span) {
    padding-top: 4px;
    font-size: 12px;
    line-height: 14px;
}

.new-products :deep(.client-product-card__price strong) {
    font-size: 26px;
    line-height: 30px;
}

.new-products :deep(.client-product-card__cart) {
    min-height: 38px;
    font-size: 14px;
    line-height: 18px;
}

.new-products__empty {
    margin: 0;
    color: #566;
    font-size: 14px;
    line-height: 20px;
}

@media (max-width: 1280px) {
    .new-products__row {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 1024px) {
    .new-products__row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .new-products__row {
        grid-template-columns: 1fr;
    }

    .new-products :deep(.client-product-card__media) {
        height: 180px;
    }

    .new-products :deep(.client-product-card__body) {
        min-height: auto;
    }
}
</style>
