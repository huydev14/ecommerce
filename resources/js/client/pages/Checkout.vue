<script setup>
import { computed, ref } from 'vue';
import { APP_CONFIG } from '@/config';

const promoCode = ref('');

const deliveryAddress = {
    name: 'Nguyễn Gia Huy',
    lines: ['World Trade Center, 37 Nguyễn Thị Minh Khai', 'Phường Bến Nghé, Quận 1', 'TP. Hồ Chí Minh 700000'],
    phone: '0903 237 900',
};

const paymentInfo = {
    method: 'Thanh toán khi nhận hàng',
    note: 'Không cần thẻ. Thanh toán trực tiếp cho đơn vị vận chuyển.',
};

const checkoutItems = [
    {
        id: 1,
        name: 'Sách Chiếu Bóng - Cinema Book',
        variant: 'Bìa mềm',
        seller: APP_CONFIG.appName,
        image: '/img/default-image.jpg',
        price: 120000,
        quantity: 1,
        stockLabel: 'Còn 12 sản phẩm',
        deliveryWindow: '27 Jun 2026 - 29 Jun 2026',
        shippingSpeed: '2-4 Business Days',
    },
    {
        id: 2,
        name: 'Truyện Cổ Andersen',
        variant: 'Ấn bản tiêu chuẩn',
        seller: APP_CONFIG.appName,
        image: '/img/default-image.jpg',
        price: 142857,
        quantity: 1,
        stockLabel: 'Còn hàng',
        deliveryWindow: '28 Jun 2026 - 30 Jun 2026',
        shippingSpeed: 'Standard Delivery',
    },
];

const formatCurrency = (value) =>
    new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const itemSubtotal = computed(() => checkoutItems.reduce((total, item) => total + item.price * item.quantity, 0));
const deliveryFee = computed(() => 0);
const promotionAmount = computed(() => Math.round(itemSubtotal.value * 0.08));
const orderTotal = computed(() => itemSubtotal.value + deliveryFee.value - promotionAmount.value);
</script>

