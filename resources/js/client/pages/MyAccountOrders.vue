<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';

const { t } = useI18n();
const orderTabs = computed(() => [
    { key: 'orders', label: t('orders.tabs_orders') },
    { key: 'buyAgain', label: t('orders.tabs_buyAgain') },
    { key: 'notYetShipped', label: t('orders.tabs_notYetShipped') },
]);
const orders = ref([]);
const searchQuery = ref('');
const isLoading = ref(false);

const formatCurrency = (value) =>
    new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const imageUrl = (image) => {
    if (!image) {
        return '/img/default-image.jpg';
    }

    if (/^https?:\/\//i.test(image) || image.startsWith('/')) {
        return image;
    }

    return `/storage/${image}`;
};

const statusLabel = (status) => {
    const labels = {
        pending: 'orders.status_pending',
        processing: 'orders.status_processing',
        completed: 'orders.status_completed',
        cancelled: 'orders.status_cancelled',
    };

    return labels[status] ? t(labels[status]) : status || t('orders.status_processing');
};

const filteredOrders = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();

    if (!keyword) {
        return orders.value;
    }

    return orders.value.filter((order) => {
        const orderNumber = String(order.order_number || '').toLowerCase();
        const itemNames = (order.items || []).map((item) => item.name).join(' ').toLowerCase();

        return orderNumber.includes(keyword) || itemNames.includes(keyword);
    });
});

const fetchOrders = async () => {
    isLoading.value = true;

    try {
        const response = await api.get('/orders');
        orders.value = (response.data.data || []).map((order) => ({
            ...order,
            items: order.items || [],
        }));
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchOrders);
</script>

<template>
    <section class="orders-page" aria-labelledby="orders-title">
        <div class="orders-page__inner">
            <header class="orders-header">
                <h1 id="orders-title">{{ t('orders.title') }}</h1>

                <form class="orders-search" role="search" @submit.prevent>
                    <label class="orders-search__box">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input v-model="searchQuery" type="search" :placeholder="t('orders.searchPlaceholder')" />
                    </label>

                    <button type="submit">{{ t('orders.searchButton') }}</button>
                </form>
            </header>

            <nav class="orders-tabs" :aria-label="t('orders.aria_views')">
                <a v-for="tab in orderTabs" :key="tab.key" href="#" :class="{ 'is-active': tab.key === 'orders' }">
                    {{ tab.label }}
                </a>
            </nav>

            <div class="orders-filter">
                <span v-html="t('orders.placedIn', { count: filteredOrders.length })"></span>
                <button type="button">
                    {{ t('orders.pastThreeMonths') }}
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>
            </div>

            <div v-if="isLoading" class="orders-empty" role="status">
                {{ t('orders.loading') }}
            </div>

            <div v-else-if="filteredOrders.length" class="orders-list">
                <article v-for="order in filteredOrders" :key="order.order_number" class="orders-card">
                    <header class="orders-card__header">
                        <div>
                            <span>{{ t('orders.card_orderPlaced') }}</span>
                            <strong>{{ order.placed_at }}</strong>
                        </div>
                        <div>
                            <span>{{ t('orders.card_total') }}</span>
                            <strong>{{ formatCurrency(order.total) }}</strong>
                        </div>
                        <div>
                            <span>{{ t('orders.card_payment') }}</span>
                            <strong>{{ order.payment_method }}</strong>
                        </div>
                        <div class="orders-card__code">
                            <span>{{ t('orders.card_orderNumber', { number: order.order_number }) }}</span>
                            <RouterLink :to="{ name: 'OrderSuccess', query: { order: order.order_number } }">
                                {{ t('orders.card_viewDetails') }}
                            </RouterLink>
                        </div>
                    </header>

                    <div class="orders-card__body">
                        <div class="orders-card__status">
                            <strong>{{ statusLabel(order.status) }}</strong>
                            <span>{{ t('orders.card_itemCount', { count: order.item_count }) }}</span>
                        </div>

                        <div class="orders-card__items">
                            <div v-for="item in order.items.slice(0, 3)" :key="item.id" class="orders-card__item">
                                <img :src="imageUrl(item.image)" :alt="item.name" />
                                <div>
                                    <strong>{{ item.name }}</strong>
                                    <span>{{ t('orders.card_quantity', { quantity: item.quantity, price: formatCurrency(item.price) }) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="orders-empty" role="status">
                {{ t('orders.empty') }}
                <a href="#">{{ t('orders.viewOrdersInYear', { year: 2026 }) }}</a>
            </div>
        </div>

    </section>
</template>

<style scoped>
.orders-page {
    min-height: 100vh;
    background: #ffffff;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.orders-page__inner {
    width: min(100%, 1320px);
    margin: 0 auto;
    padding: 18px 28px 92px;
}

.orders-breadcrumb {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 22px;
    font-size: 14px;
    line-height: 20px;
}

.orders-breadcrumb a,
.orders-breadcrumb span:last-child,
.orders-empty a,
.orders-history a {
    color: #007185;
    text-decoration: none;
}

.orders-breadcrumb span:last-child {
    color: #c45500;
    font-weight: 700;
}

.orders-breadcrumb a:hover,
.orders-empty a:hover,
.orders-history a:hover,
.orders-tabs a:hover {
    color: #c45500;
    text-decoration: underline;
}

.orders-header {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) minmax(360px, 642px);
    align-items: center;
    gap: 28px;
}

.orders-header h1 {
    margin: 0;
    color: #0f1111;
    font-size: 36px;
    font-weight: 500;
    line-height: 44px;
    letter-spacing: 0;
}

.orders-search {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
}

.orders-search__box {
    display: flex;
    align-items: center;
    min-width: 0;
    height: 44px;
    border: 1px solid #888c8c;
    border-radius: 8px;
    background: #ffffff;
    padding: 0 14px;
    color: #0f1111;
}

.orders-search__box i {
    margin-right: 10px;
    font-size: 20px;
}

.orders-search__box input {
    min-width: 0;
    flex: 1;
    border: 0;
    color: #0f1111;
    font-size: 18px;
    line-height: 24px;
    outline: none;
}

.orders-search__box input::placeholder {
    color: #6f7373;
    opacity: 1;
}

.orders-search button {
    min-height: 44px;
    border: 0;
    border-radius: 22px;
    background: #303333;
    padding: 0 22px;
    color: #ffffff;
    font-size: 18px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
}

.orders-search button:hover {
    background: #1f2323;
}

.orders-tabs {
    display: flex;
    align-items: flex-end;
    gap: 26px;
    margin-top: 28px;
    border-bottom: 1px solid #d5d9d9;
    overflow-x: auto;
}

.orders-tabs a {
    position: relative;
    flex: 0 0 auto;
    padding: 0 2px 10px;
    color: #007185;
    font-size: 18px;
    font-weight: 600;
    line-height: 24px;
    text-decoration: none;
}

.orders-tabs a.is-active {
    color: #0f1111;
}

.orders-tabs a.is-active::after {
    position: absolute;
    right: 0;
    bottom: -1px;
    left: 0;
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: #f08804;
    content: '';
}

.orders-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 22px;
    font-size: 18px;
    line-height: 24px;
}

.orders-filter button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    border: 1px solid #888c8c;
    border-radius: 8px;
    background: #ffffff;
    padding: 0 14px;
    color: #0f1111;
    font-size: 18px;
    cursor: pointer;
}

