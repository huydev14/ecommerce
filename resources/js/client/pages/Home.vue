<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import api from '../services/api';
import { APP_CONFIG } from '@/config';
import { useCartStore } from '@/stores/cart';
import BestSellersSlider from '@/components/BestSellersSlider.vue';
import FeaturedProductsSlider from '@/components/FeaturedProductsSlider.vue';
import NewProductsSection from '@/components/NewProductsSection.vue';
import 'swiper/css';
import 'swiper/css/navigation';

const cartStore = useCartStore();
const { t } = useI18n();

const banners = ref([]);
const categories = ref([]);
const bestSellers = ref([]);
const featuredProducts = ref([]);
const newProducts = ref([]);
const addingProductIds = ref(new Set());
const isHomeDataLoading = ref(true);
const swiperModules = [Autoplay, Pagination, Navigation];
const STATIC_BANNER_SLOT_COUNT = 5;

const normalizeProducts = (payload) => {
    if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
};

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

const menuCategories = computed(() => {
    const categoriesWithChildren = categories.value.filter(hasChildren);
    const categoriesWithoutChildren = categories.value.filter((category) => !hasChildren(category));

    return [...categoriesWithChildren, ...categoriesWithoutChildren];
});

const sortedBanners = computed(() => {
    return [...banners.value].sort((firstBanner, secondBanner) => {
        const firstOrder = Number(firstBanner.sort_order ?? 0);
        const secondOrder = Number(secondBanner.sort_order ?? 0);

        if (firstOrder === secondOrder) {
            return Number(firstBanner.id ?? 0) - Number(secondBanner.id ?? 0);
        }

        return firstOrder - secondOrder;
    });
});

const sliderBanners = computed(() =>
    sortedBanners.value.filter((banner) => Number(banner.sort_order) >= 1 && Number(banner.sort_order) <= 3),
);

const staticGridBanners = computed(() =>
    sortedBanners.value.filter((banner) => Number(banner.sort_order) >= 4).slice(0, STATIC_BANNER_SLOT_COUNT),
);

const showBannerPlaceholders = computed(() => isHomeDataLoading.value || sortedBanners.value.length === 0);

const staticBannerPlaceholderCount = computed(() => {
    if (showBannerPlaceholders.value) {
        return STATIC_BANNER_SLOT_COUNT;
    }

    return Math.max(0, STATIC_BANNER_SLOT_COUNT - staticGridBanners.value.length);
});

const getProductVariantId = (product) =>
    product.product_variant_id || product.default_variant_id || product.variant_id || product.variants?.[0]?.id;

const getProductKey = (product) => product.id || product.slug || product.name;

const isAddingToCart = (product) => addingProductIds.value.has(getProductKey(product));

const addProductToCart = async (product) => {
    const variantId = getProductVariantId(product);

    if (!variantId) {
        return;
    }

    const productKey = getProductKey(product);

    addingProductIds.value = new Set(addingProductIds.value).add(productKey);

    try {
        await cartStore.addItem(variantId, 1);
    } catch (error) {
        console.error(t('home.errors_addToCart'), error);
    } finally {
        const nextAddingProductIds = new Set(addingProductIds.value);
        nextAddingProductIds.delete(productKey);
        addingProductIds.value = nextAddingProductIds;
    }
};

const bannerImageUrl = (banner) => {
    const imageUrl = banner?.optimized_image_url;

    if (!imageUrl) {
        return null;
    }

    return imageUrl;
};

const categoryIconUrl = (icon) => {
    if (!icon) {
        return null;
    }

    if (/^https?:\/\//i.test(icon) || icon.startsWith('/')) {
        return icon;
    }

    if (/\.(svg|png|jpe?g|webp|gif)$/i.test(icon)) {
        return `/storage/${icon}`;
    }

    return null;
};

const categoryRoute = (category) => ({
    name: 'ProductList',
    query: category.slug ? { category: category.slug } : {},
});

const fetchHomeData = async () => {
    isHomeDataLoading.value = true;

    try {
        const response = await api.get('/home');

        if (response.data?.success) {
            banners.value = Array.isArray(response.data.data?.banners) ? response.data.data.banners : [];
        }
        isHomeDataLoading.value = false;
    } catch (error) {
        isHomeDataLoading.value = false;
        console.error(t('home.errors_fetchHomeData'), error);
    }
};

const fetchCategories = async () => {
    try {
        const response = await api.get('/categories/tree');

        if (response.data?.success) {
            categories.value = getCategoryItems(response.data);
        }
    } catch (error) {
        console.error(t('home.errors_fetchCategories'), error);
    }
};

