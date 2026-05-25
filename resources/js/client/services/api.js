import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { APP_CONFIG } from '@/config';

const api = axios.create({
    baseURL: APP_CONFIG.apiUrl,
    withCredentials: true,
    headers: {
        Accept: 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const store = useAuthStore();
    if (store.token) {
        config.headers.Authorization = `Bearer ${store.token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        if (error.response?.status === 401 && !originalRequest._retry && originalRequest.url !== '/refresh') {
            originalRequest._retry = true;

            const store = useAuthStore();

            try {
                const success = await store.silentRefresh();
                if (success) {
                    originalRequest.headers.Authorization = `Bearer ${store.token}`;
                    return api(originalRequest);
                }
            } catch (refreshError) {
                throw refreshError;
            }
        }
        throw error;
    },
);
export default api;
