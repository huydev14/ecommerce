<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';

const isMenuOpen = ref(false);

const primarySections = [
    {
        title: 'Digital Content & Devices',
        items: ['Prime Video', 'Amazon Music', 'Kindle E-readers & Books', 'Amazon Appstore'],
    },
    {
        title: 'Shop by Department',
        items: ['Electronics', 'Computers', 'Smart Home', 'Arts & Crafts', 'See all'],
    },
    {
        title: 'Programs & Features',
        items: ['Gift Cards', 'Shop By Interest', 'Amazon Live', 'International Shopping', 'See all'],
    },
    {
        title: 'Help & Settings',
        items: ['Your Account', 'English', 'United States'],
    },
];

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
        <div class="tw-flex tw-h-[39px] tw-items-center tw-gap-1 tw-overflow-x-auto tw-px-3 tw-text-[14px] tw-font-medium md:tw-gap-2 md:tw-px-4">
            <button
                type="button"
                @click="openMenu"
                class="tw-flex tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-mr-1 tw-h-5 tw-w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Tất cả
            </button>

            <a
                href="#"
                class="tw-flex tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white"
                >Today's Deals</a
            >
            <a
                href="#"
                class="tw-flex tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white"
                >Dịch vụ khách hàng</a
            >
            <a
                href="#"
                class="tw-hidden tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white sm:tw-flex"
                >Registry</a
            >
            <a
                href="#"
                class="tw-hidden tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white sm:tw-flex"
                >Gift Cards</a
            >
            <a
                href="#"
                class="tw-hidden tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white md:tw-flex"
                >Sell</a
            >
            <a
                href="#"
                class="tw-hidden tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white lg:tw-flex"
                >Amazon Pay</a
            >
            <a
                href="#"
                class="tw-hidden tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white lg:tw-flex"
                >Prime</a
            >
            <a
                href="#"
                class="tw-hidden tw-flex-none tw-items-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white xl:tw-flex"
                >Buy Again</a
            >
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
                    <div class="tw-flex tw-items-center tw-gap-3">
                        <div class="tw-grid tw-h-8 tw-w-8 tw-place-items-center tw-rounded-full tw-bg-white/15">
                            <svg xmlns="http://www.w3.org/2000/svg" class="tw-h-5 tw-w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div>
                            <div class="tw-text-base tw-font-bold">Hello, sign in</div>
                            <div class="tw-text-[12px] tw-text-white/75">Browse Amazon-style categories</div>
                        </div>
                    </div>

                    <button type="button" class="tw-grid tw-h-8 tw-w-8 tw-place-items-center tw-rounded-full hover:tw-bg-white/10" @click="closeMenu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="tw-h-5 tw-w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="tw-divide-y tw-divide-gray-200 tw-bg-white">
                    <section v-for="section in primarySections" :key="section.title" class="tw-px-0 tw-py-2">
                        <h3 class="tw-px-5 tw-py-3 tw-text-[17px] tw-font-bold tw-text-[#111827]">{{ section.title }}</h3>

                        <div class="tw-space-y-1 tw-pb-2">
                            <a
                                v-for="item in section.items"
                                :key="item"
                                href="#"
                                class="tw-flex tw-items-center tw-justify-between tw-px-5 tw-py-2 tw-text-[14px] tw-text-[#111827] hover:tw-bg-gray-50"
                            >
                                <span>{{ item }}</span>
                                <svg
                                    v-if="item !== 'See all' && item !== 'Your Account' && item !== 'English' && item !== 'United States'"
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
