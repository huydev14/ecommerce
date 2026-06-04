<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { APP_CONFIG } from '@/config';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const cartStore = useCartStore();
const { t } = useI18n();
const promoCode = ref('');
const customerAddresses = ref([]);
const selectedAddressId = ref('');
const selectedPaymentMethod = ref('cod');
const orderNote = ref('');
const checkoutReview = ref({
    items: [],
    address: null,
    summary: {
        subtotal: 0,
        shipping_fee: 0,
        discount: 0,
        total: 0,
    },
    has_address: false,
});
const isLoadingCheckout = ref(false);
const checkoutError = ref('');
const isProcessing = ref(false);
let isSyncingAddressFromApi = false;

const paymentMethods = computed(() => [
    {
        value: 'cod',
        label: t('checkout.payment_cod_label'),
        note: t('checkout.payment_cod_note'),
    },
    {
        value: 'vnpay',
        label: 'VNPay',
        note: t('checkout.payment_vnpay_note'),
    },
    // {
    //     value: 'momo',
    //     label: 'MoMo',
    //     note: t('checkout.payment_momo_note'),
    // },
]);

const formatCurrency = (value) =>
    new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const checkoutItems = computed(() =>
    checkoutReview.value.items.map((item) => {
        const price = Number(item.price || 0);
        const quantity = Number(item.quantity || 0);

        return {
            id: item.cart_item_id || item.product_variant_id,
            name: item.product_name || t('checkout.fallback_productName'),
            variant: item.sku ? `SKU: ${item.sku}` : '',
            brand: item.brand.name || APP_CONFIG.appName,
            image: item.thumbnail || null,
            price,
            quantity,
            lineTotal: Number(item.line_total || price * quantity),
            estimateShippingDate: item.estimate_shipping_date || '',
            shippingSpeed: t('checkout.shipping_standardDelivery'),
        };
    }),
);
const checkoutSummary = computed(() => checkoutReview.value.summary || {});
const itemSubtotal = computed(() => Number(checkoutSummary.value.subtotal || 0));
const deliveryFee = computed(() => Number(checkoutSummary.value.shipping_fee || 0));
const promotionAmount = computed(() => Number(checkoutSummary.value.discount || 0));
const orderTotal = computed(() => Number(checkoutSummary.value.total || itemSubtotal.value + deliveryFee.value - promotionAmount.value));
const selectedAddress = computed(
    () =>
        customerAddresses.value.find((address) => String(address.id) === String(selectedAddressId.value)) ||
        checkoutReview.value.address ||
        null,
);
const selectedPaymentInfo = computed(
    () => paymentMethods.value.find((method) => method.value === selectedPaymentMethod.value) || paymentMethods.value[0],
);
const selectedAddressLines = computed(() => {
    if (!selectedAddress.value) {
        return [];
    }

    return [
        selectedAddress.value.specific_address,
        [selectedAddress.value.ward_name, selectedAddress.value.district_name].filter(Boolean).join(', '),
        selectedAddress.value.province_name,
    ].filter(Boolean);
});

const redirectToLogin = () => {
    authStore.user = null;
    authStore.token = null;
    router.replace({ name: 'Login', query: { redirect: route.fullPath } });
};

const createVnpayPayment = async (orderId) => {
    if (!orderId) {
        throw new Error('Missing VNPAY order id.');
    }

    const response = await api.post(`/payment/vnpay/${orderId}`);
    const paymentUrl = response.data?.data?.payment_url;

    if (!response.data?.success || !paymentUrl) {
        throw new Error(response.data?.message || 'Unable to create VNPAY payment.');
    }

    window.location.href = paymentUrl;
};

const fetchCheckoutReview = async (addressId = selectedAddressId.value) => {
    isLoadingCheckout.value = true;
    checkoutError.value = '';

    try {
        const response = await api.get('/checkout/', {
            params: addressId ? { customer_address_id: addressId } : {},
        });
        const data = response.data.data || {};

        checkoutReview.value = {
            items: data.items || [],
            address: data.address || null,
            addresses: data.addresses || [],
            summary: {
                subtotal: data.summary?.subtotal || 0,
                shipping_fee: data.summary?.shipping_fee || 0,
                discount: data.summary?.discount || 0,
                total: data.summary?.total || 0,
            },
            has_address: Boolean(data.has_address),
        };
        customerAddresses.value = data.addresses || [];
        isSyncingAddressFromApi = true;
        selectedAddressId.value = data.address?.id || customerAddresses.value[0]?.id || '';
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        checkoutError.value = error.response?.data?.message || t('checkout.errors_fetchCheckout');
    } finally {
        isSyncingAddressFromApi = false;
        isLoadingCheckout.value = false;
    }
};

