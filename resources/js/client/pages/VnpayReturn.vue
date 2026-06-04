<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const responseCode = computed(() => route.query.vnp_ResponseCode || '');
const transactionStatus = computed(() => route.query.vnp_TransactionStatus || '');
const transactionNumber = computed(() => route.query.vnp_TransactionNo || '');
const txnRef = computed(() => route.query.vnp_TxnRef || '');
const isSuccess = computed(() => responseCode.value === '00' && (!transactionStatus.value || transactionStatus.value === '00'));

const orderId = computed(() => {
    if (!txnRef.value) {
        return 'N/A';
    }

    return String(txnRef.value).split('_')[0] || 'N/A';
});
</script>

<template>
    <section class="vnpay-return">
        <div class="vnpay-return__card">
            <div v-if="isSuccess" class="vnpay-return__state">
                <div class="vnpay-return__icon vnpay-return__icon--success" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h1>Thanh toán thành công!</h1>
                <p>Cảm ơn bạn đã mua sắm. Đơn hàng <strong>{{ orderId }}</strong> đang được xử lý.</p>
                <p v-if="transactionNumber" class="vnpay-return__meta">Mã giao dịch: {{ transactionNumber }}</p>
            </div>

            <div v-else class="vnpay-return__state">
                <div class="vnpay-return__icon vnpay-return__icon--failed" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1>Giao dịch thất bại</h1>
                <p>Mã lỗi: {{ responseCode || 'N/A' }}</p>
                <p>Vui lòng thử lại hoặc chọn phương thức thanh toán khác.</p>
            </div>

            <div class="vnpay-return__actions">
                <RouterLink :to="{ name: 'Home' }">Về trang chủ</RouterLink>
                <RouterLink :to="{ name: 'MyOrders' }">Xem đơn hàng</RouterLink>
            </div>
        </div>
    </section>
</template>

<style scoped>
.vnpay-return {
    display: flex;
    min-height: calc(100vh - 120px);
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    padding: 32px 16px;
}

.vnpay-return__card {
    width: min(100%, 440px);
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    padding: 32px;
    text-align: center;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
}

.vnpay-return__state {
    display: grid;
    justify-items: center;
    gap: 12px;
}

.vnpay-return__icon {
    display: grid;
    width: 64px;
    height: 64px;
    place-items: center;
    border-radius: 50%;
}

.vnpay-return__icon svg {
    width: 32px;
    height: 32px;
}

.vnpay-return__icon--success {
    background: #dcfce7;
    color: #16a34a;
}

.vnpay-return__icon--failed {
    background: #fee2e2;
    color: #dc2626;
}

.vnpay-return h1 {
    margin: 4px 0 0;
    color: #111827;
    font-size: 24px;
    line-height: 1.25;
}

.vnpay-return p {
    margin: 0;
    color: #4b5563;
    font-size: 15px;
    line-height: 1.6;
}

.vnpay-return__meta {
    color: #6b7280;
    font-size: 14px;
}

.vnpay-return__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 16px;
    margin-top: 24px;
}

.vnpay-return__actions a {
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
}

.vnpay-return__actions a:hover {
    text-decoration: underline;
}
</style>
