<template>
    <div class="login-container">
        <div class="logo-container">
            <router-link :to="{ name: 'Home' }" class="logo-link">
                <span class="logo-text">{{ APP_CONFIG.appName }}</span>
            </router-link>
        </div>

        <div class="login-box">
            <h1 class="login-title">{{ title }}</h1>

            <div v-if="errorMessage" class="a-alert-content">
                <i class="alert-icon">!</i>
                <span class="alert-text">
                    {{ errorMessage }}
                    <span v-if="isCountingDown" class="countdown-timer">{{ formattedTime }}</span>
                </span>
            </div>

            <slot></slot>

            <div class="a-divider-inner">
                <p class="terms-text">
                    {{ t('authLayout.terms_prefix', { action: displayActionText }) }}
                    <a href="#" class="a-link-normal">{{ t('authLayout.terms_conditions') }}</a>
                    {{ t('authLayout.terms_middle') }}
                    <a href="#" class="a-link-normal">{{ t('authLayout.terms_privacy') }}</a>.
                </p>
            </div>

            <slot name="footer-action"></slot>
        </div>
        <AuthFooter />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, provide } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthFooter from '@/components/AuthFooter.vue';
import '@scss/client/auth.scss';
import { APP_CONFIG } from '@/config';

const { t } = useI18n();

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    errorMessage: {
        type: String,
        default: '',
    },
    actionText: {
        type: String,
        default: '',
    },
    retryAfter: {
        type: Number,
        default: 0,
    },
});

// Countdown timer logic
const remainingSeconds = ref(0);
let intervalId = null;

const isCountingDown = computed(() => remainingSeconds.value > 0);
const displayActionText = computed(() => props.actionText || t('authLayout.defaultAction'));

// Rate limit status
const isRateLimited = computed(() => props.retryAfter > 0);
provide('isRateLimited', isRateLimited);

const formattedTime = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60);
    const secs = remainingSeconds.value % 60;

    if (minutes > 0) {
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
    return `${secs}s`;
});

const startCountdown = () => {
    if (intervalId) clearInterval(intervalId);

    intervalId = setInterval(() => {
        remainingSeconds.value--;
        if (remainingSeconds.value <= 0) {
            remainingSeconds.value = 0;
            clearInterval(intervalId);
        }
    }, 1000);
};

const initCountdown = (seconds) => {
    remainingSeconds.value = seconds;
    if (seconds > 0) {
        startCountdown();
    } else {
        if (intervalId) clearInterval(intervalId);
    }
};

watch(
    () => props.retryAfter,
    (newValue) => {
        initCountdown(newValue);
    },
    { immediate: true },
);

onMounted(() => {
    initCountdown(props.retryAfter);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>
