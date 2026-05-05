<template>
    <div class="amazon-login-container">
        <div class="logo-container">
            <router-link :to="{ name: 'Home' }" class="logo-link">
                <span class="logo-text">{{ APP_CONFIG.appName }}</span>
            </router-link>
        </div>

        <div class="login-box">
            <h1 class="login-title">{{ title }}</h1>

            <div v-if="errorMessage" class="a-alert-content">
                <i class="alert-icon">!</i>
                <span class="alert-text">{{ errorMessage }}</span>
            </div>

            <slot></slot>

            <div class="a-divider-inner">
                <p class="terms-text">
                    Bằng cách {{ actionText }}, bạn đồng ý với <a href="#" class="a-link-normal">Điều kiện sử dụng</a> và
                    <a href="#" class="a-link-normal">Thông báo bảo mật</a>.
                </p>
            </div>

            <slot name="footer-action"></slot>
        </div>

        <AuthFooter />
    </div>
</template>

<script setup>
import AuthFooter from '@/components/AuthFooter.vue';
import '@scss/client/auth.scss';
import { APP_CONFIG } from '@/config';

defineProps({
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
        default: 'tiếp tục',
    },
});
</script>
