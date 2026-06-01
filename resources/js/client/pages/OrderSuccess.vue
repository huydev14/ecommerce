<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { APP_CONFIG } from '@/config';
import api from '@/services/api';

const route = useRoute();
const order = ref({
    code: '',
    placedAt: '',
    customerName: '',
    email: '',
    deliveryAddress: [],
    estimate_shipping_date: '',
    paymentMethod: '',
    total: 0,
    items: [],
});

const formatCurrency = (value) =>
    new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const itemCount = computed(() => order.value.items.reduce((total, item) => total + item.quantity, 0));

const fetchOrder = async () => {
    const orderNumber = route.query.order;

    if (!orderNumber) {
        return;
    }

    const response = await api.get(`/orders/${orderNumber}`);
    const data = response.data.data || {};

    order.value = {
        code: data.order_number || '',
        placedAt: data.placed_at || '',
        customerName: data.customer_name || '',
        email: data.customer_email || '',
        deliveryAddress: data.delivery_address || [],
        estimate_shipping_date: data.estimate_shipping_date || '',
        paymentMethod: data.payment_method || '',
        total: data.total || 0,
        items: data.items || [],
    };
};

onMounted(fetchOrder);
</script>

<template>
    <section class="order-success-page">
        <div class="order-success-shell">
            <main class="order-success-card">
                <div class="order-success-hero">
                    <div class="order-success-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path
                                d="M20 6L9 17l-5-5"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="order-success-eyebrow">Order placed</p>
                        <h1>Cảm ơn bạn, {{ order.customerName }}.</h1>
                        <p>
                            Đơn hàng của bạn đã được ghi nhận. Chúng tôi đã gửi thông tin xác nhận đến
                            <strong>{{ order.email }}</strong
                            >.
                        </p>
                    </div>
                </div>

                <section class="order-success-summary" aria-label="Order summary">
                    <div>
                        <span>Order number</span>
                        <strong>{{ order.code }}</strong>
                    </div>
                    <div>
                        <span>Placed at</span>
                        <strong>{{ order.placedAt }}</strong>
                    </div>
                    <div>
                        <span>Total</span>
                        <strong>{{ formatCurrency(order.total) }}</strong>
                    </div>
                </section>

                <div class="order-success-grid">
                    <section class="order-success-panel">
                        <h2>Delivery details</h2>
                        <dl>
                            <div>
                                <dt>Estimated delivery</dt>
                                <dd>{{ order.estimate_shipping_date }}</dd>
                            </div>
                            <div>
                                <dt>Ship to</dt>
                                <dd>
                                    <span v-for="line in order.deliveryAddress" :key="line">{{ line }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt>Payment</dt>
                                <dd>{{ order.paymentMethod }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="order-success-panel">
                        <h2>What happens next</h2>
                        <ol class="order-success-steps">
                            <li class="is-active">
                                <strong>Order confirmed</strong>
                                <span>{{ APP_CONFIG.appName }} is preparing your order.</span>
                            </li>
                            <li>
                                <strong>Shipping update</strong>
                                <span>You will receive tracking information when the package is on the way.</span>
                            </li>
                            <li>
                                <strong>Delivery</strong>
                                <span>Payment will be collected when your order arrives.</span>
                            </li>
                        </ol>
                    </section>
                </div>

                <section class="order-success-panel order-success-items">
                    <div class="order-success-section-heading">
                        <h2>Items in this order</h2>
                        <span>{{ itemCount }} sản phẩm</span>
                    </div>

                    <article v-for="item in order.items" :key="item.id" class="order-success-item">
                        <RouterLink :to="{ name: 'ProductList' }" class="order-success-item__image">
                            <img :src="item.image" :alt="item.name" />
                        </RouterLink>
                        <div>
                            <RouterLink :to="{ name: 'ProductList' }" class="order-success-item__name">{{ item.name }}</RouterLink>
                            <p>Quantity: {{ item.quantity }}</p>
                        </div>
                        <strong>{{ formatCurrency(item.price) }}</strong>
                    </article>
                </section>

                <div class="order-success-actions">
                    <RouterLink :to="{ name: 'MyAccountOrders' }" class="order-success-primary">View your orders</RouterLink>
                    <RouterLink :to="{ name: 'Home' }" class="order-success-secondary">Continue shopping</RouterLink>
                </div>
            </main>
        </div>
    </section>
</template>

<style scoped>
.order-success-page {
    min-height: 100vh;
    background: #eaeded;
    color: #111827;
    font-family: Arial, Helvetica, sans-serif;
}

.order-success-shell {
    max-width: 1100px;
    margin: 0 auto;
    padding: 28px 16px;
}

.order-success-card {
    border: 1px solid #d5d9d9;
    background: #fff;
}

.order-success-hero {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 28px;
    border-bottom: 1px solid #e7e7e7;
}

.order-success-icon {
    display: grid;
    width: 54px;
    height: 54px;
    flex: none;
    place-items: center;
    border-radius: 50%;
    background: #067d62;
    color: #fff;
}

.order-success-icon svg {
    width: 30px;
    height: 30px;
}

.order-success-eyebrow {
    margin: 0 0 5px;
    color: #067d62;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
}

.order-success-hero h1 {
    margin: 0;
    color: #111827;
    font-size: 30px;
    font-weight: 400;
    line-height: 1.2;
}

.order-success-hero p:last-child {
    max-width: 760px;
    margin: 8px 0 0;
    color: #565959;
    font-size: 15px;
    line-height: 1.45;
}

.order-success-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    border-bottom: 1px solid #e7e7e7;
}

.order-success-summary div {
    display: grid;
    gap: 5px;
    padding: 16px 22px;
}

.order-success-summary div + div {
    border-left: 1px solid #e7e7e7;
}

.order-success-summary span,
.order-success-section-heading span,
.order-success-item p {
    color: #565959;
    font-size: 13px;
}

.order-success-summary strong {
    font-size: 16px;
}

.order-success-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 18px;
}