<template>
    <section class="checkout-page">
        <div class="checkout-shell">
            <div class="checkout-layout">
                <main class="checkout-main">
                    <header class="checkout-title">
                        <h1>Review Your Order</h1>
                        <p>Vui lòng kiểm tra địa chỉ, thanh toán và sản phẩm trước khi đặt hàng.</p>
                    </header>

                    <section class="checkout-panel checkout-info-grid" aria-label="Order information">
                        <article class="checkout-info-card">
                            <div class="checkout-section-title">
                                <h2>Delivery Address</h2>
                                <button type="button">Change</button>
                            </div>
                            <address>
                                <strong>{{ deliveryAddress.name }}</strong>
                                <span v-for="line in deliveryAddress.lines" :key="line">{{ line }}</span>
                                <span>Phone: {{ deliveryAddress.phone }}</span>
                            </address>
                        </article>

                        <article class="checkout-info-card">
                            <div class="checkout-section-title">
                                <h2>Payment Information</h2>
                                <button type="button">Change</button>
                            </div>
                            <dl>
                                <div>
                                    <dt>Payment method</dt>
                                    <dd>{{ paymentInfo.method }}</dd>
                                </div>
                                <div>
                                    <dt>Note</dt>
                                    <dd>{{ paymentInfo.note }}</dd>
                                </div>
                            </dl>
                        </article>

                        <article class="checkout-info-card checkout-promo">
                            <h2>Enter a promotional code.</h2>
                            <div class="checkout-promo__form">
                                <input v-model="promoCode" type="text" placeholder="Enter Code" aria-label="Promotional code" />
                                <button type="button">Apply</button>
                            </div>
                        </article>
                    </section>

                    <section class="checkout-panel checkout-shipment" aria-label="Shipment details">
                        <div class="checkout-section-title">
                            <div>
                                <h2>Shipment details</h2>
                                <p>Estimated delivery: <strong>27 Jun 2026 - 30 Jun 2026</strong></p>
                            </div>
                            <span>Delivered by {{ APP_CONFIG.appName }} Logistics</span>
                        </div>

                        <article v-for="item in checkoutItems" :key="item.id" class="checkout-item">
                            <RouterLink :to="{ name: 'ProductList' }" class="checkout-item__image">
                                <img :src="item.image" :alt="item.name" />
                            </RouterLink>

                            <div class="checkout-item__details">
                                <RouterLink :to="{ name: 'ProductList' }" class="checkout-item__name">{{ item.name }}</RouterLink>
                                <p>{{ item.variant }}</p>
                                <p>Sold by {{ item.seller }}</p>
                                <strong>{{ formatCurrency(item.price) }}</strong>
                                <span>{{ item.stockLabel }}</span>
                            </div>

                            <div class="checkout-item__shipping">
                                <h3>Choose a shipping speed:</h3>
                                <label>
                                    <input type="radio" checked />
                                    <span>{{ item.shippingSpeed }} (FREE Delivery)</span>
                                </label>
                                <small>{{ item.deliveryWindow }}</small>
                            </div>
                        </article>
                    </section>
                </main>

                <aside class="checkout-summary" aria-label="Order summary">
                    <RouterLink :to="{ name: 'OrderSuccess' }" class="checkout-place-order">Place your order</RouterLink>

                    <section class="checkout-summary__card">
                        <h2>Order summary</h2>
                        <dl>
                            <div>
                                <dt>Items:</dt>
                                <dd>{{ formatCurrency(itemSubtotal) }}</dd>
                            </div>
                            <div>
                                <dt>Delivery:</dt>
                                <dd>{{ formatCurrency(deliveryFee) }}</dd>
                            </div>
                            <div>
                                <dt>Total:</dt>
                                <dd>{{ formatCurrency(itemSubtotal + deliveryFee) }}</dd>
                            </div>
                            <div class="is-discount">
                                <dt>Promotion Applied:</dt>
                                <dd>-{{ formatCurrency(promotionAmount) }}</dd>
                            </div>
                            <div class="is-total">
                                <dt>Order Total:</dt>
                                <dd>{{ formatCurrency(orderTotal) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="checkout-promotion-box">
                        <h3>Promotions applied:</h3>
                        <ul>
                            <li>Extra Discount</li>
                            <li>Free Delivery</li>
                        </ul>
                        <a href="#">How are delivery costs calculated?</a>
                    </section>
                </aside>
            </div>
        </div>
    </section>
</template>

<style scoped>
.checkout-page {
    min-height: 100vh;
    background: #eaeded;
    color: #111827;
    font-family: Arial, Helvetica, sans-serif;
}

.checkout-shell {
    max-width: 1240px;
    margin: 0 auto;
    padding: 16px;
}

.checkout-progress {
    display: flex;
    align-items: center;
    gap: 28px;
    padding: 10px 0 16px;
}

.checkout-brand {
    flex: none;
    color: #111827;
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    text-decoration: none;
}

.checkout-progress ol {
    display: grid;
    flex: 1;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin: 0;
    padding: 0;
    list-style: none;
}

.checkout-progress li {
    position: relative;
    display: flex;
    justify-content: center;
    border-top: 3px solid #d5d9d9;
    padding-top: 8px;
    color: #8a8f98;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.checkout-progress li::before {
    position: absolute;
    top: -8px;
    width: 12px;
    height: 12px;
    border: 2px solid currentColor;
    border-radius: 50%;
    background: #fff;
    content: '';
}

.checkout-progress li.is-complete,
.checkout-progress li.is-current {
    border-color: #f3a847;
    color: #b45f06;
}

.checkout-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 18px;
    align-items: start;
}

.checkout-main {
    min-width: 0;
}

.checkout-title {
    margin-bottom: 12px;
    padding: 18px 20px;
    background: #fff;
}

.checkout-title h1 {
    margin: 0;
    color: #111827;
    font-size: 28px;
    font-weight: 400;
}

.checkout-title p {
    margin: 6px 0 0;
    color: #565959;
    font-size: 13px;
}

.checkout-panel,
.checkout-summary,
.checkout-summary__card,
.checkout-promotion-box {
    border: 1px solid #d5d9d9;
    background: #fff;
}

.checkout-info-grid {
    display: grid;
    grid-template-columns: 1.1fr 1fr 1.1fr;
    gap: 0;
}

.checkout-info-card {
    min-width: 0;
    padding: 18px;
}

.checkout-info-card + .checkout-info-card {
    border-left: 1px solid #e7e7e7;
}

.checkout-section-title {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.checkout-section-title h2,
.checkout-info-card h2,
.checkout-summary h2 {
    margin: 0;
    font-size: 20px;
    line-height: 1.25;
}

.checkout-section-title p,
.checkout-section-title span {
    margin: 5px 0 0;
    color: #565959;
    font-size: 13px;
}

.checkout-section-title p strong {
    color: #0f8a00;
}

.checkout-section-title button,
.checkout-promo__form button {
    border: 1px solid #adb1b8;
    border-radius: 3px;
    background: linear-gradient(#fff, #e7e9ec);
    color: #111827;
    cursor: pointer;
    font-size: 12px;
}

.checkout-section-title button {
    padding: 4px 9px;
}

.checkout-info-card address {
    display: grid;
    gap: 3px;
    margin-top: 12px;
    color: #111827;
    font-style: normal;
    font-size: 13px;
    line-height: 1.35;
}

.checkout-info-card dl,
.checkout-summary dl {
    margin: 12px 0 0;
}

.checkout-info-card dl div,
.checkout-summary dl div {
    display: flex;
    justify-content: space-between;
    gap: 16px;
}

.checkout-info-card dt {
    flex: none;
    color: #565959;
    font-size: 12px;
    font-weight: 700;
}

.checkout-info-card dd {
    margin: 0;
    color: #111827;
    text-align: right;
    font-size: 13px;
}

.checkout-promo__form {
    display: flex;
    gap: 6px;
    margin-top: 16px;
}

.checkout-promo__form input {
    min-width: 0;
    flex: 1;
    border: 1px solid #a6a6a6;
    border-radius: 3px;
    padding: 8px 10px;
    font-size: 13px;
}

.checkout-promo__form button {
    flex: none;
    padding: 0 12px;
}

.checkout-shipment {
    margin-top: 12px;
    padding: 18px;
}

.checkout-item {
    display: grid;
    grid-template-columns: 92px minmax(0, 1fr) 260px;
    gap: 16px;
    border-top: 1px solid #e7e7e7;
    padding: 18px 0;
}

.checkout-item:last-child {
    padding-bottom: 0;
}

.checkout-item__image {
    display: grid;
    height: 92px;
    place-items: center;
    background: #f7f7f7;
}

.checkout-item__image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.checkout-item__details {
    display: grid;
    align-content: start;
    gap: 3px;
    font-size: 13px;
}

.checkout-item__name {
    color: #007185;
    font-size: 15px;
    line-height: 1.35;
    text-decoration: none;
}

.checkout-item__name:hover,
.checkout-promotion-box a:hover {
    color: #c45500;
    text-decoration: underline;
}

.checkout-item__details p,
.checkout-item__details strong,
.checkout-item__details span {
    margin: 0;
}

.checkout-item__details strong {
    color: #b12704;
}

.checkout-item__details span {
    color: #b12704;
    font-size: 12px;
}

.checkout-item__shipping h3 {
    margin: 0 0 8px;
    font-size: 13px;
}

.checkout-item__shipping label {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    color: #111827;
    font-size: 13px;
    line-height: 1.35;
}

.checkout-item__shipping input {
    margin-top: 2px;
}

.checkout-item__shipping small {
    display: block;
    margin-top: 6px;
    color: #0f8a00;
}

.checkout-summary {
    position: sticky;
    top: 16px;
    padding: 14px;
}

.checkout-place-order {
    width: 100%;
    min-height: 34px;
    border: 1px solid #a88734;
    border-radius: 3px;
    background: linear-gradient(#f7dfa5, #f0c14b);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.45);
    color: #111827;
    cursor: pointer;
    font-size: 13px;
    padding: 8px 10px;
}

.checkout-place-order:hover {
    background: linear-gradient(#f5d78e, #eeb933);
}

.checkout-summary__card {
    margin-top: 14px;
    padding: 14px;
}

.checkout-summary dt,
.checkout-summary dd {
    margin: 0;
    font-size: 13px;
    line-height: 1.75;
}

.checkout-summary dd {
    text-align: right;
}

.checkout-summary .is-discount {
    margin-top: 6px;
    color: #b12704;
}

.checkout-summary .is-total {
    margin-top: 10px;
    border-top: 1px solid #e7e7e7;
    padding-top: 10px;
    color: #b12704;
    font-weight: 700;
}

.checkout-summary .is-total dt,
.checkout-summary .is-total dd {
    font-size: 17px;
}

.checkout-promotion-box {
    margin-top: 14px;
    padding: 12px;
}

.checkout-promotion-box h3 {
    margin: 0 0 8px;
    color: #b12704;
    font-size: 13px;
}

.checkout-promotion-box ul {
    margin: 0 0 10px 18px;
    padding: 0;
    color: #565959;
    font-size: 12px;
}

.checkout-promotion-box a {
    color: #007185;
    font-size: 12px;
    text-decoration: none;
}

@media (max-width: 980px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }

    .checkout-summary {
        position: static;
        order: -1;
    }

    .checkout-info-grid {
        grid-template-columns: 1fr;
    }

    .checkout-info-card + .checkout-info-card {
        border-top: 1px solid #e7e7e7;
        border-left: 0;
    }
}

@media (max-width: 720px) {
    .checkout-shell {
        padding: 10px;
    }

    .checkout-progress {
        display: block;
    }

    .checkout-progress ol {
        margin-top: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        row-gap: 18px;
    }

    .checkout-title h1 {
        font-size: 24px;
    }

    .checkout-item {
        grid-template-columns: 76px minmax(0, 1fr);
        gap: 12px;
    }

    .checkout-item__image {
        height: 76px;
    }

    .checkout-item__shipping {
        grid-column: 1 / -1;
        border-top: 1px solid #f0f2f2;
        padding-top: 12px;
    }
}
</style>
