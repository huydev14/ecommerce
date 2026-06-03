<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useCartStore } from '@/stores/cart';
import ProductCard from '@/components/ProductCard.vue';

const route = useRoute();
const router = useRouter();
const cartStore = useCartStore();
const { t } = useI18n();

const filters = computed(() => [
    {
        title: t('productList.filters_price_title'),
        links: [
            t('productList.filters_price_under500'),
            t('productList.filters_price_from500To1m'),
            t('productList.filters_price_over1m'),
        ],
    },
    {
        title: t('productList.filters_status_title'),
        options: [t('productList.filters_status_inStock'), t('productList.filters_status_new'), t('productList.filters_status_bestSeller')],
    },
    {
        title: t('productList.filters_offer_title'),
        links: [t('productList.filters_offer_discount'), t('productList.filters_offer_fastDelivery'), t('productList.filters_offer_highRating')],
    },
]);

const products = ref([]);
const brandOptions = ref([]);
const meta = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
});
const isLoading = ref(false);
const errorMessage = ref('');
const addingProductIds = ref(new Set());
const cartMessages = ref({});

const categorySlug = computed(() => route.query.category || '');
const keyword = computed(() => String(route.query.keyword || route.query.q || '').trim());
const searchQuery = computed(() => keyword.value || categorySlug.value || t('productList.allProducts'));
const currentPage = computed(() => Number(route.query.page || 1));
const hasProducts = computed(() => products.value.length > 0);
const selectedBrands = computed(() => parseCommaQuery(route.query.brand));
const paginationQuery = (page) => ({
    ...route.query,
    page,
});
const resultRange = computed(() => {
    if (!meta.value.total || !hasProducts.value) {
        return t('productList.resultCount', { count: 0 });
    }

    const start = (meta.value.current_page - 1) * 24 + 1;
    const end = start + products.value.length - 1;

    return t('productList.resultRange', { start, end, total: meta.value.total });
});

const parseCommaQuery = (value) => {
    if (!value) {
        return [];
    }

    return String(value)
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);
};

const toggleBrand = (brandSlug) => {
    const nextBrands = new Set(selectedBrands.value);

    if (nextBrands.has(brandSlug)) {
        nextBrands.delete(brandSlug);
    } else {
        nextBrands.add(brandSlug);
    }

    const query = {
        ...route.query,
        page: undefined,
        brand: nextBrands.size ? Array.from(nextBrands).join(',') : undefined,
    };

    router.push({ name: 'ProductList', query });
};

const isAddingToCart = (product) => addingProductIds.value.has(product.id);

const setCartMessage = (productId, message, type = 'success') => {
    cartMessages.value = {
        ...cartMessages.value,
        [productId]: {
            message,
            type,
        },
    };
};

const addProductToCart = async (product) => {
    if (!product.product_variant_id) {
        setCartMessage(product.id, t('productList.messages_missingVariant'), 'error');
        return;
    }

    addingProductIds.value = new Set(addingProductIds.value).add(product.id);
    setCartMessage(product.id, '');

    try {
        await cartStore.addItem(product.product_variant_id, 1);
        setCartMessage(product.id, t('productList.messages_addedToCart'));
    } catch (error) {
        setCartMessage(product.id, error.response?.data?.message || t('productList.messages_addToCartFailed'), 'error');
    } finally {
        const nextAddingProductIds = new Set(addingProductIds.value);
        nextAddingProductIds.delete(product.id);
        addingProductIds.value = nextAddingProductIds;
    }
};