.orders-filter button:hover {
    background: #f7fafa;
}

.orders-empty {
    margin-top: 62px;
    text-align: center;
    font-size: 18px;
    font-weight: 700;
    line-height: 26px;
}

.orders-empty a {
    font-weight: 500;
}

.orders-list {
    display: grid;
    gap: 18px;
    margin-top: 28px;
}

.orders-card {
    overflow: hidden;
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background: #ffffff;
}

.orders-card__header {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr minmax(220px, 1.2fr);
    gap: 18px;
    border-bottom: 1px solid #d5d9d9;
    background: #f0f2f2;
    padding: 14px 18px;
}

.orders-card__header span,
.orders-card__status span,
.orders-card__item span {
    display: block;
    color: #565959;
    font-size: 13px;
    line-height: 18px;
}

.orders-card__header strong,
.orders-card__status strong,
.orders-card__item strong {
    display: block;
    color: #0f1111;
    font-size: 15px;
    line-height: 20px;
}

.orders-card__code {
    text-align: right;
}

.orders-card__code a {
    color: #007185;
    font-size: 14px;
    line-height: 20px;
    text-decoration: none;
}

.orders-card__code a:hover {
    color: #c45500;
    text-decoration: underline;
}

.orders-card__body {
    display: grid;
    grid-template-columns: 180px minmax(0, 1fr);
    gap: 22px;
    padding: 18px;
}

.orders-card__status strong {
    font-size: 18px;
}

.orders-card__items {
    display: grid;
    gap: 14px;
}

.orders-card__item {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr);
    align-items: center;
    gap: 14px;
}

.orders-card__item img {
    width: 72px;
    height: 72px;
    border: 1px solid #e3e6e6;
    border-radius: 4px;
    object-fit: cover;
}

.orders-history {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 180px;
    align-items: center;
    gap: 28px;
    min-height: 156px;
    border: 1px solid #d5d9d9;
    border-radius: 4px;
    background: #ffffff;
    padding: 28px 44px;
}

.orders-history p {
    margin: 0;
    color: #0f1111;
    font-size: 17px;
    font-style: italic;
    font-weight: 700;
    line-height: 24px;
}

.orders-history a {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    line-height: 16px;
}

@media (max-width: 900px) {
    .orders-page__inner {
        padding: 18px 18px 64px;
    }

    .orders-header {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .orders-search {
        grid-template-columns: 1fr;
    }

    .orders-tabs {
        gap: 22px;
    }

    .orders-history {
        grid-template-columns: 1fr;
        padding: 24px 20px;
    }

    .orders-card__header,
    .orders-card__body {
        grid-template-columns: 1fr;
    }

    .orders-card__code {
        text-align: left;
    }
}

@media (max-width: 640px) {
    .orders-page__inner {
        padding: 16px 14px 46px;
    }

    .orders-header h1 {
        font-size: 30px;
        line-height: 36px;
    }

    .orders-search__box,
    .orders-search button,
    .orders-filter button {
        min-height: 40px;
    }

    .orders-search__box input,
    .orders-search button,
    .orders-tabs a,
    .orders-filter,
    .orders-filter button,
    .orders-empty {
        font-size: 16px;
    }

    .orders-filter {
        align-items: flex-start;
        flex-direction: column;
    }

    .orders-empty {
        margin-top: 42px;
        text-align: left;
    }
}
</style>
