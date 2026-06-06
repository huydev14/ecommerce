<template>
    <div class="client-wrapper tw-min-w-0 tw-overflow-x-hidden tw-font-sans tw-antialiased">
        <header class="client-header tw-min-w-0">
            <TheHeader />
        </header>

        <nav class="client-subnav tw-min-w-0">
            <TheSubNav />
        </nav>

        <main class="client-main tw-min-w-0">
            <router-view />
        </main>

        <footer class="client-footer tw-min-w-0">
            <TheFooter />
        </footer>

        <HrTourHome />
    </div>
</template>

<script setup>
import TheHeader from '../components/TheHeader.vue';
import TheSubNav from '../components/TheSubNav.vue';
import TheFooter from '../components/TheFooter.vue';
import HrTourHome from '../components/HrTourHome.vue';
</script>