const placeOrder = async () => {
    if (isProcessing.value) {
        return;
    }

    if (!selectedAddressId.value) {
        checkoutError.value = t('checkout.errors_selectAddress');
        return;
    }

    isProcessing.value = true;
    checkoutError.value = '';

    try {
        const response = await api.post('/checkout', {
            customer_address_id: selectedAddressId.value,
            payment_method: selectedPaymentMethod.value,
            note: orderNote.value,
        });
        const data = response.data.data || {};
        cartStore.clearCart();

        if (selectedPaymentMethod.value === 'vnpay') {
            await createVnpayPayment(data.order_id);
            return;
        }

        if (data.payment_url) {
            window.location.href = data.payment_url;
            return;
        }

        await router.push({
            name: 'OrderSuccess',
            query: data.order_number ? { order: data.order_number } : {},
        });
    } catch (error) {
        if (error.response?.status === 401) {
            redirectToLogin();
            return;
        }

        checkoutError.value = error.response?.data?.message || t('checkout.errors_placeOrder');
    } finally {
        isProcessing.value = false;
    }
};

onMounted(fetchCheckoutReview);

watch(selectedAddressId, (addressId, previousAddressId) => {
    if (!addressId || !previousAddressId || isSyncingAddressFromApi) {
        return;
    }

    fetchCheckoutReview(addressId);
});
</script>

