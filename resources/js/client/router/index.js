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
});

router.beforeEach(async (to, from) => {
    const authStore = useAuthStore();
    let isAuthenticated = authStore.isLoggedIn;

    if (!isAuthenticated && to.meta.requiresAuth) {
        isAuthenticated = await authStore.bootstrapAuth();
    }

    if (to.meta.requiresAuth && !isAuthenticated) {
        return { name: 'Login' };
    } else if (to.meta.guestOnly && isAuthenticated) {
        return { name: 'Home' };
    } else {
        return true;
    }
});

export default router;
