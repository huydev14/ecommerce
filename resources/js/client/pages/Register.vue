<template>
    <AuthLayout :title="t('register.title')" :errorMessage="errorMessage" :actionText="t('register.actionText')">
        <form @submit.prevent="handleRegister" class="login-form" novalidate>
            <div class="a-input-text-group">
                <label for="fullname" class="a-form-label">{{ t('register.fullNameLabel') }}</label>
                <input id="fullname" type="text" v-model="form.fullname" required :placeholder="t('register.fullNamePlaceholder')" class="a-input-text" />
            </div>

            <div class="a-input-text-group">
                <label for="email" class="a-form-label">{{ t('register.emailLabel') }}</label>
                <input id="email" type="email" v-model="form.email" required class="a-input-text" />
            </div>

            <div class="a-input-text-group">
                <label for="password" class="a-form-label">{{ t('register.passwordLabel') }}</label>
                <input id="password" type="password" v-model="form.password" required :placeholder="t('register.passwordPlaceholder')" class="a-input-text" />
                <div class="a-alert-inline" v-if="form.password.length > 0 && form.password.length < 6">
                    {{ t('register.passwordMinLength') }}
                </div>
            </div>

            <div class="a-input-text-group">
                <label for="password_confirmation" class="a-form-label">{{ t('register.confirmPasswordLabel') }}</label>
                <input id="password_confirmation" type="password" v-model="form.password_confirmation" required class="a-input-text" />
                <div class="a-alert-inline error-text" v-if="passwordMismatch">{{ t('register.passwordMismatch') }}</div>
            </div>

            <button type="submit" class="a-button-primary" :disabled="isLoading || passwordMismatch || form.password.length < 6">
                {{ isLoading ? t('register.creating') : t('register.continue') }}
            </button>
        </form>

        <template #footer-action>
            <div class="a-divider a-divider-break"></div>
            <p class="already-have-account">
                {{ t('register.alreadyHaveAccount') }}
                <router-link to="/login" class="a-link-normal">{{ t('register.signIn') }} <span class="arrow">›</span></router-link>
            </p>
        </template>
    </AuthLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';
import AuthLayout from '@/layouts/AuthLayout.vue';
const router = useRouter();
const route = useRoute();
const { t } = useI18n();

onMounted(() => {
    if (route.query.email) {
        form.email = route.query.email;
    }
});

const authStore = useAuthStore();

const form = reactive({
    fullname: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const isLoading = ref(false);
const errorMessage = ref('');

const passwordMismatch = computed(() => {
    return form.password_confirmation.length > 0 && form.password !== form.password_confirmation;
});

const handleRegister = async () => {
    if (passwordMismatch.value || form.password.length < 6) return;

    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.post('/register', form);

        if (response.data.success) {
            router.push({
                name: 'VerifyOTP',
                query: { email: form.email },
            });
        }
    } catch (error) {
        if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors;
            errorMessage.value = Object.values(errors)[0][0];
        } else if (error.response && error.response.data) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = t('register.errors_connection');
        }
    } finally {
        isLoading.value = false;
    }
};
</script>
