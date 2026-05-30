<template>
    <footer class="tw-mt-7 tw-flex tw-w-full tw-flex-col tw-items-center tw-border-t tw-border-gray-300 tw-bg-[#fbfbfb] tw-py-7">
        <div class="tw-mb-2.5 tw-flex tw-flex-wrap tw-justify-center tw-gap-5">
            <a href="#" class="tw-text-[11px] tw-text-[#0066c0] tw-no-underline hover:tw-underline"> Điều khoản sử dụng </a>
            <a href="#" class="tw-text-[11px] tw-text-[#0066c0] tw-no-underline hover:tw-underline"> Thông báo Bảo mật </a>
            <a href="#" class="tw-text-[11px] tw-text-[#0066c0] tw-no-underline hover:tw-underline"> Trợ giúp </a>
        </div>

        <p class="tw-m-0 tw-text-[11px] tw-text-[#555]">
            © {{ currentYear }}, {{ APP_CONFIG.appName }}.com, Inc. hoặc các chi nhánh của nó
        </p>
    </footer>
</template>

<script setup>
import { APP_CONFIG } from '@/config';

const currentYear = new Date().getFullYear();
</script>
