<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const { t, locale } = useI18n();
const isMenuOpen = ref(false);
const categories = ref([]);
const trendingKeywords = ref([]);

const userName = computed(() => authStore.user?.name || authStore.user?.fullname || t('subNav.accountFallback'));
const userAvatar = computed(() => authStore.user?.avatar || authStore.user?.photo_url || authStore.user?.image || '');
const userInitial = computed(() => userName.value.trim().charAt(0).toUpperCase() || 'U');
const languageLabel = computed(() => (locale.value === 'vi' ? t('subNav.language_vi') : t('subNav.language_en')));
const hasTrendingKeywords = computed(() => trendingKeywords.value.length > 0);

const getCategoryItems = (payload) => {
    if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
};

const hasChildren = (category) => Array.isArray(category.children) && category.children.length > 0;
const categoryUrl = (category) => (category.slug ? `/products?category=${category.slug}` : '#');
const keywordRoute = (keyword) => ({
    name: 'ProductList',
    query: {
        keyword,
    },
});

const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/v1/categories/tree', {
            params: {
                limit: 15,
            },
        });
        if (response.data && response.data.success) {
            categories.value = getCategoryItems(response.data);
        }
    } catch (error) {
        console.error(t('subNav.errors_fetchCategories'), error);
    }
};

const fetchTrendingKeywords = async () => {
    try {
        const response = await axios.get('/api/v1/trending-keywords');
        const keywords = response.data?.data;

        trendingKeywords.value = Array.isArray(keywords) ? keywords.filter(Boolean) : [];
    } catch (error) {
        trendingKeywords.value = [];
    }
};

onMounted(() => {
    fetchCategories();
    fetchTrendingKeywords();
});

const openMenu = () => {
    isMenuOpen.value = true;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};

