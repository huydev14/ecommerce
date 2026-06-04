import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/',
        component: () => import('@/layouts/DefaultLayout.vue'),
        children: [
            {
                path: '',
                name: 'Home',
                component: () => import('@/pages/Home.vue'),
            },
            {
                path: 'products',
                name: 'ProductList',
                component: () => import('@/pages/ProductList.vue'),
            },
            {
                path: 'products/:slug',
                name: 'ProductDetail',
                component: () => import('@/pages/ProductDetail.vue'),
            },
            {
                path: 'cart',
                name: 'Cart',
                component: () => import('@/pages/Cart.vue'),
            },
            {
                path: 'checkout',
                name: 'Checkout',
                component: () => import('@/pages/Checkout.vue'),
                meta: { requiresAuth: true },
            },
            {
                path: 'checkout/vnpay-return',
                name: 'VnpayReturn',
                component: () => import('@/pages/VnpayReturn.vue'),
            },
            {
                path: 'order-success',
                name: 'OrderSuccess',
                component: () => import('@/pages/OrderSuccess.vue'),
            },
            {
                path: 'my-orders',
                name: 'MyOrders',
                component: () => import('@/pages/MyOrders.vue'),
                meta: { requiresAuth: true },
            },
            {
                path: 'customer-addresses',
                name: 'CustomerAddresses',
                component: () => import('@/pages/CustomerAddresses.vue'),
                meta: { requiresAuth: true },
            },
        ],
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('@/pages/Login.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('@/pages/Register.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/verify-otp',
        name: 'VerifyOTP',
        component: () => import('@/pages/VerifyOTP.vue'),
    },
    // {
    //     path: '/profile',
    //     name: 'Profile',
    //     component: () => import('@/pages/Profile.vue'),
    //     meta: { requiresAuth: true },
    // },
];

const router = createRouter({
    history: createWebHistory('/'),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        if (to.name === 'ProductDetail') {
            return { left: 0, top: 0 };
        }
        return false;
    },
});

router.beforeEach(async (to, from) => {
    const authStore = useAuthStore();
    let isAuthenticated = authStore.isLoggedIn;

    if (!isAuthenticated && to.meta.requiresAuth) {
        isAuthenticated = await authStore.bootstrapAuth();
    }

    if (to.meta.requiresAuth && !isAuthenticated) {
        return { name: 'Login', query: { redirect: to.fullPath } };
    } else if (to.meta.guestOnly && isAuthenticated) {
        return { name: 'Home' };
    } else {
        return true;
    }
});

export default router;
