<script setup>
const footerSections = [
    {
        title: 'Get to Know Us',
        links: ['Careers', 'About WorkHub', 'Investor Relations', 'WorkHub Devices'],
    },
    {
        title: 'Make Money with Us',
        links: ['Sell products', 'Become an Affiliate', 'Advertise Your Products', 'Fulfillment by WorkHub'],
    },
    {
        title: 'Payment Products',
        links: ['Business Card', 'Shop with Points', 'Reload Balance', 'Installment Support'],
    },
    {
        title: 'Let Us Help You',
        links: ['Your Account', 'Your Orders', 'Shipping Rates', 'Returns & Replacements'],
    },
];
</script>

<template>
    <div class="amazon-footer">
        <a href="#" class="amazon-footer__back">Back to top</a>

        <div class="amazon-footer__main">
            <div class="amazon-footer__columns">
                <section v-for="section in footerSections" :key="section.title" class="amazon-footer__section">
                    <h3>{{ section.title }}</h3>
                    <a v-for="link in section.links" :key="link" href="#">{{ link }}</a>
                </section>
            </div>

            <div class="amazon-footer__brand">
                <router-link :to="{ name: 'Home' }" class="amazon-footer__logo">
                    <span>workhub</span>
                    <i></i>
                </router-link>

                <div class="amazon-footer__controls">
                    <button type="button">🌐 Tiếng Việt</button>
                    <button type="button">₫ VND</button>
                    <button type="button">Việt Nam</button>
                </div>
            </div>
        </div>

        <div class="amazon-footer__bottom">
            <div class="amazon-footer__legal">
                <a href="#">Conditions of Use</a>
                <a href="#">Privacy Notice</a>
                <a href="#">Consumer Health Data Privacy</a>
                <a href="#">Your Ads Privacy Choices</a>
            </div>
            <p>&copy; 2026 WorkHub - Ecommerce System. Amazon-style interface concept.</p>
        </div>
    </div>
</template>

<style scoped>
.amazon-footer {
    font-family: Arial, Helvetica, sans-serif;
    color: #fff;
}

.amazon-footer__back {
    display: grid;
    min-height: 48px;
    place-items: center;
    background: #37475a;
    color: #fff;
    font-size: 13px;
    line-height: 18px;
    text-decoration: none;
}

.amazon-footer__back:hover {
    background: #485769;
}

.amazon-footer__main {
    background: #232f3e;
}

.amazon-footer__columns {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 40px;
    max-width: 1120px;
    margin: 0 auto;
    padding: 38px 24px 34px;
}

.amazon-footer__section h3 {
    margin: 0 0 12px;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    line-height: 20px;
}

.amazon-footer__section a {
    display: block;
    width: fit-content;
    margin-bottom: 8px;
    color: #ddd;
    font-size: 14px;
    line-height: 20px;
    text-decoration: none;
}

.amazon-footer__section a:hover,
.amazon-footer__legal a:hover {
    text-decoration: underline;
}

.amazon-footer__brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 54px;
    min-height: 92px;
    border-top: 1px solid #3a4553;
    padding: 18px 24px;
}

.amazon-footer__logo {
    display: grid;
    width: 126px;
    color: #fff;
    text-decoration: none;
}

.amazon-footer__logo span {
    font-size: 28px;
    font-weight: 700;
    line-height: 30px;
    letter-spacing: -1px;
}

.amazon-footer__logo i {
    width: 72px;
    height: 8px;
    margin-left: 36px;
    border-bottom: 3px solid #ff9900;
    border-radius: 50%;
}

.amazon-footer__controls {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.amazon-footer__controls button {
    min-height: 34px;
    border: 1px solid #848688;
    border-radius: 3px;
    background: transparent;
    padding: 6px 12px;
    color: #ddd;
    font-size: 13px;
    line-height: 18px;
}

.amazon-footer__bottom {
    background: #131a22;
    padding: 22px 24px;
    color: #ddd;
    text-align: center;
}

.amazon-footer__legal {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 14px;
    margin-bottom: 8px;
}

.amazon-footer__legal a,
.amazon-footer__bottom p {
    color: #ddd;
    font-size: 12px;
    line-height: 18px;
    text-decoration: none;
}

.amazon-footer__bottom p {
    margin: 0;
}

@media (max-width: 900px) {
    .amazon-footer__columns {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .amazon-footer__brand {
        align-items: center;
        flex-direction: column;
        gap: 18px;
    }
}

@media (max-width: 560px) {
    .amazon-footer__columns {
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .amazon-footer__controls {
        justify-content: center;
    }
}
</style>