watch(isMenuOpen, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="tw-relative tw-border-b tw-border-[#3a4553] tw-bg-[#232f3e] tw-text-white">
        <div
            class="tw-flex tw-h-[39px] tw-items-center tw-gap-1 tw-overflow-x-auto tw-px-3 tw-text-[14px] tw-font-medium md:tw-gap-2 md:tw-px-4"
        >
            <button
                type="button"
                @click="openMenu"
                class="tw-flex tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-mr-2 tw-h-6 tw-w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                {{ t('subNav.links_all') }}
            </button>

            <span class="tw-text-gray-100" v-if="hasTrendingKeywords"> | </span>

            <span v-if="hasTrendingKeywords" class="tw-flex tw-flex-none tw-items-center tw-px-1 tw-py-1 tw-text-gray-100">
                {{ t('subNav.trending_label') }}
            </span>

            <template v-if="hasTrendingKeywords">
                <router-link
                    v-for="keyword in trendingKeywords"
                    :key="keyword"
                    :to="keywordRoute(keyword)"
                    class="tw-flex tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 tw-text-gray-100 tw-no-underline hover:tw-border-white hover:tw-text-[#febd69]"
                >
                    {{ keyword }}
                </router-link>
            </template>
        </div>

        <transition name="nav-overlay">
            <div v-if="isMenuOpen" class="tw-fixed tw-inset-0 tw-z-[70] tw-bg-black/55" @click="closeMenu"></div>
        </transition>

        <transition name="nav-drawer">
            <aside
                v-if="isMenuOpen"
                class="tw-fixed tw-left-0 tw-top-0 tw-z-[80] tw-h-full tw-w-[330px] tw-max-w-[88vw] tw-overflow-y-auto tw-bg-[#f3f3f3] tw-text-[#111827] tw-shadow-2xl"
            >
                <div class="tw-flex tw-items-center tw-justify-between tw-bg-[#232f3e] tw-px-5 tw-py-4 tw-text-white">
                    <div v-if="!authStore.isLoggedIn" class="tw-flex tw-items-center tw-gap-3">
                        <div class="tw-grid tw-h-8 tw-w-8 tw-place-items-center tw-rounded-full tw-bg-white/15">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tw-h-5 tw-w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <div class="tw-text-base tw-font-bold">{{ t('subNav.signInGreeting') }}</div>
                        </div>
                    </div>

                    <div v-else class="tw-flex tw-min-w-0 tw-items-center tw-gap-3">
                        <img
                            v-if="userAvatar"
                            :src="userAvatar"
                            :alt="userName"
                            class="tw-h-9 tw-w-9 tw-flex-none tw-rounded-full tw-bg-white/15 tw-object-cover"
                        />
                        <div
                            v-else
                            class="tw-grid tw-h-9 tw-w-9 tw-flex-none tw-place-items-center tw-rounded-full tw-bg-white/15 tw-text-sm tw-font-bold"
                        >
                            {{ userInitial }}
                        </div>
                        <div class="tw-min-w-0">
                            <div class="tw-truncate tw-text-base tw-font-bold">{{ userName }}</div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="tw-grid tw-h-8 tw-w-8 tw-place-items-center tw-rounded-full hover:tw-bg-white/10"
                        @click="closeMenu"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="tw-h-5 tw-w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="tw-divide-y tw-divide-gray-200 tw-bg-white">
                    <section v-for="category in categories" :key="category.id" class="tw-px-0 tw-py-2">
                        <h3 class="tw-truncate tw-px-5 tw-py-3 tw-text-[17px] tw-font-bold tw-capitalize tw-text-[#111827]">
                            {{ category.name }}
                        </h3>

                        <div class="tw-space-y-1 tw-pb-2">
                            <div v-for="child in category.children" :key="child.id" class="tw-text-[#111827]">
                                <a
                                    :href="categoryUrl(child)"
                                    class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-2 tw-text-[14px] hover:tw-bg-gray-50"
                                >
                                    <span class="tw-truncate tw-capitalize">{{ child.name }}</span>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="tw-h-4 tw-w-4 tw-text-gray-400"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </a>

                                <div v-if="hasChildren(child)" class="tw-pb-1">
                                    <a
                                        v-for="grandchild in child.children"
                                        :key="grandchild.id"
                                        :href="categoryUrl(grandchild)"
                                        class="tw-block tw-truncate tw-py-1.5 tw-pl-8 tw-pr-5 tw-text-[13px] tw-capitalize tw-text-gray-600 hover:tw-bg-gray-100 hover:tw-text-[#111827]"
                                    >
                                        {{ grandchild.name }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="tw-px-0 tw-py-4">
                        <h3 class="tw-px-5 tw-pb-4 tw-pt-1 tw-text-[17px] tw-font-bold tw-text-[#111827]">
                            {{ t('subNav.helpSettings') }}
                        </h3>

                        <div class="tw-space-y-1 tw-pb-2">
                            <router-link
                                :to="{ name: 'MyOrders' }"
                                class="tw-block tw-px-5 tw-py-2.5 tw-text-[14px] tw-font-medium tw-text-[#111827] hover:tw-bg-gray-50"
                                @click="closeMenu"
                            >
                                {{ t('subNav.account_orders') }}
                            </router-link>

                            <router-link
                                :to="{ name: 'CustomerAddresses' }"
                                class="tw-block tw-px-5 tw-py-2.5 tw-text-[14px] tw-font-medium tw-text-[#111827] hover:tw-bg-gray-50"
                                @click="closeMenu"
                            >
                                {{ t('subNav.account_addresses') }}
                            </router-link>

                            <a
                                href="#"
                                class="tw-flex tw-items-center tw-gap-4 tw-px-5 tw-py-2.5 tw-text-[14px] tw-font-medium tw-text-[#111827] hover:tw-bg-gray-50"
                            >
                                <span class="tw-grid tw-h-5 tw-w-5 tw-place-items-center tw-text-[16px]" aria-hidden="true">🌐</span>
                                <span>{{ languageLabel }}</span>
                            </a>

                            <a
                                href="#"
                                class="tw-flex tw-items-center tw-gap-4 tw-px-5 tw-py-2.5 tw-text-[14px] tw-font-medium tw-text-[#111827] hover:tw-bg-gray-50"
                            >
                                <span class="tw-grid tw-h-5 tw-w-5 tw-place-items-center tw-text-[16px]" aria-hidden="true">🇺🇸</span>
                                <span>{{ t('subNav.country') }}</span>
                            </a>

                            <a
                                href="#"
                                class="tw-block tw-px-5 tw-py-2.5 tw-text-[14px] tw-font-medium tw-text-[#111827] hover:tw-bg-gray-50"
                            >
                                {{ t('subNav.links_customerService') }}
                            </a>

                            <router-link
                                v-if="!authStore.isLoggedIn"
                                :to="{ name: 'Login' }"
                                class="tw-block tw-px-5 tw-py-2.5 tw-text-[14px] tw-font-medium tw-text-[#111827] hover:tw-bg-gray-50"
                                @click="closeMenu"
                            >
                                {{ t('subNav.signIn') }}
                            </router-link>
                        </div>
                    </section>
                </div>
            </aside>
        </transition>
    </div>
</template>

<style scoped>
.nav-overlay-enter-active,
.nav-overlay-leave-active,
.nav-drawer-enter-active,
.nav-drawer-leave-active {
    transition: all 0.22s ease;
}

.nav-overlay-enter-from,
.nav-overlay-leave-to {
    opacity: 0;
}

.nav-drawer-enter-from,
.nav-drawer-leave-to {
    transform: translateX(-100%);
}
</style>
