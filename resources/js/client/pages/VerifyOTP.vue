<template>
    <AuthLayout :title="t('verifyOtp.title')" :errorMessage="errorMessage" :retryAfter="retryAfter" :actionText="t('verifyOtp.actionText')">
        <form @submit.prevent="handleVerify" class="login-form verify-otp-page">
            <div class="verify-instruction">
                <p>{{ t('verifyOtp.instruction') }}</p>
                <div class="email-display">
                    <strong>{{ email }}</strong>
                    <router-link :to="{ name: 'Register' }" class="a-link-normal change-email-link">{{ t('verifyOtp.change') }}</router-link>
                </div>
            </div>

            <div class="a-input-text-group">
                <div class="password-label-group">
                    <label for="otp" class="a-form-label">{{ t('verifyOtp.otpLabel') }}</label>
                </div>
                <input
                    id="otp"
                    type="text"
                    v-model="otpCode"
                    required
                    maxlength="6"
                    autocomplete="one-time-code"
                    class="a-input-text otp-input"
                />
            </div>

            <button type="submit" class="a-button-primary" :disabled="isLoading || otpCode.length < 6">
                {{ isLoading ? t('verifyOtp.verifying') : t('verifyOtp.createAccount', { appName: APP_CONFIG.appName }) }}
            </button>
        </form>

        <template #footer-action>
            <div class="resend-section verify-otp-page">
                <div class="a-divider a-divider-break"></div>
                <div class="resend-content">
                    <p class="resend-text">{{ t('verifyOtp.notReceived') }}</p>
                    <a href="#" @click.prevent="handleResend" class="a-link-normal" :class="{ disabled: isResending }">
                        {{ isResending ? t('verifyOtp.resending') : t('verifyOtp.resend') }}
                    </a>
                </div>
                <div v-if="resendSuccessMessage" class="a-alert-inline success-text"><i>✓</i> {{ resendSuccessMessage }}</div>
            </div>
        </template>
    </AuthLayout>
</template>

<script setup>
import '@scss/client/auth.scss';

import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter, useRoute } from 'vue-router';
import api from '../services/api';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { APP_CONFIG } from '@/config';

const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const email = ref('');
const otpCode = ref('');
const isLoading = ref(false);
const isResending = ref(false);
const errorMessage = ref('');
const resendSuccessMessage = ref('');
const retryAfter = ref(0);

onMounted(() => {
    if (route.query.email) {
        email.value = route.query.email;
    } else {
        router.push({ name: 'Register' });
    }
});

const handleVerify = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    resendSuccessMessage.value = '';

    try {
        const response = await api.post('/verify-otp', {
            email: email.value,
            otp: otpCode.value,
        });

        if (response.data.success) {
            router.push({ name: 'Login', query: { verified: 'true' } });
        }
    } catch (error) {
        if (error.response && error.response.data) {
            errorMessage.value = error.response.data.message || t('verifyOtp.errors_invalid');
        } else {
            errorMessage.value = t('verifyOtp.errors_network');
        }
    } finally {
        isLoading.value = false;
    }
};

const handleResend = async () => {
    if (isResending.value) return;

    isResending.value = true;
    errorMessage.value = '';
    resendSuccessMessage.value = '';
    retryAfter.value = 0;

    try {
        const response = await api.post('/resend-otp', { email: email.value });
        if (response.data.success) {
            resendSuccessMessage.value = t('verifyOtp.resendSuccess');
            otpCode.value = '';
        }
    } catch (error) {
        if (error.response && error.response.data) {
            errorMessage.value = error.response.data.message || t('verifyOtp.errors_resendFailed');
            // Handle rate limit - extract retry_after
            if (error.response.status === 429 && error.response.data.retry_after) {
                retryAfter.value = error.response.data.retry_after;
            }
        } else {
            errorMessage.value = t('verifyOtp.errors_networkRetry');
        }
        console.error(t('verifyOtp.errors_resendLog'), error);
    } finally {
        isResending.value = false;
    }
};
</script>