const fetchProducts = async () => {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get('/products', {
            params: {
                brandLimit: 10,
                page: currentPage.value,
                ...(keyword.value ? { keyword: keyword.value } : {}),
                ...(categorySlug.value ? { category: categorySlug.value } : {}),
                ...(selectedBrands.value.length ? { brand: selectedBrands.value.join(',') } : {}),
            },
        });

        if (response.data?.success) {
            products.value = response.data.data || [];
            meta.value = {
                current_page: response.data.meta?.current_page || 1,
                last_page: response.data.meta?.last_page || 1,
                total: response.data.meta?.total || 0,
            };
            brandOptions.value = response.data.meta?.filters?.brands || brandOptions.value;
        } else {
            products.value = [];
            errorMessage.value = t('productList.errors_fetchProducts');
        }
    } catch (error) {
        products.value = [];
        errorMessage.value = t('productList.errors_fetchProductsGeneric');
        console.error(t('productList.errors_fetchProductsLog'), error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchProducts);

watch(() => [route.query.category, route.query.keyword, route.query.q, route.query.page, route.query.brand], fetchProducts);
</script>

<template>
    <section class="product-listing">
        <div class="listing-summary">
            <p>
                {{ t('productList.resultsFor', { range: resultRange, query: searchQuery }) }}
            </p>

            <label class="sort-control">
                <span>{{ t('productList.sort_label') }}</span>
                <select>
                    <option>{{ t('productList.sort_newest') }}</option>
                </select>
            </label>
        </div>

        <div class="listing-shell">
            <aside class="listing-filters" :aria-label="t('productList.aria_filters')">
                <section class="filter-group">
                    <h2>{{ t('productList.filters_brand') }}</h2>

                    <div v-if="brandOptions.length" class="filter-options">
                        <label v-for="brand in brandOptions" :key="brand.slug" class="filter-check">
                            <input type="checkbox" :checked="selectedBrands.includes(brand.slug)" @change="toggleBrand(brand.slug)" />
                            <span>{{ brand.name }}</span>
                        </label>
                    </div>
                    <p v-else class="filter-empty">{{ t('productList.empty_brands') }}</p>
                </section>

                <section v-for="group in filters" :key="group.title" class="filter-group">
                    <h2>{{ group.title }}</h2>

                    <div v-if="group.options" class="filter-options">
                        <label v-for="option in group.options" :key="option" class="filter-check">
                            <input type="checkbox" />
                            <span>{{ option }}</span>
                        </label>
                    </div>

                    <div v-if="group.links" class="filter-links">
                        <a v-for="link in group.links" :key="link" href="#">{{ link }}</a>
                    </div>

                    <button v-if="group.hasMore" type="button" class="filter-more">{{ t('productList.showMore') }}</button>
                </section>

                <section class="filter-group">
                    <h2>{{ t('productList.filters_rating') }}</h2>
                    <a href="#" class="review-filter"><span>★★★★★</span> {{ t('productList.filters_andUp') }}</a>
                </section>
            </aside>

            <main class="listing-results">
                <header class="results-header">
                    <h1>{{ t('productList.title') }}</h1>
                </header>

                <div v-if="isLoading" class="listing-state">{{ t('productList.loading') }}</div>
                <div v-else-if="errorMessage" class="listing-state is-error">{{ errorMessage }}</div>
                <div v-else-if="!hasProducts" class="listing-state">{{ t('productList.empty_products') }}</div>
                <template v-else>
                    <div class="product-grid">
                        <ProductCard
                            v-for="product in products"
                            :key="product.id"
                            :product="product"
                            :is-adding="isAddingToCart(product)"
                            :message="cartMessages[product.id]?.message || ''"
                            :message-type="cartMessages[product.id]?.type || 'success'"
                            show-badge
                            :cart-label="t('productCard.actions_addToCart')"
                            :adding-label="t('productCard.actions_adding')"
                            @add-to-cart="addProductToCart"
                        />
                    </div>

                    <nav v-if="meta.last_page > 1" class="listing-pagination" :aria-label="t('productList.aria_pagination')">
                        <RouterLink
                            v-if="meta.current_page > 1"
                            class="pagination-link"
                            :to="{ name: 'ProductList', query: paginationQuery(meta.current_page - 1) }"
                        >
                            {{ t('productList.pagination_previous') }}
                        </RouterLink>
                        <span v-else class="pagination-link is-disabled">{{ t('productList.pagination_previous') }}</span>
                        <span>{{ t('productList.pagination_page', { current: meta.current_page, total: meta.last_page }) }}</span>
                        <RouterLink
                            v-if="meta.current_page < meta.last_page"
                            class="pagination-link"
                            :to="{ name: 'ProductList', query: paginationQuery(meta.current_page + 1) }"
                        >
                            {{ t('productList.pagination_next') }}
                        </RouterLink>
                        <span v-else class="pagination-link is-disabled">{{ t('productList.pagination_next') }}</span>
                    </nav>
                </template>
            </main>
        </div>
    </section>
</template>

<style scoped>
.product-listing {
    min-height: 100vh;
    background: #ffffff;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.listing-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-height: 44px;
    padding: 0 8.5%;
    border-bottom: 1px solid #d5d9d9;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    font-size: 14px;
}

.listing-summary strong {
    color: #c45500;
}

.sort-control {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex: 0 0 auto;
    font-size: 12px;
}

.sort-control select {
    height: 28px;
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background: #f0f2f2;
    padding: 0 28px 0 10px;
    color: #0f1111;
    font-size: 12px;
}

.listing-shell {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 28px;
    max-width: 1760px;
    margin: 0 auto;
    padding: 24px 28px 48px;
}

.listing-filters {
    align-self: start;
    padding-top: 2px;
}

.filter-group {
    margin-bottom: 20px;
}

.filter-group h2 {
    margin: 0 0 8px;
    font-size: 14px;
    font-weight: 700;
}

.filter-options {
    display: grid;
    gap: 6px;
}

.filter-check {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 14px;
    line-height: 1.25;
}

.filter-check input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: #007185;
}

.filter-more,
.filter-links a,
.review-filter {
    display: block;
    color: #007185;
    font-size: 14px;
    text-decoration: none;
}

.filter-more {
    margin-top: 8px;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
}

