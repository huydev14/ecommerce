import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import '../bootstrap';
import i18n from './i18n';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);

async function initApp() {
    const authStore = useAuthStore();
    authStore.setupWatcher();

    try {
        await authStore.bootstrapAuth();
    } catch (error) {
        console.error('Auth bootstrap failed:', error);
    }

    app.use(router);
    app.use(i18n);

    app.mount('#client-app');
}
initApp();