.order-success-panel {
    border: 1px solid #d5d9d9;
    padding: 18px;
}

.order-success-panel h2 {
    margin: 0 0 14px;
    font-size: 20px;
    line-height: 1.25;
}

.order-success-panel dl {
    display: grid;
    gap: 14px;
    margin: 0;
}

.order-success-panel dl div {
    display: grid;
    gap: 4px;
}

.order-success-panel dt {
    color: #565959;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.order-success-panel dd {
    display: grid;
    gap: 2px;
    margin: 0;
    color: #111827;
    font-size: 14px;
    line-height: 1.4;
}

.order-success-steps {
    display: grid;
    gap: 14px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.order-success-steps li {
    position: relative;
    display: grid;
    gap: 4px;
    padding-left: 28px;
}

.order-success-steps li::before {
    position: absolute;
    top: 2px;
    left: 0;
    width: 12px;
    height: 12px;
    border: 2px solid #d5d9d9;
    border-radius: 50%;
    background: #fff;
    content: '';
}

.order-success-steps li.is-active::before {
    border-color: #067d62;
    background: #067d62;
}

.order-success-steps strong {
    font-size: 14px;
}

.order-success-steps span {
    color: #565959;
    font-size: 13px;
    line-height: 1.4;
}

.order-success-items {
    margin: 0 18px 18px;
}

.order-success-section-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #e7e7e7;
    padding-bottom: 12px;
}

.order-success-section-heading h2 {
    margin-bottom: 0;
}

.order-success-item {
    display: grid;
    grid-template-columns: 76px minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    padding: 14px 0;
}

.order-success-item + .order-success-item {
    border-top: 1px solid #f0f2f2;
}

.order-success-item__image {
    display: grid;
    height: 76px;
    place-items: center;
    background: #f7f7f7;
}

.order-success-item__image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.order-success-item__name {
    color: #007185;
    font-size: 15px;
    line-height: 1.35;
    text-decoration: none;
}

.order-success-item__name:hover,
.order-success-secondary:hover {
    color: #c45500;
    text-decoration: underline;
}

.order-success-item p {
    margin: 5px 0 0;
}

.order-success-item strong {
    text-align: right;
    color: #b12704;
}

.order-success-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 0 18px 22px;
}

.order-success-primary,
.order-success-secondary {
    display: inline-flex;
    min-height: 38px;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 0 18px;
    font-size: 14px;
    text-decoration: none;
}

.order-success-primary {
    border: 1px solid #ffd814;
    background: #ffd814;
    color: #111827;
}

.order-success-primary:hover {
    border-color: #f7ca00;
    background: #f7ca00;
}

.order-success-secondary {
    border: 1px solid #d5d9d9;
    background: #fff;
    color: #007185;
}

@media (max-width: 820px) {
    .order-success-summary,
    .order-success-grid {
        grid-template-columns: 1fr;
    }

    .order-success-summary div + div {
        border-top: 1px solid #e7e7e7;
        border-left: 0;
    }
}

@media (max-width: 560px) {
    .order-success-shell {
        padding: 12px;
    }

    .order-success-hero {
        display: grid;
        padding: 20px;
    }

    .order-success-hero h1 {
        font-size: 24px;
    }

    .order-success-grid,
    .order-success-items {
        margin: 0;
        padding: 12px;
    }

    .order-success-item {
        grid-template-columns: 64px minmax(0, 1fr);
    }

    .order-success-item__image {
        height: 64px;
    }

    .order-success-item strong {
        grid-column: 2;
        text-align: left;
    }

    .order-success-actions {
        display: grid;
        padding: 0 12px 16px;
    }
}
</style>
