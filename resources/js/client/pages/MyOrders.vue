<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';

const { t } = useI18n();
const orderTabs = computed(() => [
    { key: 'orders', label: t('orders.tabs_orders') },
    { key: 'notYetShipped', label: t('orders.tabs_notYetShipped') },
]);
const orders = ref([]);
const searchQuery = ref('');
const isLoading = ref(false);
const payingOrderId = ref(null);

const totalSpent = computed(() => orders.value.reduce((total, order) => total + Number(order.total || 0), 0));

const activeOrderCount = computed(() => orders.value.filter((order) => ['pending', 'processing'].includes(order.status)).length);

const formatCurrency = (value) =>
    new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const imageUrl = (image) => {
    if (!image) {
        return null;
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

const statusClass = (status) => `orders-status--${status || 'processing'}`;
const paymentStatusLabel = (status) => (status === 'paid' ? t('orders.payment_status_paid') : t('orders.payment_status_unpaid'));
const paymentStatusClass = (status) => `orders-payment-status--${status === 'paid' ? 'paid' : 'unpaid'}`;
const canPayWithVnpay = (order) => order.payment_method_code === 'vnpay' && order.payment_status !== 'paid' && order.order_id;

const filteredOrders = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();

    if (!keyword) {
        return orders.value;
    }

    return orders.value.filter((order) => {
        const orderNumber = String(order.order_number || '').toLowerCase();
        const itemNames = (order.items || [])
            .map((item) => item.name)
            .join(' ')
            .toLowerCase();

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

const payWithVnpay = async (order) => {
    if (!canPayWithVnpay(order) || payingOrderId.value) {
        return;
    }

    payingOrderId.value = order.order_id;

    try {
        const response = await api.post(`/payment/vnpay/${order.order_id}`);
        const paymentUrl = response.data?.data?.payment_url;

        if (response.data?.success && paymentUrl) {
            window.location.href = paymentUrl;
        }
    } finally {
        payingOrderId.value = null;
    }
};

onMounted(fetchOrders);
</script>

<template>
    <section class="orders-page" aria-labelledby="orders-title">
        <div class="orders-page__inner">
            <header class="orders-hero">
                <div class="orders-hero__copy">
                    <h1 id="orders-title">{{ t('orders.title') }}</h1>
                </div>

                <form class="orders-search" role="search" @submit.prevent>
                    <label class="orders-search__box">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input v-model="searchQuery" type="search" :placeholder="t('orders.searchPlaceholder')" />
                    </label>

                    <button type="submit">
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        <span>{{ t('orders.searchButton') }}</span>
                    </button>
                </form>
            </header>

            <div class="orders-summary" :aria-label="t('orders.title')">
                <div class="orders-summary__item">
                    <span>{{ t('orders.tabs_orders') }}</span>
                    <p>{{ orders.length }}</p>
                </div>
                <div class="orders-summary__item">
                    <span>{{ t('orders.card_total') }}</span>
                    <p>{{ formatCurrency(totalSpent) }}</p>
                </div>
                <div class="orders-summary__item">
                    <span>{{ t('orders.tabs_notYetShipped') }}</span>
                    <p>{{ activeOrderCount }}</p>
                </div>
            </div>

            <div class="orders-toolbar">
                <nav class="orders-tabs" :aria-label="t('orders.aria_views')">
                    <a v-for="tab in orderTabs" :key="tab.key" href="#" :class="{ 'is-active': tab.key === 'orders' }">
                        {{ tab.label }}
                    </a>
                </nav>

                <button class="orders-period" type="button">
                    {{ t('orders.pastThreeMonths') }}
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>
            </div>

            <div v-if="isLoading" class="orders-empty" role="status">
                <i class="fa-solid fa-spinner" aria-hidden="true"></i>
                <span>{{ t('orders.loading') }}</span>
            </div>

            <div v-else-if="filteredOrders.length" class="orders-list">
                <article v-for="order in filteredOrders" :key="order.order_number" class="orders-card">
                    <header class="orders-card__header">
                        <div class="orders-card__meta">
                            <span>{{ t('orders.card_orderPlaced') }}</span>
                            <strong>{{ order.placed_at }}</strong>
                        </div>
                        <div class="orders-card__meta">
                            <span>{{ t('orders.card_total') }}</span>
                            <strong>{{ formatCurrency(order.total) }}</strong>
                        </div>
                        <div class="orders-card__meta">
                            <span>{{ t('orders.card_payment') }}</span>
                            <strong>{{ order.payment_method }} </strong>
                            <span :class="paymentStatusClass(order.payment_status)">
                                ({{ paymentStatusLabel(order.payment_status) }})
                            </span>
                            <button
                               v-if="canPayWithVnpay(order)"
                                type="button"
                                class="orders-pay-btn tw-bg-gray-200 tw-text-gray-600 tw-rounded-md hover:tw-bg-gray-300 tw-font-bold"
                                :disabled="payingOrderId === order.order_id"
                                @click="payWithVnpay(order)"
                            >
                                {{ payingOrderId === order.order_id ? t('orders.payment_processing') : t('orders.payment_payNow') }}
                            </button>
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
                            <strong :class="statusClass(order.status)">{{ statusLabel(order.status) }}</strong>
                            <span>{{ t('orders.card_itemCount', { count: order.item_count }) }}</span>
                        </div>

                        <div class="orders-card__items">
                            <div v-for="item in order.items.slice(0, 3)" :key="item.id || item.name" class="orders-card__item">
                                <img v-if="imageUrl(item.image)" :src="imageUrl(item.image)" :alt="item.name" />
                                <div v-else class="orders-card__item-placeholder" aria-hidden="true">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                                <div>
                                    <strong>{{ item.name }}</strong>
                                    <span>{{
                                        t('orders.card_quantity', { quantity: item.quantity, price: formatCurrency(item.price) })
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="orders-empty" role="status">
                <span>{{ t('orders.empty') }}</span>
                <a href="#">{{ t('orders.viewOrdersInYear', { year: 2026 }) }}</a>
            </div>
        </div>
    </section>
</template>

<style scoped>
.orders-page {
    min-height: 100vh;
    background: #eef2f5;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.orders-page__inner {
    width: min(100%, 1240px);
    margin: 0 auto;
    padding: 28px 24px 92px;
}

.orders-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(360px, 560px);
    align-items: center;
    gap: 34px;
    overflow: hidden;
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background-color: #fff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
    padding: 30px;
}

.orders-hero__copy {
    min-width: 0;
}

.orders-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    border-radius: 999px;
    background: #111827;
    padding: 0 10px;
    color: #ffffff;
    font-size: 12px;
    font-weight: 700;
    line-height: 16px;
}

.orders-hero h1 {
    margin: 0;
    color: #0f1111;
    font-size: 24px;
    font-weight: 700;
    line-height: 46px;
    letter-spacing: 0;
}

.orders-hero p {
    margin: 10px 0 0;
    color: #565959;
    font-size: 16px;
    line-height: 24px;
}

.orders-search {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 135px;
    align-items: center;
}

.orders-search__box {
    display: flex;
    align-items: center;
    min-width: 0;
    height: 48px;
    border: 1px solid #c7cccc;
    background: #ffffff;
    padding: 0 15px;
    color: #0f1111;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08) inset;
}

.orders-search__box i {
    margin-right: 10px;
    font-size: 20px;
}

.orders-search__box input {
    min-width: 0;
    flex: 1;
    border: 0;
    background: transparent;
    color: #0f1111;
    font-size: 16px;
    line-height: 24px;
    outline: none;
}

.orders-search__box input::placeholder {
    color: #6f7373;
    opacity: 1;
}

.orders-search button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    border: 0;
    background: #ffd814;
    padding: 0 18px;
    color: #0f1111;
    font-size: 14px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
}

.orders-search button:hover {
    background: #f7ca00;
}

.orders-summary {
    display: flex;
    margin-top: 18px;
}

.orders-summary__item {
    flex: 1;
    min-width: 0;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
    padding: 18px 20px;
}

.orders-summary__item span {
    display: block;
    color: #565959;
    font-size: 13px;
    font-weight: 700;
    line-height: 18px;
    text-transform: uppercase;
}

.orders-summary__item strong {
    display: block;
    overflow: hidden;
    margin-top: 6px;
    color: #0f1111;
    font-size: 28px;
    font-weight: 700;
    line-height: 34px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.orders-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-top: 20px;
    border: 1px solid #d5d9d9;
    background: #ffffff;
    padding: 0 14px 0 20px;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.orders-tabs {
    display: flex;
    align-items: flex-end;
    gap: 22px;
    overflow-x: auto;
}

.orders-tabs a {
    position: relative;
    flex: 0 0 auto;
    padding: 18px 2px 16px;
    color: #007185;
    font-size: 15px;
    font-weight: 600;
    line-height: 20px;
    text-decoration: none;
}

.orders-tabs a.is-active {
    color: #0f1111;
}

.orders-tabs a.is-active::after {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    height: 4px;
    border-radius: 3px 3px 0 0;
    background: #ffd814;
    content: '';
}

.orders-tabs a:hover,
.orders-empty a:hover,
.orders-card__code a:hover {
    color: #c7511f;
    text-decoration: underline;
}

.orders-period {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex: none;
    min-height: 38px;
    border: 1px solid #d5d9d9;
    background: #ffffff;
    padding: 0 13px;
    color: #0f1111;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.orders-period:hover {
    background: #f7fafa;
}

.orders-empty {
    display: grid;
    place-items: center;
    min-height: 280px;
    border: 1px solid #d5d9d9;
    background: #ffffff;
    padding: 34px 20px;
    text-align: center;
    color: #0f1111;
    font-size: 17px;
    font-weight: 700;
    line-height: 26px;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.orders-empty i {
    display: grid;
    width: 54px;
    height: 54px;
    place-items: center;
    border-radius: 50%;
    background: #f7fafa;
    color: #007185;
    font-size: 22px;
}

.orders-empty a {
    color: #007185;
    font-weight: 500;
    text-decoration: none;
}

.orders-list {
    display: grid;
    gap: 16px;
}

.orders-card {
    overflow: hidden;
    border: 1px solid #d5d9d9;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.orders-card__header {
    display: grid;
    grid-template-columns: minmax(130px, 0.9fr) minmax(130px, 0.9fr) minmax(150px, 1fr) minmax(220px, 1.2fr);
    gap: 16px;
    border-bottom: 1px solid #d5d9d9;
    background: #f7fafa;
    padding: 16px 18px;
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
.orders-card__item strong {
    display: block;
    color: #0f1111;
    font-size: 15px;
    line-height: 20px;
}

.orders-pay-btn {
    display: inline-flex;
    width: fit-content;
    align-items: center;
    justify-content: center;
    min-height: 30px;
    margin-top: 8px;
    padding: 0 14px;

    font-size: 13px;

    line-height: 18px;
    cursor: pointer;
}

.orders-pay-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.orders-card__code {
    text-align: right;
}

.orders-card__code a {
    display: inline-flex;
    margin-top: 2px;
    color: #007185;
    font-size: 14px;
    line-height: 20px;
    text-decoration: none;
}

.orders-card__body {
    display: grid;
    grid-template-columns: 170px minmax(0, 1fr) auto;
    align-items: start;
    gap: 24px;
    padding: 18px;
}

.orders-card__status {
    display: grid;
    gap: 8px;
}

.orders-card__status strong {
    display: inline-flex;
    width: fit-content;
    align-items: center;
    min-height: 30px;
    border-radius: 999px;
    padding: 0 12px;
    font-size: 14px;
    font-weight: 700;
    line-height: 18px;
}

.orders-status--pending {
    background: #fff4d6;
    color: #7a4d00;
}

.orders-status--completed {
    background: #e9f8ef;
    color: #0f6b3f;
}

.orders-status--cancelled {
    background: #ffe8e8;
    color: #a61b1b;
}

.orders-card__items {
    display: grid;
    gap: 12px;
}

.orders-card__item {
    display: grid;
    grid-template-columns: 76px minmax(0, 1fr);
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.orders-card__item img,
.orders-card__item-placeholder {
    width: 76px;
    height: 76px;
    border: 1px solid #e3e6e6;
    border-radius: 4px;
    background: #f7fafa;
    object-fit: cover;
}

.orders-card__item-placeholder {
    display: grid;
    place-items: center;
    color: #6f7373;
    font-size: 22px;
}

.orders-card__item strong {
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.orders-card__actions {
    display: grid;
    gap: 10px;
    min-width: 176px;
}

.orders-card__actions a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    border-radius: 999px;
    padding: 0 16px;
    font-size: 14px;
    font-weight: 700;
    line-height: 18px;
    text-decoration: none;
    cursor: pointer;
}

@media (max-width: 900px) {
    .orders-page__inner {
        padding: 18px 18px 64px;
    }

    .orders-hero {
        grid-template-columns: 1fr;
        gap: 18px;
        padding: 24px;
    }

    .orders-search {
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .orders-summary,
    .orders-card__header,
    .orders-card__body {
        grid-template-columns: 1fr;
    }

    .orders-toolbar {
        align-items: stretch;
        flex-direction: column;
        padding: 0 16px 16px;
    }

    .orders-tabs {
        width: 100%;
    }

    .orders-period {
        width: fit-content;
    }

    .orders-card__actions {
        min-width: 0;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .orders-card__code {
        text-align: left;
    }
}

@media (max-width: 640px) {
    .orders-page__inner {
        padding: 16px 14px 46px;
    }

    .orders-hero {
        padding: 20px 16px;
    }

    .orders-hero h1 {
        font-size: 30px;
        line-height: 36px;
    }

    .orders-search {
        grid-template-columns: 1fr;
    }

    .orders-search button {
        width: 100%;
    }

    .orders-summary__item {
        padding: 15px 16px;
    }

    .orders-summary__item p {
        font-size: 24px;
        line-height: 30px;
    }

    .orders-card__actions {
        grid-template-columns: 1fr;
    }

    .orders-card__item {
        grid-template-columns: 64px minmax(0, 1fr);
    }

    .orders-card__item img,
    .orders-card__item-placeholder {
        width: 64px;
        height: 64px;
    }
}
</style>