.filter-links {
    display: grid;
    gap: 7px;
}

.filter-empty {
    margin: 0;
    color: #565959;
    font-size: 13px;
}

.review-filter {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #0f1111;
}

.review-filter span,
.stars {
    color: #ff9900;
    letter-spacing: 0;
}

.color-grid {
    display: grid;
    grid-template-columns: repeat(6, 26px);
    gap: 10px;
}

.color-swatch {
    width: 26px;
    height: 26px;
    border: 1px solid #d5d9d9;
    border-radius: 50%;
    cursor: pointer;
}

.results-header {
    margin-bottom: 8px;
}

.results-header h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

.results-header p {
    margin: 4px 0 0;
    color: #565959;
    font-size: 14px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 8px;
}

.product-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid #f0f2f2;
    border-radius: 4px;
    background: #ffffff;
    overflow: hidden;
}

.product-badge {
    position: absolute;
    top: 6px;
    left: 6px;
    z-index: 2;
    border-radius: 3px;
    background: #0f172a;
    padding: 3px 7px;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
}

.product-badge.is-orange {
    background: #e65a00;
}

.product-image {
    display: grid;
    place-items: center;
    height: 300px;
    background: #f7f7f7;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    max-width: 100%;
    max-height: 100%;
}

.product-content {
    padding: 12px 12px 18px;
}

.product-title {
    display: block;
    color: #0f1111;
    font-size: 16px;
    line-height: 1.35;
    text-decoration: none;
}

.product-title:hover,
.filter-links a:hover,
.review-filter:hover,
.filter-more:hover {
    color: #c45500;
    text-decoration: underline;
}

.product-tag {
    margin: 8px 0 0;
    font-size: 13px;
    font-weight: 700;
}

.product-rating {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 3px;
    margin-top: 10px;
    font-size: 13px;
}

.product-rating a {
    color: #007185;
    text-decoration: none;
}

.product-bought,
.product-original {
    margin: 4px 0 0;
    color: #565959;
    font-size: 13px;
}

.product-price {
    margin-top: 8px;
    font-size: 30px;
    line-height: 1;
    color: #0f1111;
}

.listing-state {
    display: grid;
    min-height: 260px;
    place-items: center;
    border: 1px solid #f0f2f2;
    border-radius: 4px;
    background: #ffffff;
    color: #565959;
    font-size: 15px;
}

.listing-state.is-error {
    color: #b42318;
}

.listing-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 24px;
    color: #565959;
    font-size: 14px;
}

.pagination-link {
    min-width: 86px;
    border: 1px solid #d5d9d9;
    border-radius: 999px;
    padding: 8px 16px;
    background: #ffffff;
    color: #007185;
    text-align: center;
    text-decoration: none;
}

.pagination-link:hover {
    border-color: #007185;
    color: #c45500;
}

.pagination-link.is-disabled {
    pointer-events: none;
    color: #8c8c8c;
    background: #f7f7f7;
}

.product-price small {
    margin-right: 3px;
    font-size: 12px;
    vertical-align: super;
}

.cart-button {
    margin-top: 12px;
    min-height: 32px;
    border-radius: 999px;
    padding: 0 14px;
    font-size: 13px;
    cursor: pointer;
}

.cart-button {
    border: 1px solid #ffd814;
    background: #ffd814;
    color: #0f1111;
}

.cart-button:hover {
    border-color: #f7ca00;
    background: #f7ca00;
}

.cart-button:disabled {
    cursor: not-allowed;
    opacity: 0.7;
}

.cart-message {
    margin: 8px 0 0;
    color: #007600;
    font-size: 12px;
    line-height: 1.35;
}

.cart-message.is-error {
    color: #b42318;
}

.more-results {
    margin-top: 12px;
}

.more-results h2 {
    margin: 0 0 8px;
    font-size: 22px;
    font-weight: 700;
}

@media (max-width: 1400px) {
    .product-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 1100px) {
    .listing-shell {
        grid-template-columns: 220px minmax(0, 1fr);
        gap: 20px;
        padding-inline: 18px;
    }

    .product-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .product-image {
        height: 250px;
    }
}

@media (max-width: 820px) {
    .listing-summary {
        align-items: flex-start;
        padding: 10px 14px;
        flex-direction: column;
    }

    .listing-shell {
        display: block;
        padding: 14px;
    }

    .listing-filters {
        display: flex;
        gap: 16px;
        margin: 0 -14px 18px;
        padding: 0 14px 12px;
        overflow-x: auto;
        border-bottom: 1px solid #e5e7eb;
    }

    .filter-group {
        flex: 0 0 170px;
        margin: 0;
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .product-grid {
        grid-template-columns: 1fr;
    }

    .product-card {
        display: grid;
        grid-template-columns: 40% 1fr;
    }

    .product-image {
        height: auto;
        min-height: 220px;
    }

    .product-price {
        font-size: 25px;
    }
}
</style>
