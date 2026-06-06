<template>
    <AuthLayout :title="currentTitle" :errorMessage="errorMessage" :retryAfter="retryAfter" :actionText="actionText">
        <form v-if="step === 'email'" @submit.prevent="handleCheckEmail" class="login-form" novalidate>
            <div class="a-input-text-group">
                <label for="email" class="a-form-label">{{ t('login.emailLabel') }}</label>
                <input id="email" type="email" v-model="form.email" required class="a-input-text" />
            </div>

            <button type="submit" class="a-button-primary" :disabled="isLoading">
                {{ isLoading ? t('login.checking') : t('login.continue') }}
            </button>

            <button type="button" @click="loginWithSocial('google')" class="a-button-secondary w-100 social-btn" :disabled="isLoading">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google Logo" class="social-icon" />
                {{ t('login.continueWithGoogle') }}
            </button>
        </form>

        <form v-else-if="step === 'password'" @submit.prevent="handleLogin" class="login-form" novalidate>
            <div class="email-display-box">
                <span class="email-text">{{ form.email }}</span>
                <a href="#" @click.prevent="step = 'email'" class="a-link-normal change-link">{{ t('login.change') }}</a>
            </div>

            <div class="a-input-text-group">
                <div class="password-label-group">
                    <label for="password" class="a-form-label">{{ t('login.passwordLabel') }}</label>
                    <a href="#" class="a-link-normal forgot-password">{{ t('login.forgotPassword') }}</a>
                </div>
                <input id="password" type="password" v-model="form.password" required class="a-input-text" autofocus />
            </div>

            <button type="submit" class="a-button-primary" :disabled="isLoading">
                {{ isLoading ? t('login.signingIn') : t('login.signIn') }}
            </button>
        </form>

        <div v-else-if="step === 'new_user'" class="login-form">
            <div class="email-display-box">
                <span class="email-text">{{ form.email }}</span>
                <a href="#" @click.prevent="step = 'email'" class="a-link-normal change-link">{{ t('login.change') }}</a>
            </div>

            <p class="new-user-text">{{ t('login.createWithEmail') }}</p>

            <button @click="goToRegister" class="a-button-primary">{{ t('login.continueCreateAccount') }}</button>
        </div>

        <template #footer-action>
            <div v-if="step === 'email'">
                </div>

            <div v-if="step === 'new_user'">
                </div>

            <div class="hr-demo-card">
                <div class="hr-card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                    <span>Welcome HR!</span>
                </div>

                <p class="hr-card-desc">
                    Đăng nhập nhanh bằng tài khoản Demo cho HR.
                </p>

                <button type="button" class="hr-login-demo-btn" :disabled="isLoading" @click="handleDemoLogin">
                    <svg v-if="!isLoading" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>

                    {{ isLoading ? t('login.signingIn') : 'Tự động đăng nhập' }}
                </button>
            </div>
            </template>
    </AuthLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { APP_CONFIG } from '@/config';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { t } = useI18n();

const step = ref('email');
const DEMO_LOGIN_CREDENTIALS = {
    email: 'hr.demo@gmail.com',
    password: 'hrdemo',
};

const form = reactive({
    email: '',
    password: '',
});

const isLoading = ref(false);
const errorMessage = ref('');
const retryAfter = ref(0);

const currentTitle = computed(() => {
    if (step.value === 'email') return t('login.title');
    if (step.value === 'password') return t('login.title');
    return t('login.newUserTitle', { appName: APP_CONFIG.appName });
});

const actionText = computed(() => {
    return step.value === 'password' ? t('login.signInAction') : t('login.continueAction');
});

const getSafeRedirectPath = () => {
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '';

    if (!redirect || !redirect.startsWith('/') || redirect.startsWith('//')) {
        return null;
    }

    return redirect;
};

const redirectAfterLogin = () => {
    const redirectPath = getSafeRedirectPath();
    router.push(redirectPath || { name: 'Home' });
};

const handleCheckEmail = async () => {
    if (!form.email) return;

    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.post('/check-email', { email: form.email });

        if (response.data.data.exists) {
            step.value = 'password';
        } else {
            step.value = 'new_user';
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || t('login.errors_connection');
    } finally {
        isLoading.value = false;
    }
};

const handleLogin = async () => {
    isLoading.value = true;
    errorMessage.value = '';
    retryAfter.value = 0;

    try {
        const response = await authStore.login({
            email: form.email,
            password: form.password,
        });

        if (response.success) {
            redirectAfterLogin();
        }
    } catch (error) {
        if (error.response && error.response.data) {
            errorMessage.value = error.response.data.message || t('login.errors_generic');
            // Handle rate limit
            if (error.response.status === 429 && error.response.data.retry_after) {
                retryAfter.value = error.response.data.retry_after;
            }
        } else {
            errorMessage.value = t('login.errors_connection');
        }
    } finally {
        isLoading.value = false;
    }
};

const handleDemoLogin = async () => {
    if (isLoading.value) return;

    form.email = DEMO_LOGIN_CREDENTIALS.email;
    form.password = DEMO_LOGIN_CREDENTIALS.password;
    step.value = 'password';

    await handleLogin();
};

const goToRegister = () => {
    router.push({ name: 'Register', query: { email: form.email } });
};

const loginWithSocial = (provider) => {
    errorMessage.value = '';

    const width = 500,
        height = 600;
    const left = window.innerWidth / 2 - width / 2;
    const top = window.innerHeight / 2 - height / 2;

    const url = `${APP_CONFIG.apiUrl}/auth/${provider}/redirect`;

    window.open(url, 'SocialLogin', `width=${width},height=${height},top=${top},left=${left}`);

    const handleMessage = (event) => {
        const { token, user, error } = event.data;

        if (token) {
            authStore.token = token;
            authStore.user = user;

            window.removeEventListener('message', handleMessage);
            redirectAfterLogin();
        } else if (error) {
            errorMessage.value = error;
            window.removeEventListener('message', handleMessage);
        }
    };

    window.addEventListener('message', handleMessage);
};
</script>

<style scoped>
.email-display-box {
    display: flex;
    align-items: center;
    margin-bottom: 14px;
    font-size: 13px;
}
.email-display-box .email-text {
    font-weight: bold;
    margin-right: 8px;
}
.email-display-box .change-link {
    font-size: 12px;
}

.new-user-text {
    font-size: 13px;
    margin-bottom: 18px;
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background-color: #fff;
    border: 1px solid #d5d9d9;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.15);
}
.social-btn:hover {
    background-color: #f7fafa;
}
.social-icon {
    width: 18px;
    height: 18px;
}
.w-100 {
    width: 100%;
}

/* =========================================================
   CARD HR DEMO LOGIN
   ========================================================= */
.hr-demo-card {
    margin-top: 24px;
    padding: 16px;
    background-color: #f4f8fb; /* Màu nền xanh lam nhạt tạo sự chú ý mà không bị gắt */
    border: 1px solid #c8dbe8;
    border-radius: 8px;
}

.hr-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #005fb8; /* Accent color */
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 8px;
}

.hr-card-desc {
    font-size: 12px;
    color: #4b5563;
    margin-bottom: 14px;
    line-height: 1.5;
}

.hr-login-demo-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    width: 100%;
    background: #005fb8;
    color: #ffffff;
    border: 1px solid transparent;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.hr-login-demo-btn:hover:not(:disabled) {
    background: #004a94;
}

.hr-login-demo-btn:active:not(:disabled) {
    background: #003873;
    transform: translateY(1px);
}

.hr-login-demo-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
</style>
