<script setup>
import { APP_CONFIG } from '@/config';

const currentYear = new Date().getFullYear();

const footerSections = [
    {
        title: 'Let Us Help You',
        links: [
            { label: 'Your Account', to: { name: 'MyAccount' } },
            { label: 'Your Orders', to: { name: 'MyAccountOrders' } },
            { label: 'Cart', to: { name: 'Cart' } },
            { label: 'Shipping Rates', href: '#' },
            { label: 'Returns & Replacements', href: '#' },
        ],
    },
];
</script>

<template>
    <div class="client-footer">
        <a href="#" class="footer__back">Back to top</a>

        <div class="footer__main">
            <div class="footer__content">
                <div class="footer__links">
                    <section v-for="section in footerSections" :key="section.title" class="footer__section">
                        <h3>{{ section.title }}</h3>
                        <template v-for="link in section.links" :key="link.label">
                            <router-link v-if="link.to" :to="link.to">{{ link.label }}</router-link>
                            <a v-else :href="link.href">{{ link.label }}</a>
                        </template>
                    </section>
                </div>

                <div class="footer__brand">
                    <div class="footer__brand-mark">
                        <router-link :to="{ name: 'Home' }" class="footer__logo">
                            <span>{{ APP_CONFIG.appName }}</span>
                            <i></i>
                        </router-link>
                    </div>

                    <div class="footer__controls">
                        <button type="button">Tiếng Việt</button>
                        <button type="button">VND</button>
                        <button type="button">Việt Nam</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer__bottom">
            <p>&copy; {{ currentYear }} {{ APP_CONFIG.appName }} - Ecommerce System.</p>
        </div>
    </div>
</template>

<style scoped>
.client-footer {
    width: 100vw;
    margin-inline: calc(50% - 50vw);
    overflow: hidden;
    font-family: Arial, Helvetica, sans-serif;
    color: #fff;
    background: #131a22;
}

.footer__back {
    display: grid;
    min-height: 46px;
    place-items: center;
    background: #37475a;
    color: #fff;
    font-size: 13px;
    line-height: 18px;
    text-decoration: none;
}

.footer__back:hover {
    background: #485769;
}

.footer__main {
    background: linear-gradient(180deg, #243447 0%, #1f2c3a 100%);
}

.footer__content {
    display: grid;
    grid-template-columns: minmax(220px, 0.9fr) minmax(360px, 1.1fr);
    align-items: center;
    gap: 64px;
    width: min(100%, 1180px);
    margin: 0 auto;
    padding: 36px 24px 38px;
}

.footer__links {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 28px 56px;
}

.footer__section {
    min-width: 210px;
}

.footer__section h3 {
    margin: 0 0 14px;
    color: #fff;
    font-size: 17px;
    font-weight: 700;
    line-height: 22px;
}

.footer__section a {
    display: block;
    width: fit-content;
    margin-bottom: 9px;
    color: #e5edf7;
    font-size: 14px;
    line-height: 20px;
    text-decoration: none;
    transition:
        color 0.18s ease,
        transform 0.18s ease;
}

.footer__section a:hover {
    color: #fff;
    text-decoration: underline;
}

.footer__section a:hover {
    transform: translateX(2px);
}

.footer__brand {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    min-height: 112px;
    padding: 18px 0 18px 56px;
    border-left: 1px solid rgba(148, 163, 184, 0.22);
}

.footer__brand-mark {
    display: grid;
    place-items: center;
}

.footer__logo {
    display: grid;
    width: fit-content;
    color: #fff;
    text-decoration: none;
}

.footer__logo span {
    font-size: 34px;
    font-weight: 700;
    line-height: 36px;
    letter-spacing: 0;
}

.footer__logo i {
    width: 86px;
    height: 8px;
    margin-left: auto;
    margin-right: 8px;
    border-bottom: 3px solid #ff9900;
    border-radius: 50%;
}

.footer__controls {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}

.footer__controls button {
    min-height: 38px;
    border: 1px solid rgba(203, 213, 225, 0.38);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.04);
    padding: 7px 14px;
    color: #f1f5f9;
    font-size: 13px;
    line-height: 18px;
    transition:
        border-color 0.18s ease,
        background-color 0.18s ease;
}

.footer__controls button:hover {
    border-color: rgba(255, 255, 255, 0.72);
    background: rgba(255, 255, 255, 0.1);
}

.footer__bottom {
    background: #131a22;
    padding: 20px 24px 24px;
    color: #d5dde7;
    text-align: center;
}

.footer__bottom p {
    color: #d5dde7;
    font-size: 12px;
    line-height: 18px;
    text-decoration: none;
}

.footer__bottom p {
    margin: 0;
}

@media (max-width: 900px) {
    .footer__content {
        grid-template-columns: 1fr;
        gap: 26px;
        padding-block: 32px;
    }

    .footer__links {
        justify-content: center;
    }

    .footer__brand {
        flex-direction: column;
        justify-content: center;
        gap: 18px;
        min-height: auto;
        padding: 26px 0 0;
        border-top: 1px solid rgba(148, 163, 184, 0.22);
        border-left: 0;
    }

    .footer__controls {
        justify-content: center;
    }
}

@media (max-width: 560px) {
    .footer__content {
        padding: 28px 18px 30px;
    }

    .footer__section {
        text-align: center;
    }

    .footer__section a {
        margin-right: auto;
        margin-left: auto;
    }

    .footer__logo span {
        font-size: 30px;
        line-height: 32px;
    }

    .footer__controls button {
        flex: 1 1 auto;
    }
}
</style>
