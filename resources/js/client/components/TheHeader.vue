<template>
    <div class="tw-bg-[#131921] tw-text-white">
        <div class="tw-flex tw-min-w-0 tw-items-center tw-justify-center tw-gap-1 tw-px-2 tw-py-2 md:tw-gap-2 md:tw-px-4 xl:tw-gap-4">
            <router-link
                :to="{ name: 'Home' }"
                class="tw-grid tw-min-h-[42px] tw-flex-none tw-cursor-pointer tw-place-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 tw-text-white tw-no-underline hover:tw-border-white"
                :aria-label="APP_CONFIG.appName"
            >
                <span class="tw-grid tw-w-fit tw-leading-none">
                    <span class="tw-text-[22px] tw-font-bold tw-leading-6 tw-tracking-normal md:tw-text-[26px] md:tw-leading-7">
                        {{ APP_CONFIG.appName }}
                    </span>
                    <span
                        class="tw-ml-auto tw-mr-1 tw-h-1.5 tw-w-14 tw-rounded-[50%] tw-border-b-[3px] tw-border-[#ff9900] md:tw-w-16"
                    ></span>
                </span>
            </router-link>

            <div
                role="button"
                tabindex="0"
                class="open-location-modal tw-hidden tw-flex-none tw-cursor-pointer tw-flex-col tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white lg:tw-flex"
                @click="isLocationModalOpen = true"
                @keydown.enter.prevent="isLocationModalOpen = true"
                @keydown.space.prevent="isLocationModalOpen = true"
            >
                <span class="tw-pl-4 tw-text-[12px] tw-leading-3 tw-text-[#cccccc]">
                    {{ t('header.location_deliverTo') }}
                </span>
                <div class="tw-flex tw-items-center">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="tw-h-4 tw-w-4 tw-text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                        />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="tw-text-[14px] tw-font-bold tw-leading-4 tw-text-white">{{ currentLocationName }}</span>
                </div>
            </div>

            <button
                v-if="!isHrTourActive"
                type="button"
                class="hr-tour-replay-trigger tw-hidden tw-h-[42px] tw-flex-none tw-cursor-pointer tw-items-center tw-gap-2 tw-rounded-sm tw-border tw-border-transparent tw-bg-transparent tw-px-2 tw-py-1 tw-text-white tw-transition-colors hover:tw-border-white hover:tw-text-[#febd69] focus:tw-border-white focus:tw-outline-none lg:tw-inline-flex"
                title="Xem lại các tính năng nổi bật"
                aria-label="Xem lại các tính năng nổi bật"
                @click="replayHrTour"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="tw-h-5 tw-w-5 tw-flex-none"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path
                        d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"
                    />
                    <path d="M12 8v4" />
                    <path d="M12 16h.01" />
                </svg>
                <span class="tw-hidden tw-whitespace-nowrap tw-text-[13px] tw-font-bold xl:tw-inline">HR Tour</span>
            </button>

            <form
                @submit.prevent="submitSearch"
                class="tw-flex tw-h-[40px] tw-min-w-0 tw-flex-grow tw-overflow-hidden tw-rounded-md tw-bg-white focus-within:tw-ring-2 focus-within:tw-ring-[#f3a847] lg:tw-max-w-[620px] xl:tw-max-w-[760px]"
            >
                <select
                    v-model="searchCategory"
                    :style="{ width: searchCategoryWidth }"
                    class="tw-cursor-pointer tw-border-0 tw-border-r tw-border-gray-300 tw-bg-gray-100 tw-pl-2 tw-pr-7 tw-text-[12px] tw-text-gray-700 tw-outline-none hover:tw-bg-gray-200 focus:tw-border-gray-300 focus:tw-outline-none focus:tw-ring-0"
                >
                    <option value="all">All</option>
                    <option value="product">Product</option>
                    <option value="brand">Brand</option>
                </select>

                <input
                    v-model="searchTerm"
                    type="text"
                    :placeholder="currentPlaceholder"
                    class="tw-min-w-0 tw-flex-grow tw-border-none tw-px-3 tw-text-[15px] tw-text-black focus:tw-outline-none focus:tw-ring-0"
                />

                <button
                    type="submit"
                    class="tw-flex tw-w-[45px] tw-flex-none tw-cursor-pointer tw-items-center tw-justify-center tw-bg-[#febd69] tw-transition-colors hover:tw-bg-[#f3a847]"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="tw-h-5 tw-w-5 tw-text-[#333333]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </button>
            </form>

            <div class="tw-group tw-relative tw-hidden tw-h-[50px] lg:tw-flex">
                <router-link
                    v-if="!authStore.isLoggedIn"
                    :to="{ name: 'Login' }"
                    class="tw-flex tw-h-full tw-cursor-pointer tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-bg-transparent tw-px-2 tw-py-1 tw-text-[14px] tw-font-bold tw-leading-4 tw-text-white tw-no-underline hover:tw-border-white focus:tw-border-white focus:tw-outline-none"
                >
                    {{ t('header.account_signIn') }}
                </router-link>
                <button
                    v-else
                    type="button"
                    class="tw-flex tw-h-full tw-cursor-pointer tw-flex-col tw-justify-center tw-rounded-sm tw-border tw-border-transparent tw-bg-transparent tw-px-2 tw-py-1 tw-text-left hover:tw-border-white focus:tw-border-white focus:tw-outline-none"
                >
                    <span class="tw-text-[12px] tw-leading-3 tw-text-white">
                        {{ t('header.account_greeting') }}
                    </span>
                    <span class="tw-text-5 tw-flex tw-items-center tw-font-bold tw-leading-4 tw-text-white">
                        {{ accountDisplayName }}
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="tw-ml-1 tw-h-3 tw-w-3 tw-text-gray-400"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
                </button>

                <div
                    v-if="authStore.isLoggedIn"
                    class="tw-invisible tw-absolute tw-right-0 tw-top-[48px] tw-z-[90] tw-w-60 tw-rounded-md tw-border tw-border-gray-200 tw-bg-white tw-p-2 tw-text-[#111827] tw-opacity-0 tw-shadow-2xl tw-transition-all tw-duration-200 group-hover:tw-visible group-hover:tw-opacity-100"
                >
                    <div
                        class="tw-absolute -tw-top-2 tw-right-7 tw-h-4 tw-w-4 tw-rotate-45 tw-border-l tw-border-t tw-border-gray-200 tw-bg-white"
                    ></div>
                    <div
                        class="tw-absolute -tw-top-2 tw-right-7 tw-h-4 tw-w-4 tw-rotate-45 tw-border-l tw-border-t tw-border-gray-200 tw-bg-white"
                    ></div>

                    <nav class="tw-text-3" :aria-label="t('header.account_aria')">
                        <component
                            :is="item.to ? 'router-link' : 'a'"
                            v-for="item in accountMenuItems"
                            :key="item.title"
                            :to="item.to"
                            :href="item.href"
                            class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-text-[#111827] tw-transition-colors hover:tw-bg-[#f7fafa] hover:tw-text-[#c45500]"
                        >
                            <span
                                class="tw-flex tw-items-center tw-gap-3 tw-rounded-md tw-px-3 tw-py-2.5 hover:tw-bg-gray-50 hover:tw-text-[#c45500]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="tw-h-5 tw-w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.iconPath" />
                                </svg>
                            </span>
                            <span class="tw-min-w-0 tw-flex-1 tw-truncate tw-text-[14px] tw-font-medium tw-leading-5">{{
                                item.title
                            }}</span>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="tw-h-4 tw-w-4 tw-flex-none tw-text-gray-400"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </component>

                        <button
                            v-if="authStore.isLoggedIn"
                            type="button"
                            class="tw-mt-2 tw-flex tw-w-full tw-items-center tw-gap-3 tw-border-t tw-border-gray-100 tw-text-left tw-text-[#111827] tw-transition-colors hover:tw-bg-[#f7fafa] hover:tw-text-[#c45500]"
                            @click="authStore.logout()"
                        >
                            <span
                                class="tw-flex tw-items-center tw-gap-3 tw-rounded-md tw-px-3 tw-py-2.5 hover:tw-bg-gray-50 hover:tw-text-[#c45500]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="tw-h-5 tw-w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H9m4 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6"
                                    />
                                </svg>
                            </span>
                            <span class="tw-text-[14px] tw-font-medium">{{ t('header.account_logout') }}</span>
                        </button>
                    </nav>
                </div>
            </div>

            <button
                type="button"
                class="tw-hidden tw-h-[50px] tw-cursor-pointer tw-flex-col tw-justify-center tw-rounded-sm tw-border tw-border-transparent tw-bg-transparent tw-px-2 tw-py-1 tw-text-left hover:tw-border-white focus:tw-border-white focus:tw-outline-none lg:tw-flex"
                :aria-label="t('header.language_switchTo', { language: nextLanguageLabel })"
                @click="toggleLanguage"
            >
                <span class="tw-text-[12px] tw-leading-3 tw-text-[#cccccc]">{{ t('header.language_label') }}</span>
                <span class="tw-flex tw-items-center tw-gap-1 tw-text-[14px] tw-font-bold tw-leading-4 tw-text-white">
                    {{ currentLanguageLabel }}
                    <span class="tw-text-[12px] tw-font-medium tw-text-[#f59e0b]">/ {{ nextLanguageLabel }}</span>
                </span>
            </button>

            <div class="client-cart tw-group tw-relative tw-h-[50px]">
                <router-link
                    :to="{ name: 'Cart' }"
                    class="tw-flex tw-h-[50px] tw-cursor-pointer tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white"
                >
                    <div class="tw-relative tw-flex tw-items-end tw-pr-1">
                        <span
                            class="tw-absolute tw-left-[10px] tw-top-[-11px] tw-w-full tw-text-center tw-text-[16px] tw-font-bold tw-leading-none tw-text-[#f59e0b]"
                        >
                            {{ cartStore.totalItems }}
                        </span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="tw-h-7 tw-w-7 tw-text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </div>
                    <span class="tw-mb-1 tw-ml-1 tw-text-[14px] tw-font-bold tw-text-white">{{ t('header.cart_label') }}</span>
                </router-link>

                <div
                    class="tw-invisible tw-absolute tw-right-0 tw-top-[48px] tw-z-[90] tw-hidden tw-w-[360px] tw-rounded-sm tw-bg-white tw-p-4 tw-text-[#0f1111] tw-opacity-0 tw-shadow-xl tw-transition-all tw-duration-200 group-hover:tw-visible group-hover:tw-opacity-100 md:tw-block"
                >
                    <div
                        class="tw-absolute -tw-top-2 tw-right-8 tw-h-4 tw-w-4 tw-rotate-45 tw-bg-white tw-shadow-[-2px_-2px_2px_rgba(0,0,0,0.05)]"
                    ></div>

                    <div class="tw-mb-3 tw-flex tw-items-center tw-justify-between tw-border-b tw-border-gray-200 tw-pb-3">
                        <div>
                            <h3 class="tw-m-0 tw-text-[18px] tw-font-bold">{{ t('header.cart_title') }}</h3>
                        </div>
                        <p class="tw-m-0 tw-text-[12px] tw-text-gray-600">
                            {{ t('header.cart_itemCount', { count: cartStore.totalItems }) }}
                        </p>
                    </div>

                    <div v-if="cartStore.isLoading" class="tw-py-8 tw-text-center tw-text-[13px] tw-text-gray-600">
                        {{ t('header.cart_loading') }}
                    </div>
                    <div v-else-if="cartStore.isEmpty" class="tw-py-8 tw-text-center">
                        <p class="tw-mb-3 tw-text-[14px] tw-text-gray-700">{{ t('header.cart_empty') }}</p>
                        <router-link
                            :to="{ name: 'ProductList' }"
                            class="tw-rounded-full tw-bg-[#ffd814] tw-px-4 tw-py-2 tw-text-[13px] tw-text-[#0f1111] hover:tw-bg-[#f7ca00]"
                        >
                            {{ t('header.cart_shopNow') }}
                        </router-link>
                    </div>

                    <template v-else>
                        <div class="tw-grid tw-gap-4">
                            <div v-for="(group, brand) in cartPreviewItemsGroupedByBrand" :key="brand" class="tw-grid tw-gap-3">
                                <h4
                                    class="tw-m-0 tw-border-b tw-border-gray-100 tw-pb-1 tw-text-[13px] tw-font-bold tw-uppercase tw-text-gray-800"
                                >
                                    {{ brand }}
                                </h4>
                                <div
                                    v-for="item in group.items"
                                    :key="item.product_variant_id"
                                    class="tw-grid tw-grid-cols-[64px_1fr] tw-gap-3"
                                >
                                    <img
                                        :src="item.thumbnail"
                                        :alt="item.product_name"
                                        class="tw-h-16 tw-w-16 tw-rounded tw-border tw-border-gray-200 tw-object-cover"
                                    />
                                    <div class="tw-min-w-0">
                                        <router-link
                                            :to="
                                                item.product_slug
                                                    ? { name: 'ProductDetail', params: { slug: item.product_slug } }
                                                    : { name: 'ProductList' }
                                            "
                                            class="tw-block tw-truncate tw-text-[13px] tw-font-semibold tw-text-[#0f1111] hover:tw-text-[#c45500]"
                                        >
                                            {{ item.product_name }}
                                        </router-link>
                                        <p class="tw-m-0 tw-text-[12px] tw-text-gray-600">
                                            {{ t('header.cart_quantity', { quantity: item.quantity }) }}
                                            <template v-if="item.unit_name"> - Đơn vị: {{ item.unit_name }}</template>
                                        </p>
                                        <p class="tw-m-0 tw-text-[13px] tw-font-bold">
                                            <span
                                                v-if="item.compare_at_price"
                                                class="tw-mr-1 tw-text-[11px] tw-font-normal tw-text-gray-400 tw-line-through"
                                            >
                                                {{ formatPrice(item.compare_at_price * item.quantity) }}
                                            </span>
                                            {{ formatPrice(item.line_total) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="group.totalCount > 2" class="-tw-mt-2 tw-text-center">
                                    <router-link :to="{ name: 'Cart' }" class="tw-text-[12px] tw-text-blue-600 hover:tw-underline">
                                        Xem thêm {{ group.totalCount - 2 }} sản phẩm thuộc {{ brand }}
                                    </router-link>
                                </div>
                            </div>
                        </div>

                        <p v-if="cartStore.items.length > previewItemCount" class="tw-mt-3 tw-text-[12px] tw-text-gray-600">
                            {{ t('header.cart_moreItems', { count: cartStore.items.length - previewItemCount }) }}
                        </p>

                        <div class="tw-mt-4 tw-flex tw-items-center tw-justify-between tw-border-t tw-border-gray-200 tw-pt-3">
                            <div class="tw-text-[14px] tw-font-bold tw-text-[#0f1111]">
                                Total: <span class="tw-text-[#B12704]">{{ formatPrice(cartStore.subtotal) }}</span>
                            </div>
                            <router-link
                                :to="{ name: 'Cart' }"
                                class="tw-rounded-full tw-bg-[#ffd814] tw-px-5 tw-py-2 tw-text-center tw-text-[13px] tw-font-semibold tw-text-[#0f1111] tw-shadow-sm hover:tw-bg-[#f7ca00]"
                            >
                                {{ t('header.cart_viewCart') }}
                            </router-link>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <LocationModal v-if="isLocationModalOpen" @close="isLocationModalOpen = false" />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useCartStore } from '../stores/cart';
import { useLocationStore } from '@/stores/location';
import { APP_CONFIG } from '@/config';
import LocationModal from '@/components/Modals/LocationModal.vue';

const HR_TOUR_REPLAY_EVENT = 'hr-tour:replay';
const HR_TOUR_ACTIVE_CHANGE_EVENT = 'hr-tour:active-change';

const authStore = useAuthStore();
const cartStore = useCartStore();
const locationStore = useLocationStore();

const router = useRouter();
const { locale, t } = useI18n();

const searchTerm = ref('');
const searchCategory = ref('product');

const searchCategoryWidth = computed(() => {
    switch (searchCategory.value) {
        case 'all': return '56px';
        case 'brand': return '68px';
        case 'product': default: return '82px';
    }
});
const currentPlaceholder = computed(() => {
    if (searchCategory.value === 'product') {
        return t('header.search_placeholder_product');
    }
    if (searchCategory.value === 'brand') {
        return t('header.search_placeholder_brand');
    }
    return t('header.search_placeholder_all');
});
const isLocationModalOpen = ref(false);
const isHrTourActive = ref(false);
const cartPreviewItemsGroupedByBrand = computed(() => {
    const groups = {};
    cartStore.items.forEach((item) => {
        const brand = item.brand_name || 'No Brand';
        if (!groups[brand]) {
            groups[brand] = { items: [], totalCount: 0 };
        }
        groups[brand].totalCount++;
        if (groups[brand].items.length < 2) {
            groups[brand].items.push(item);
        }
    });
    return groups;
});
const previewItemCount = computed(() => {
    return Object.values(cartPreviewItemsGroupedByBrand.value).reduce((sum, group) => sum + group.items.length, 0);
});
const currentLocationName = computed(() => locationStore.currentLocationName);
const currentLanguageLabel = computed(() => locale.value.toUpperCase());
const nextLanguageLabel = computed(() => (locale.value === 'vi' ? 'EN' : 'VI'));
const accountDisplayName = computed(() => authStore.user?.name || t('header.account_label'));
let originalBodyOverflow = '';
let isBodyScrollLockedByLocationModal = false;

const accountMenuItems = computed(() => [
    {
        title: t('header.account_orders'),
        iconPath: 'M9 5h6m-8 4h10m-10 4h10M7 3h10a2 2 0 012 2v14l-4-2-3 2-3-2-4 2V5a2 2 0 012-2z',
        to: { name: 'MyOrders' },
    },
    {
        title: t('header.account_security'),
        iconPath:
            'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        href: '#',
    },
    {
        title: t('header.account_addresses'),
        iconPath: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        to: { name: 'CustomerAddresses' },
    },
    {
        title: t('header.account_payments'),
        iconPath: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        href: '#',
    },
]);

const submitSearch = () => {
    const keyword = searchTerm.value.trim();
    router.push({ name: 'ProductList', query: keyword ? { keyword } : {} });
};

const toggleLanguage = () => {
    const nextLocale = locale.value === 'vi' ? 'en' : 'vi';
    locale.value = nextLocale;
    localStorage.setItem('user_locale', nextLocale);
    document.documentElement.lang = nextLocale;
};

const replayHrTour = () => {
    window.dispatchEvent(new CustomEvent(HR_TOUR_REPLAY_EVENT));
};

const handleHrTourActiveChange = (event) => {
    isHrTourActive.value = Boolean(event.detail?.isActive);
};

const formatPrice = (price) => {
    const numericPrice = Number(price || 0);

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(numericPrice);
};

onMounted(() => {
    document.documentElement.lang = locale.value;
    cartStore.fetchCart().catch(() => {});
    window.addEventListener(HR_TOUR_ACTIVE_CHANGE_EVENT, handleHrTourActiveChange);
});

const lockBodyScroll = () => {
    if (isBodyScrollLockedByLocationModal) {
        return;
    }

    originalBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    isBodyScrollLockedByLocationModal = true;
};

const unlockBodyScroll = () => {
    if (!isBodyScrollLockedByLocationModal) {
        return;
    }

    document.body.style.overflow = originalBodyOverflow;
    originalBodyOverflow = '';
    isBodyScrollLockedByLocationModal = false;
};

watch(isLocationModalOpen, (isOpen) => {
    if (isOpen) {
        lockBodyScroll();
        return;
    }

    unlockBodyScroll();
});

onBeforeUnmount(() => {
    window.removeEventListener(HR_TOUR_ACTIVE_CHANGE_EVENT, handleHrTourActiveChange);
    unlockBodyScroll();
});
</script>