<template>
    <section class="checkout-page">
        <div class="checkout-shell">
            <div class="checkout-layout">
                <main class="checkout-main">
                    <header class="checkout-title">
                        <h1>{{ t('checkout.title') }}</h1>
                        <p>{{ t('checkout.subtitle') }}</p>
                    </header>

                    <section class="checkout-panel checkout-info-grid" :aria-label="t('checkout.aria_orderInfo')">
                        <article class="checkout-info-card">
                            <div class="checkout-section-title">
                                <h2>{{ t('checkout.address_title') }}</h2>
                                <RouterLink :to="{ name: 'CustomerAddresses' }" class="checkout-section-link">
                                    {{ t('checkout.address_manage') }}
                                </RouterLink>
                            </div>

                            <div v-if="isLoadingCheckout" class="checkout-muted">{{ t('checkout.address_loading') }}</div>
                            <div v-else-if="checkoutError" class="checkout-alert">{{ checkoutError }}</div>
                            <div v-else-if="!selectedAddress" class="checkout-empty">
                                <p>{{ t('checkout.address_empty') }}</p>
                                <RouterLink :to="{ name: 'CustomerAddresses' }">{{ t('checkout.address_add') }}</RouterLink>
                            </div>
                            <label v-else class="checkout-select-field">
                                <span>{{ t('checkout.address_shipTo') }}</span>
                                <select v-model="selectedAddressId">
                                    <option v-for="address in customerAddresses" :key="address.id" :value="address.id">
                                        {{ address.label }} - {{ address.receiver_name }}
                                    </option>
                                </select>
                            </label>
                            <address v-if="selectedAddress">
                                <span v-if="selectedAddress.label">{{ selectedAddress.label }}</span>
                                <strong>{{ selectedAddress.receiver_name }}</strong>
                                <span v-for="line in selectedAddressLines" :key="line">{{ line }}</span>
                                <span>{{ t('checkout.address_phone', { phone: selectedAddress.receiver_phone }) }}</span>
                                <span v-if="selectedAddress.delivery_note">{{ t('checkout.address_note', { note: selectedAddress.delivery_note }) }}</span>
                            </address>
                        </article>

                        <article class="checkout-info-card">
                            <div class="checkout-section-title">
                                <h2>{{ t('checkout.payment_title') }}</h2>
                            </div>
                            <div class="checkout-payment-options">
                                <label v-for="method in paymentMethods" :key="method.value" class="checkout-payment-option">
                                    <input v-model="selectedPaymentMethod" type="radio" name="payment_method" :value="method.value" />
                                    <span>
                                        <strong>{{ method.label }}</strong>
                                        <small>{{ method.note }}</small>
                                    </span>
                                </label>
                            </div>
                            <label class="checkout-note-field">
                                <span>{{ t('checkout.orderNote_label') }}</span>
                                <textarea v-model.trim="orderNote" rows="2" :placeholder="t('checkout.orderNote_placeholder')"></textarea>
                            </label>
                        </article>

                        <article class="checkout-info-card checkout-promo">
                            <h2>{{ t('checkout.promo_title') }}</h2>
                            <p>{{ selectedPaymentInfo.note }}</p>
                            <div class="checkout-promo__form">
                                <input
                                    v-model="promoCode"
                                    type="text"
                                    :placeholder="t('checkout.promo_placeholder')"
                                    :aria-label="t('checkout.aria_promoCode')"
                                />
                                <button type="button">{{ t('checkout.promo_apply') }}</button>
                            </div>
                        </article>
                    </section>

                    <section class="checkout-panel checkout-shipment" :aria-label="t('checkout.aria_shipmentDetails')">
                        <div class="checkout-section-title">
                            <div>
                                <h2>{{ t('checkout.shipment_title') }}</h2>
                                <p v-html="t('checkout.shipment_estimatedDelivery', { date: '27 Jun 2026 - 30 Jun 2026' })"></p>
                            </div>
                            <span>{{ t('checkout.shipment_deliveredBy') }}</span>
                        </div>

                        <div v-if="isLoadingCheckout" class="checkout-muted">{{ t('checkout.shipment_loadingProducts') }}</div>
                        <div v-else-if="checkoutError" class="checkout-alert">{{ checkoutError }}</div>
                        <div v-else-if="!checkoutItems.length" class="checkout-empty">
                            <p>{{ t('checkout.shipment_emptyCart') }}</p>
                            <RouterLink :to="{ name: 'ProductList' }">{{ t('cart.continueShopping') }}</RouterLink>
                        </div>

                        <template v-else>
                            <article v-for="item in checkoutItems" :key="item.id" class="checkout-item">
                                <RouterLink :to="{ name: 'ProductList' }" class="checkout-item__image">
                                    <img v-if="item.image" :src="item.image" :alt="item.name" />
                                </RouterLink>

                                <div class="checkout-item__details">
                                    <RouterLink :to="{ name: 'ProductList' }" class="checkout-item__name">{{ item.name }}</RouterLink>
                                    <p v-if="item.variant">{{ item.variant }}</p>
                                    <p>{{ t('checkout.item_soldBy', { brand: item.brand }) }}</p>
                                    <strong>{{ formatCurrency(item.price) }}</strong>
                                    <p>{{ t('checkout.item_quantity', { quantity: item.quantity }) }}</p>
                                </div>

                                <div class="checkout-item__shipping">
                                    <h3>{{ t('checkout.item_chooseShippingSpeed') }}</h3>
                                    <label>
                                        <input type="radio" checked />
                                        <span>{{ t('checkout.item_freeDelivery', { speed: item.shippingSpeed }) }}</span>
                                    </label>
                                    <small>{{ item.estimateShippingDate }}</small>
                                </div>
                            </article>
                        </template>
                    </section>
                </main>

                <aside class="checkout-summary" :aria-label="t('checkout.aria_summary')">
                    <section class="checkout-summary__card">
                        <h2>{{ t('checkout.summary_title') }}</h2>
                        <dl>
                            <div>
                                <dt>{{ t('checkout.summary_items') }}</dt>
                                <dd>{{ formatCurrency(itemSubtotal) }}</dd>
                            </div>
                            <div>
                                <dt>{{ t('checkout.summary_delivery') }}</dt>
                                <dd>{{ formatCurrency(deliveryFee) }}</dd>
                            </div>
                            <div>
                                <dt>{{ t('checkout.summary_total') }}</dt>
                                <dd>{{ formatCurrency(itemSubtotal + deliveryFee) }}</dd>
                            </div>
                            <div class="is-discount">
                                <dt>{{ t('checkout.summary_promotion') }}</dt>
                                <dd>-{{ formatCurrency(promotionAmount) }}</dd>
                            </div>
                            <div class="is-total">
                                <dt>{{ t('checkout.summary_orderTotal') }}</dt>
                                <dd>{{ formatCurrency(orderTotal) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <button @click="placeOrder" :disabled="isProcessing" class="amazon-checkout-btn">
                        <span v-if="isProcessing" class="spinner"></span>
                        <span v-else-if="selectedPaymentMethod === 'vnpay'">Thanh toán qua VNPAY</span>
                        <span v-else>{{ t('checkout.placeOrder') }}</span>
                    </button>
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
.checkout-section-link,
.checkout-promo__form button {
    border: 1px solid #adb1b8;
    border-radius: 3px;
    background: linear-gradient(#fff, #e7e9ec);
    color: #111827;
    cursor: pointer;
    font-size: 12px;
}

.checkout-section-title button,
.checkout-section-link {
    padding: 4px 9px;
    text-decoration: none;
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

.checkout-muted,
.checkout-alert,
.checkout-empty {
    margin-top: 12px;
    font-size: 13px;
    line-height: 1.4;
}

.checkout-muted {
    color: #565959;
}

.checkout-alert {
    color: #b12704;
}

.checkout-empty p {
    margin: 0 0 6px;
    color: #565959;
}

.checkout-empty a {
    color: #007185;
    font-size: 13px;
    text-decoration: none;
}

.checkout-select-field,
.checkout-note-field {
    display: grid;
    gap: 6px;
    margin-top: 12px;
    color: #565959;
    font-size: 12px;
    font-weight: 700;
}

.checkout-select-field select,
.checkout-note-field textarea {
    width: 100%;
    border: 1px solid #a6a6a6;
    border-radius: 3px;
    background-color: #fff;
    color: #111827;
    font: inherit;
    font-size: 13px;
    font-weight: 400;
    outline: none;
}

.checkout-select-field select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%23374151' stroke-width='1.7' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-position: right 10px center;
    background-repeat: no-repeat;
    background-size: 12px 8px;
    padding: 8px 32px 8px 10px;
}

.checkout-note-field textarea {
    resize: vertical;
    padding: 8px 10px;
}

.checkout-select-field select:focus,
.checkout-note-field textarea:focus,
.checkout-promo__form input:focus {
    border-color: #007185;
    box-shadow: 0 0 0 3px rgba(0, 113, 133, 0.16);
}

.checkout-payment-options {
    display: grid;
    gap: 10px;
    margin-top: 12px;
}

.checkout-payment-option {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: #111827;
    font-size: 13px;
    line-height: 1.35;
}

.checkout-payment-option input {
    margin-top: 2px;
    accent-color: #007185;
}

.checkout-payment-option span {
    display: grid;
    gap: 2px;
}

.checkout-payment-option small,
.checkout-promo p {
    color: #565959;
    font-size: 12px;
    line-height: 1.4;
}

.checkout-promo p {
    margin: 8px 0 0;
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

.amazon-checkout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 44px;
    background: #ffd814;
    border: 1px solid #fcd200;
    box-shadow: 0 2px 5px 0 rgba(213, 217, 217, 0.5);
    color: #0f1111;
    cursor: pointer;
    font-size: 15px;
    font-weight: 400;
    line-height: 20px;
    padding: 0 15px;
    text-align: center;
    text-decoration: none;
    transition:
        background-color 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
    box-sizing: border-box;
}

.amazon-checkout-btn:hover:not(:disabled) {
    background: #f7ca00;
    border-color: #f2c200;
}

.amazon-checkout-btn:focus-visible {
    outline: none;
    border-color: #008296;
    box-shadow: 0 0 0 3px rgba(0, 130, 150, 0.4);
}

.amazon-checkout-btn:active:not(:disabled) {
    background: #f0b800;
    border-color: #008296;
    box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.2);
    transform: scale(0.99);
}

.amazon-checkout-btn:disabled {
    background: #f3f3f3;
    border-color: #d5d9d9;
    color: #878787;
    cursor: not-allowed;
    box-shadow: none;
}

.amazon-checkout-btn .spinner {
    width: 20px;
    height: 20px;
    border: 3px solid rgba(15, 17, 17, 0.1);
    border-radius: 50%;
    border-top-color: #0f1111;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.checkout-summary__card {
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