const fetchBestSellers = async () => {
    try {
        const response = await api.get('/products/best-sellers', {
            params: {
                limit: 15,
            },
        });

        bestSellers.value = normalizeProducts(response.data);
    } catch (error) {
        console.error(t('home.errors_fetchBestSellers'), error);
    }
};

const fetchFeaturedProducts = async () => {
    try {
        const response = await api.get('/products/featured-products');

        if (response.data?.success) {
            featuredProducts.value = normalizeProducts(response.data);
        }
    } catch (error) {
        console.error(t('home.errors_fetchFeaturedProducts'), error);
    }
};

const fetchNewArrivals = async () => {
    try {
        const response = await api.get('/products/new-arrivals');

        if (response.data?.success) {
            newProducts.value = normalizeProducts(response.data);
        }
    } catch (error) {
        console.error(t('home.errors_fetchNewArrivals'), error);
    }
};

onMounted(() => {
    fetchHomeData();
    fetchCategories();
    fetchBestSellers();
    fetchFeaturedProducts();
    fetchNewArrivals();
});
</script>

<template>
    <div class="home">
        <section class="home-hero" :aria-label="t('home.aria_hero')">
            <aside class="home-category-menu" :aria-label="t('home.aria_categoryMenu')">
                <ul v-if="menuCategories.length > 0" class="home-category-menu__list">
                    <li
                        v-for="category in menuCategories"
                        :key="category.id"
                        class="home-category-menu__item"
                        :class="{ 'home-category-menu__item--has-children': hasChildren(category) }"
                    >
                        <router-link :to="categoryRoute(category)" class="home-category-menu__link">
                            <span class="home-category-menu__icon" aria-hidden="true">
                                <img v-if="categoryIconUrl(category.icon)" :src="categoryIconUrl(category.icon)" :alt="category.name" />
                                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6ZM14 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V6ZM4 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2ZM14 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-2Z"
                                    />
                                </svg>
                            </span>
                            <span class="home-category-menu__name">{{ category.name }}</span>
                            <svg class="home-category-menu__arrow" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path
                                    fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 1 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </router-link>

                        <div
                            v-if="hasChildren(category)"
                            class="home-category-flyout"
                            role="menu"
                            :aria-label="t('home.aria_categoryChildren', { name: category.name })"
                        >
                            <router-link :to="categoryRoute(category)" class="home-category-flyout__heading">
                                {{ category.name }}
                            </router-link>

                            <div class="home-category-flyout__grid">
                                <div v-for="child in category.children" :key="child.id" class="home-category-flyout__group">
                                    <router-link :to="categoryRoute(child)" class="home-category-flyout__parent" role="menuitem">
                                        {{ child.name }}
                                    </router-link>

                                    <router-link
                                        v-for="grandchild in child.children || []"
                                        :key="grandchild.id"
                                        :to="categoryRoute(grandchild)"
                                        class="home-category-flyout__child"
                                        role="menuitem"
                                    >
                                        {{ grandchild.name }}
                                    </router-link>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>

                <div v-else class="home-category-menu__empty">{{ t('home.empty_categories') }}</div>
            </aside>

            <div class="home-banner-grid" :aria-label="t('home.aria_promoBanners')">
                <div class="home-banner-slider">
                    <swiper
                        v-if="sliderBanners.length > 0"
                        :space-between="0"
                        :autoplay="{
                            delay: 4000,
                            disableOnInteraction: false,
                        }"
                        :pagination="{
                            clickable: true,
                        }"
                        :navigation="sliderBanners.length > 1"
                        :modules="swiperModules"
                        :loop="sliderBanners.length > 1"
                        class="home-banner-swiper"
                    >
                        <swiper-slide v-for="banner in sliderBanners" :key="banner.id">
                            <a v-if="banner.link" :href="banner.link" class="home-banner-slide">
                                <img
                                    v-if="bannerImageUrl(banner)"
                                    :src="bannerImageUrl(banner)"
                                    :alt="banner.title || t('home.banner_alt', { appName: APP_CONFIG.appName })"
                                />
                            </a>
                            <div v-else class="home-banner-slide">
                                <img
                                    v-if="bannerImageUrl(banner)"
                                    :src="bannerImageUrl(banner)"
                                    :alt="banner.title || t('home.banner_alt', { appName: APP_CONFIG.appName })"
                                />
                            </div>
                        </swiper-slide>
                    </swiper>

                    <div v-else class="home-banner-placeholder home-banner-placeholder--hero" aria-hidden="true">
                        <span></span>
                    </div>
                </div>

                <a
                    v-for="banner in staticGridBanners"
                    :key="banner.id"
                    :href="banner.link || '#'"
                    class="home-banner-tile"
                    :aria-label="banner.title || t('home.aria_promoBanner')"
                >
                    <img
                        v-if="bannerImageUrl(banner)"
                        :src="bannerImageUrl(banner)"
                        :alt="banner.title || t('home.banner_alt', { appName: APP_CONFIG.appName })"
                        loading="lazy"
                    />
                </a>

                <div
                    v-for="placeholderIndex in staticBannerPlaceholderCount"
                    :key="`banner-placeholder-${placeholderIndex}`"
                    class="home-banner-tile home-banner-placeholder"
                    aria-hidden="true"
                >
                    <span></span>
                </div>
            </div>
        </section>

        <div class="home__content">
            <BestSellersSlider :products="bestSellers" :is-product-adding="isAddingToCart" @add-to-cart="addProductToCart" />

            <FeaturedProductsSlider :products="featuredProducts" :is-product-adding="isAddingToCart" @add-to-cart="addProductToCart" />

            <NewProductsSection :products="newProducts" :is-product-adding="isAddingToCart" @add-to-cart="addProductToCart" />
        </div>
    </div>
