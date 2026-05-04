<template>
    <div class="client-wrapper tw-font-sans tw-antialiased">
        <header class="client-header">
            <TheHeader />
        </header>

        <nav class="client-subnav">
            <TheSubNav />
        </nav>

        <main class="client-main">
            <router-view v-slot="{ Component }">
                <transition name="fade-page" mode="out-in">
                    <component :is="Component" />
                </transition>
            </router-view>
        </main>

        <footer class="client-footer">
            <TheFooter />
        </footer>
    </div>
</template>

<script setup>
import TheHeader from '../components/TheHeader.vue';
import TheSubNav from '../components/TheSubNav.vue';
import TheFooter from '../components/TheFooter.vue';
</script>
