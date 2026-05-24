<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/api';

const route = useRoute();

const filters = [
    {
        title: 'Khoảng giá',
        links: ['Dưới 500.000đ', '500.000đ - 1.000.000đ', 'Trên 1.000.000đ'],
    },
    {
        title: 'Tình trạng',
        options: ['Còn hàng', 'Hàng mới', 'Bán chạy'],
    },
    {
        title: 'Ưu đãi',
        links: ['Có giảm giá', 'Giao nhanh', 'Đánh giá cao'],
    },
];

const colors = [
    '#111111',
    '#f5f5f5',
    '#808080',
    '#8b5a2b',
    '#e8cda5',
    '#cf2e2e',
    '#e98ab6',
    '#f59e0b',
    '#f8df21',
    '#ffffff',
    '#64b246',
    '#2f65b0',
    '#6a4ca0',
    '#d4a800',
    '#737373',
    '#d9f2ff',
];

const products = ref([]);
const meta = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
});
const isLoading = ref(false);
const errorMessage = ref('');

const categorySlug = computed(() => route.query.category || '');
const searchQuery = computed(() => route.query.q || categorySlug.value || 'tất cả sản phẩm');
const currentPage = computed(() => Number(route.query.page || 1));
const hasProducts = computed(() => products.value.length > 0);
const paginationQuery = (page) => ({
    ...route.query,
    page,
});
const resultRange = computed(() => {
    if (!meta.value.total || !hasProducts.value) {
        return '0 sản phẩm';
    }

    const start = (meta.value.current_page - 1) * 24 + 1;
    const end = start + products.value.length - 1;

    return `${start}-${end} trong ${meta.value.total} sản phẩm`;
});

const productUrl = () => '#';
const formatPrice = (price) => {
    const numericPrice = Number(price || 0);

    if (!numericPrice) {
        return 'Liên hệ';
    }

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(numericPrice);
};

const fetchProducts = async () => {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get('/products', {
            params: {
                page: currentPage.value,
                ...(categorySlug.value ? { category: categorySlug.value } : {}),
            },
        });

        if (response.data?.success) {
            products.value = response.data.data || [];
            meta.value = {
                current_page: response.data.meta?.current_page || 1,
                last_page: response.data.meta?.last_page || 1,
                total: response.data.meta?.total || 0,
            };
        } else {
            products.value = [];
            errorMessage.value = 'Không thể tải danh sách sản phẩm.';
        }
    } catch (error) {
        products.value = [];
        errorMessage.value = 'Có lỗi xảy ra khi tải danh sách sản phẩm.';
        console.error('Lỗi khi lấy danh sách sản phẩm:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchProducts);

watch(
    () => [route.query.category, route.query.page],
    fetchProducts,
);
</script>

<template>
    <section class="product-listing">
        <div class="listing-summary">
            <p>
                {{ resultRange }} cho <strong>"{{ searchQuery }}"</strong>
            </p>

            <label class="sort-control">
                <span>Sắp xếp:</span>
                <select>
                    <option>Mới nhất</option>
                </select>
            </label>
        </div>

        <div class="listing-shell">
            <aside class="listing-filters" aria-label="Product filters">
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

                    <button v-if="group.hasMore" type="button" class="filter-more">Xem thêm</button>
                </section>

                <section class="filter-group">
                    <h2>Đánh giá</h2>
                    <a href="#" class="review-filter"><span>★★★★★</span> trở lên</a>
                </section>

                <section class="filter-group">
                    <h2>Màu sắc</h2>
                    <div class="color-grid">
                        <button
                            v-for="color in colors"
                            :key="color"
                            type="button"
                            class="color-swatch"
                            :style="{ backgroundColor: color }"
                            aria-label="Filter by color"
                        ></button>
                    </div>
                </section>
            </aside>

            <main class="listing-results">
                <header class="results-header">
                    <h1>Danh sách sản phẩm</h1>
                    <p>
                        Sản phẩm được lấy từ API và hiển thị theo danh mục nếu URL có tham số category.
                    </p>
                </header>

                <div v-if="isLoading" class="listing-state">Đang tải sản phẩm...</div>
                <div v-else-if="errorMessage" class="listing-state is-error">{{ errorMessage }}</div>
                <div v-else-if="!hasProducts" class="listing-state">Không có sản phẩm phù hợp.</div>
                <template v-else>
                    <div class="product-grid">
                        <article v-for="product in products" :key="product.id" class="product-card">
                            <span class="product-badge">Mới</span>

                            <a :href="productUrl(product)" class="product-image">
                                <img :src="product.thumbnail" :alt="product.name" />
                            </a>

                            <div class="product-content">
                                <a :href="productUrl(product)" class="product-title">{{ product.name }}</a>
                                <div class="product-rating">
                                    <span class="stars">★★★★★</span>
                                    <span>5.0</span>
                                </div>
                                <p class="product-bought">Sản phẩm đang bán trên WorkHub</p>
                                <div class="product-price">{{ formatPrice(product.price) }}</div>
                                <button type="button" class="cart-button">Thêm vào giỏ</button>
                            </div>
                        </article>
                    </div>

                    <nav v-if="meta.last_page > 1" class="listing-pagination" aria-label="Product pagination">
                        <RouterLink
                            v-if="meta.current_page > 1"
                            class="pagination-link"
                            :to="{ name: 'ProductList', query: paginationQuery(meta.current_page - 1) }"
                        >
                            Trước
                        </RouterLink>
                        <span v-else class="pagination-link is-disabled">Trước</span>
                        <span>Trang {{ meta.current_page }} / {{ meta.last_page }}</span>
                        <RouterLink
                            v-if="meta.current_page < meta.last_page"
                            class="pagination-link"
                            :to="{ name: 'ProductList', query: paginationQuery(meta.current_page + 1) }"
                        >
                            Sau
                        </RouterLink>
                        <span v-else class="pagination-link is-disabled">Sau</span>
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
