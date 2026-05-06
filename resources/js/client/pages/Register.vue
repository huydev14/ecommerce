<template>
    <AuthLayout title="Tạo tài khoản" :errorMessage="errorMessage" actionText="tạo tài khoản">
        <form @submit.prevent="handleRegister" class="login-form" novalidate>
            <div class="a-input-text-group">
                <label for="fullname" class="a-form-label">Họ và tên của bạn</label>
                <input id="fullname" type="text" v-model="form.fullname" required placeholder="Họ và tên" class="a-input-text" />
            </div>

            <div class="a-input-text-group">
                <label for="email" class="a-form-label">Email</label>
                <input id="email" type="email" v-model="form.email" required class="a-input-text" />
            </div>

            <div class="a-input-text-group">
                <label for="password" class="a-form-label">Mật khẩu</label>
                <input id="password" type="password" v-model="form.password" required placeholder="Ít nhất 6 ký tự" class="a-input-text" />
                <div class="a-alert-inline" v-if="form.password.length > 0 && form.password.length < 6">
                    Mật khẩu phải có ít nhất 6 ký tự.
                </div>
            </div>

            <div class="a-input-text-group">
                <label for="password_confirmation" class="a-form-label">Nhập lại mật khẩu</label>
                <input id="password_confirmation" type="password" v-model="form.password_confirmation" required class="a-input-text" />
                <div class="a-alert-inline error-text" v-if="passwordMismatch">Mật khẩu không khớp.</div>
            </div>

            <button type="submit" class="a-button-primary" :disabled="isLoading || passwordMismatch || form.password.length < 6">
                {{ isLoading ? 'Đang tạo tài khoản...' : 'Tiếp tục' }}
            </button>
        </form>

        <template #footer-action>
            <div class="a-divider a-divider-break"></div>
            <p class="already-have-account">
                Bạn đã có tài khoản?
                <router-link to="/login" class="a-link-normal">Đăng nhập <span class="arrow">›</span></router-link>
            </p>
        </template>
    </AuthLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';
import AuthLayout from '@/layouts/AuthLayout.vue';
const router = useRouter();
const route = useRoute();

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
            errorMessage.value = 'Không thể kết nối đến máy chủ.';
        }
    } finally {
        isLoading.value = false;
    }
};
</script>