</template>

<style scoped>
.home {
    min-height: 100%;
    background: #e3e6e6;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.home-hero {
    display: grid;
    grid-template-columns: 250px minmax(0, 1fr);
    gap: 12px;
    max-width: 1500px;
    margin: 0 auto;
    padding: 14px 20px 0;
}

.home-category-menu {
    position: relative;
    z-index: 5;
    align-self: start;
    height: 600px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.home-category-menu__list {
    margin: 0;
    padding: 8px 0;
    list-style: none;
}

.home-category-menu__item {
    position: static;
}

.home-category-menu__link {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    align-items: center;
    min-height: 36px;
    padding: 7px 16px;
    color: #111827;
    font-size: 14px;
    line-height: 18px;
    text-decoration: none;
    transition:
        background 0.2s ease,
        color 0.2s ease;
}

.home-category-menu__link:hover,
.home-category-menu__item:focus-within > .home-category-menu__link {
    background: #f3f4f6;
    color: #0f62fe;
}

.home-category-menu__icon {
    display: grid;
    width: 24px;
    height: 24px;
    place-items: center;
    color: #4b5563;
}

.home-category-menu__icon svg,
.home-category-menu__icon img {
    width: 18px;
    height: 18px;
    object-fit: contain;
}

.home-category-menu__name {
    flex: 1;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-transform: capitalize;
}

.home-category-menu__arrow {
    width: 16px;
    height: 16px;
    color: #6b7280;
    opacity: 0;
    transition: transform 0.2s ease;
}

.home-category-menu__item--has-children .home-category-menu__arrow {
    opacity: 1;
}

.home-category-menu__item--has-children:hover .home-category-menu__arrow {
    transform: translateX(3px);
}

.home-category-flyout {
    position: absolute;
    top: 0;
    left: 100%;
    z-index: 20;
    margin-left: 4px;
    width: min(420px, calc(100vw - 300px));
    min-height: 100%;
    max-height: 100%;
    overflow-y: auto;

    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    padding: 16px;

    visibility: hidden;
    opacity: 0;
    transform: translateX(-8px);
    transition:
        opacity 0.25s ease,
        transform 0.25s ease,
        visibility 0.25s ease;
}

.home-category-menu__item--has-children:hover > .home-category-flyout,
.home-category-menu__item--has-children:focus-within > .home-category-flyout {
    visibility: visible;
    opacity: 1;
    transform: translateX(0);
}

.home-category-flyout::before {
    content: '';
    position: absolute;
    top: 0;
    left: -12px;
    width: 12px;
    height: 100%;
    background: transparent;
}

.home-category-flyout::-webkit-scrollbar {
    width: 6px;
}
.home-category-flyout::-webkit-scrollbar-track {
    background: transparent;
}
.home-category-flyout::-webkit-scrollbar-thumb {
    background-color: #d1d5db;
    border-radius: 10px;
}

.home-category-flyout__heading {
    display: block;
    margin-bottom: 12px;
    color: #111827;
    font-size: 15px;
    font-weight: 700;
    line-height: 20px;
    text-decoration: none;
    text-transform: capitalize;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 9px;
}

.home-category-flyout__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: 18px;
    row-gap: 12px;
}

