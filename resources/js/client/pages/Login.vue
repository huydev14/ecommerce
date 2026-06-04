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

            <div class="a-divider a-divider-break tw-mb-5 tw-mt-5">
                <h5>{{ t('login.or') }}</h5>
            </div>

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
                <div class="a-divider a-divider-break">
                    <h5>{{ t('login.newToApp', { appName: APP_CONFIG.appName }) }}</h5>
                </div>
                <router-link :to="{ name: 'Register' }" custom v-slot="{ navigate }">
                    <button @click="navigate" class="a-button-secondary w-100">
                        {{ t('login.createYourAccount', { appName: APP_CONFIG.appName }) }}
                    </button>
                </router-link>
            </div>

            <div v-if="step === 'new_user'">
                <div class="a-divider a-divider-break"></div>
                <div class="already-have-account" style="margin-top: 14px">
                    <span style="font-weight: bold; display: block; margin-bottom: 4px">{{ t('login.alreadyCustomer') }}</span>
                    <a href="#" @click.prevent="step = 'email'" class="a-link-normal">{{ t('login.signInWithEmailOrPhone') }}</a>
                </div>
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
</style>
