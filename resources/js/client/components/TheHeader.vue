<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();
const searchTerm = ref('fitness clothing');

const submitSearch = () => {
    const query = searchTerm.value.trim() || 'fitness clothing';
    router.push({ name: 'ProductList', query: { q: query } });
};
</script>

<template>
    <div class="tw-bg-[#131921] tw-text-white">
        <div class="tw-flex tw-items-center tw-gap-1 tw-px-2 tw-py-2 md:tw-gap-4 md:tw-px-4">
            <router-link
                :to="{ name: 'Home' }"
                class="tw-flex-none tw-cursor-pointer tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white"
            >
                <img
                    src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg"
                    alt="WorkHub Logo"
                    class="tw-mt-2 tw-h-7 tw-object-contain tw-invert tw-filter md:tw-h-8"
                />
            </router-link>

            <div
                class="tw-hidden tw-flex-none tw-cursor-pointer tw-flex-col tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white lg:tw-flex"
            >
                <span class="tw-pl-4 tw-text-[12px] tw-leading-3 tw-text-[#cccccc]">Giao đến {{ authStore.isLoggedIn && authStore.user ? authStore.user.name : 'Huy' }}</span>
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
                    <span class="tw-text-[14px] tw-font-bold tw-leading-4 tw-text-white">TP. Hồ Chí Minh</span>
                </div>
            </div>

            <form
                @submit.prevent="submitSearch"
                class="tw-flex tw-h-[40px] tw-flex-grow tw-overflow-hidden tw-rounded-md tw-bg-white focus-within:tw-ring-2 focus-within:tw-ring-[#f3a847]"
            >
                <select
                    class="tw-hidden tw-cursor-pointer tw-border-r tw-border-gray-300 tw-bg-[#f3f3f3] tw-px-3 tw-text-xs tw-text-gray-700 hover:tw-bg-[#dadada] focus:tw-outline-none md:tw-block"
                >
                    <option>Tất cả</option>
                    <option>Công nghệ</option>
                    <option>Thời trang</option>
                    <option>Nhà cửa</option>
                </select>

                <input
                    v-model="searchTerm"
                    type="text"
                    placeholder="Tìm kiếm sản phẩm..."
                    class="tw-flex-grow tw-px-3 tw-text-[15px] tw-text-black focus:tw-outline-none"
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

            <div class="tw-group tw-relative tw-hidden tw-h-[50px] md:tw-flex">
                <router-link
                    :to="{ name: 'Login' }"
                    class="tw-flex tw-h-full tw-cursor-pointer tw-flex-col tw-justify-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white"
                >
                    <span class="tw-text-[12px] tw-leading-3 tw-text-white">Xin chào, {{ authStore.isLoggedIn && authStore.user ? authStore.user.name : 'Đăng nhập' }}</span>
                    <span class="tw-flex tw-items-center tw-text-[14px] tw-font-bold tw-leading-4 tw-text-white">
                        Tài khoản & Danh sách
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
                </router-link>

                <!-- Dropdown menu -->
                <div
                    class="tw-invisible tw-absolute tw-right-0 tw-top-[48px] tw-z-50 tw-w-[400px] tw-rounded-sm tw-bg-white tw-p-4 tw-text-black tw-opacity-0 tw-shadow-xl tw-transition-all tw-duration-200 group-hover:tw-visible group-hover:tw-opacity-100"
                >
                    <!-- Triangle pointing up -->
                    <div class="tw-absolute -tw-top-2 tw-right-6 tw-h-4 tw-w-4 tw-rotate-45 tw-bg-white tw-shadow-[-2px_-2px_2px_rgba(0,0,0,0.05)]"></div>

                    <div v-if="!authStore.isLoggedIn" class="tw-mb-4 tw-flex tw-flex-col tw-items-center tw-border-b tw-border-gray-200 tw-pb-4">
                        <router-link :to="{ name: 'Login' }" class="tw-mb-2 tw-w-48 tw-rounded-md  tw-py-1.5 tw-text-center a-button-primary">
                            Đăng nhập
                        </router-link>
                        <div class="tw-text-[11px] tw-text-gray-600">Khách hàng mới? <a href="#" class="tw-text-blue-600 hover:tw-text-[#c45500] hover:tw-underline">Bắt đầu tại đây.</a></div>
                    </div>

                    <div class="tw-flex tw-justify-between tw-text-[13px]">
                        <div class="tw-w-1/2 tw-pr-4">
                            <h3 class="tw-mb-2 tw-text-[16px] tw-font-bold">Danh sách của bạn</h3>
                            <ul class="tw-space-y-1 tw-text-gray-600">
                                <li><a href="#" class="hover:tw-text-[#c45500] hover:tw-underline">Tạo danh sách</a></li>
                                <li><a href="#" class="hover:tw-text-[#c45500] hover:tw-underline">Tìm danh sách</a></li>
                                <li><a href="#" class="hover:tw-text-[#c45500] hover:tw-underline">Danh sách mong muốn</a></li>
                            </ul>
                        </div>
                        <div class="tw-w-1/2 tw-border-l tw-border-gray-200 tw-pl-4">
                            <h3 class="tw-mb-2 tw-text-[16px] tw-font-bold">Tài khoản của bạn</h3>
                            <ul class="tw-space-y-1 tw-text-gray-600">
                                <li><a href="#" class="hover:tw-text-[#c45500] hover:tw-underline">Tài khoản</a></li>
                                <li><a href="#" class="hover:tw-text-[#c45500] hover:tw-underline">Đơn hàng</a></li>
                                <li><a href="#" class="hover:tw-text-[#c45500] hover:tw-underline">Đề xuất của bạn</a></li>
                                <li><a href="#" class="hover:tw-text-[#c45500] hover:tw-underline">Lịch sử duyệt web</a></li>
                                <li v-if="authStore.isLoggedIn" class="tw-mt-2 tw-border-t tw-border-gray-100 tw-pt-2">
                                    <button @click="authStore.logout()" class="hover:tw-text-[#c45500] hover:tw-underline">Đăng xuất</button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <router-link
                :to="{ name: 'Login' }"
                class="tw-hidden tw-h-[50px] tw-cursor-pointer tw-flex-col tw-justify-center tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white lg:tw-flex"
            >
                <span class="tw-text-[12px] tw-leading-3 tw-text-white">Trả hàng</span>
                <span class="tw-flex tw-items-center tw-text-[14px] tw-font-bold tw-leading-4 tw-text-white">& Đơn hàng</span>
            </router-link>

            <a
                href="#"
                class="tw-flex tw-h-[50px] tw-cursor-pointer tw-items-end tw-rounded-sm tw-border tw-border-transparent tw-px-2 tw-py-1 hover:tw-border-white"
            >
                <div class="tw-relative tw-flex tw-items-end tw-pr-1">
                    <span
                        class="tw-absolute tw-left-[10px] tw-top-[-5px] tw-w-full tw-text-center tw-text-[16px] tw-font-bold tw-leading-none tw-text-[#f59e0b]"
                    >
                        0
                    </span>
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="tw-h-8 tw-w-8 tw-text-white"
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
                <span class="tw-mb-1 tw-ml-1 tw-text-[14px] tw-font-bold tw-text-white">Cart</span>
            </a>
        </div>

        <div class="tw-bg-[#131921] tw-px-2 tw-pb-2 md:tw-hidden">
            <form
                @submit.prevent="submitSearch"
                class="tw-flex tw-h-[40px] tw-overflow-hidden tw-rounded-md tw-bg-white focus-within:tw-ring-2 focus-within:tw-ring-[#f3a847]"
            >
                <input v-model="searchTerm" type="text" placeholder="Tìm kiếm sản phẩm..." class="tw-flex-grow tw-px-3 tw-text-black focus:tw-outline-none" />
                <button type="submit" class="tw-flex tw-w-[45px] tw-items-center tw-justify-center tw-bg-[#febd69]">
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
        </div>
    </div>
</template>