.home-category-flyout__parent {
    display: block;
    overflow: hidden;
    margin-bottom: 5px;
    font-size: 13px;
    line-height: 18px;
    text-decoration: none;
    text-overflow: ellipsis;
    text-transform: capitalize;
    white-space: nowrap;
    transition: color 0.15s ease;
}

.home-category-flyout__child {
    display: block;
    overflow: hidden;
    margin-top: 4px;
    color: #4b5563;
    font-size: 13px;
    line-height: 18px;
    text-decoration: none;
    text-overflow: ellipsis;
    text-transform: capitalize;
    white-space: nowrap;
    transition: color 0.15s ease;
}

.home-category-flyout__heading:hover,
.home-category-flyout__parent:hover,
.home-category-flyout__child:hover {
    color: #0f62fe;
}

.home-banner-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-template-rows: repeat(3, minmax(0, 1fr));
    grid-auto-flow: row dense;
    gap: 10px;
    min-width: 0;
    height: 600px;
}

.home-banner-slider {
    position: relative;
    grid-column: span 2;
    grid-row: span 2;
    overflow: hidden;
    border-radius: 8px;
    background: #111827;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.home-banner-swiper {
    width: 100%;
    height: 100%;
}

.home-banner-slide,
.home-banner-tile {
    display: block;
    width: 100%;
    height: 100%;
    overflow: hidden;
    background: #f3f3f3;
}

.home-banner-slide img,
.home-banner-tile img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.home-banner-tile {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.home-banner-tile img {
    transition: transform 0.25s ease;
}

.home-banner-tile:hover img {
    transform: scale(1.04);
}

.home-banner-placeholder {
    display: grid;
    min-height: 0;
    place-items: center;
    border: 1px solid #e5e7eb;
    background: linear-gradient(100deg, #f3f4f6 0%, #e5e7eb 45%, #f3f4f6 100%);
    background-size: 200% 100%;
    animation: banner-placeholder-pulse 1.4s ease-in-out infinite;
}

.home-banner-placeholder--hero {
    height: 100%;
    border: 0;
}

.home-banner-placeholder span {
    width: 30%;
    max-width: 180px;
    height: 10px;
    border-radius: 999px;
    background: rgba(156, 163, 175, 0.55);
}

@keyframes banner-placeholder-pulse {
    0% {
        background-position: 100% 0;
    }

    100% {
        background-position: -100% 0;
    }
}

.home-banner-slider :deep(.swiper-button-next),
.home-banner-slider :deep(.swiper-button-prev) {
    width: 44px;
    height: 70px;
    color: #e9e9e954;
}

.home-banner-slider :deep(.swiper-button-next::after),
.home-banner-slider :deep(.swiper-button-prev::after) {
    font-size: 24px;
    font-weight: 700;
}

.home-banner-slider :deep(.swiper-pagination-bullet-active) {
    background: #f0c14b;
}

.pill-button,
.signin-card__button,
.outline-button,
.a-button-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    line-height: 18px;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid;
    transition: background 0.2s;
}

.pill-button,
.signin-card__button,
.a-button-primary {
    background: #f0c14b;
    border-color: #a88734 #9c7e31 #846a29;
    color: #111;
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.4) inset;
}

.pill-button:hover,
.signin-card__button:hover,
.a-button-primary:hover {
    background: #f4d078;
}

.outline-button {
    border: 1px solid #d5d9d9;
    background: #fff;
    color: #0f1111;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.12);
}

.home__content {
    position: relative;
    z-index: 2;
    max-width: 1500px;
    margin: 0 auto;
    padding: 0 20px 38px;
}

