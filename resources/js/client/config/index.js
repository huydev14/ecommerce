export const APP_CONFIG = {
    appName: import.meta.env.VITE_APP_NAME || 'ecommerce',
    baseUrl: import.meta.env.VITE_APP_URL,
    apiUrl: import.meta.env.VITE_API_BASE_URL || '/api/v1',
};