.card,
.rail,
.feature-band {
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.card {
    display: flex;
    min-height: 420px;
    flex-direction: column;
    padding: 20px;
}

.card--compact {
    min-height: 150px;
}

.card h2,
.feature-band h2 {
    margin: 0;
    color: #0f1111;
    font-size: 21px;
    font-weight: 700;
    line-height: 27px;
    letter-spacing: 0;
}

.card__tiles {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 16px;
}

.tile {
    display: flex;
    min-height: 128px;
    flex-direction: column;
    justify-content: flex-end;
    padding: 10px;
    background: #eef2f2;
    color: #0f1111;
    font-size: 12px;
    line-height: 16px;
    text-decoration: none;
}

.tile__visual {
    display: grid;
    flex: 1;
    place-items: center;
    margin-bottom: 8px;
    background: #fff;
    font-size: 42px;
}

.link {
    display: inline-flex;
    width: fit-content;
    margin-top: auto;
    color: #007185;
    font-size: 13px;
    line-height: 18px;
    text-decoration: none;
}

.link:hover,
.product__name:hover,
.category-product:hover b {
    color: #c7511f;
    text-decoration: underline;
}

.signin-card {
    display: grid;
    grid-template-rows: auto 1fr;
    gap: 20px;
}

.signin-card__button {
    width: 100%;
    margin: 10px 0 14px;
}

.ad-card {
    display: grid;
    min-height: 250px;
    place-items: center;
    background: #f7fafa;
    color: #566;
    font-size: 54px;
    letter-spacing: 0;
}

.rail {
    margin-top: 20px;
    padding: 18px 20px;
}

.section-heading {
    display: flex;
    align-items: baseline;
    gap: 14px;
    justify-content: space-between;
    margin-bottom: 14px;
}

.section-heading .link {
    margin-top: 0;
}

.product-row {
    display: grid;
    grid-template-columns: repeat(8, minmax(140px, 1fr));
    gap: 18px;
}

.product {
    min-width: 0;
}

.product__image {
    display: grid;
    height: 150px;
    place-items: center;
    margin-bottom: 10px;
    background: #f7fafa;
    font-size: 50px;
    text-decoration: none;
}

.product__deal {
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 24px;
    color: #cc0c39;
    font-size: 12px;
    font-weight: 700;
}

.product__deal span {
    display: inline-flex;
    align-items: center;
    min-height: 22px;
    padding: 3px 6px;
    background: #cc0c39;
    color: #fff;
}

.product__name {
    display: block;
    margin: 8px 0 4px;
    color: #0f1111;
    font-size: 14px;
    line-height: 18px;
    text-decoration: none;
}

.product__rating {
    color: #ffa41c;
    font-size: 13px;
    line-height: 18px;
}

.product__price {
    margin-top: 4px;
    font-size: 20px;
    line-height: 24px;
}

.feature-band {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-top: 20px;
    padding: 24px 28px;
    border-top: 4px solid #ff9900;
}

.feature-band__eyebrow {
    display: block;
    margin-bottom: 6px;
    color: #007185;
    font-size: 13px;
    font-weight: 700;
}

.feature-band p {
    max-width: 720px;
    margin: 8px 0 0;
    color: #27323a;
    font-size: 15px;
    line-height: 22px;
}

.outline-button {
    flex: none;
    border: 1px solid #d5d9d9;
    background: #fff;
    color: #0f1111;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.12);
}

.rail--compact {
    padding-bottom: 20px;
}

.category-row {
    display: grid;
    grid-template-columns: repeat(6, minmax(140px, 1fr));
    gap: 16px;
}

.category-product {
    display: grid;
    min-height: 140px;
    place-items: center;
    padding: 16px;
    background: #f7fafa;
    color: #0f1111;
    text-align: center;
    text-decoration: none;
}

.category-product span {
    font-size: 44px;
}

.category-product b {
    margin-top: 8px;
    font-size: 14px;
    line-height: 18px;
}

@media (max-width: 1280px) {
    .product-row {
        grid-template-columns: repeat(4, minmax(150px, 1fr));
    }

    .category-row {
        grid-template-columns: repeat(3, minmax(150px, 1fr));
    }

}

@media (max-width: 768px) {
    .home-hero {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 12px 12px 0;
    }

    .home-category-menu__list {
        grid-auto-flow: column;
        grid-auto-columns: minmax(170px, 1fr);
        overflow-x: auto;
        padding: 8px;
        scroll-snap-type: x proximity;
    }

    .home-category-menu {
        height: auto;
        overflow: hidden;
    }

    .home-category-menu__item {
        scroll-snap-align: start;
    }

    .home-category-menu__link {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .home-category-flyout,
    .home-category-menu__item--has-children:hover > .home-category-flyout,
    .home-category-menu__item--has-children:focus-within > .home-category-flyout {
        display: none;
    }

    .home-banner-grid {
        grid-template-columns: 1fr;
        grid-template-rows: none;
        grid-auto-rows: 130px;
        height: auto;
    }

    .home-banner-slider {
        grid-column: auto;
        grid-row: auto;
        height: 260px;
    }

    .home__content {
        padding: 0 12px 28px;
    }

    .product-row,
    .category-row {
        grid-template-columns: 1fr;
    }

    .product-row,
    .category-row {
        gap: 12px;
    }

    .product {
        display: grid;
        grid-template-columns: 120px 1fr;
        column-gap: 14px;
        align-items: start;
    }

    .product__image {
        grid-row: span 4;
        height: 120px;
        margin-bottom: 0;
    }

    .feature-band {
        align-items: stretch;
        flex-direction: column;
        padding: 20px;
    }

    .outline-button {
        width: 100%;
    }
}
</style>
