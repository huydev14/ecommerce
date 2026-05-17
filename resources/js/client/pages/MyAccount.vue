<script setup>
const accountCards = [
    {
        title: 'My orders',
        description: 'Track, return, cancel an order, download invoice or buy again',
        icon: 'fa-solid fa-box-open',
        accent: 'orders',
    },
    {
        title: 'Login & security',
        description: 'Edit login, name, email, mobile number, and password',
        icon: 'fa-solid fa-shield-halved',
        accent: 'security',
    },
    {
        title: 'Your addresses',
        description: 'Edit, remove, or set default address for fast checkout',
        icon: 'fa-solid fa-address-book',
        accent: 'addresses',
    },
    {
        title: 'Your payments',
        description: 'View transactions, manage payment methods and settings',
        icon: 'fa-solid fa-credit-card',
        accent: 'payments',
    },
];
</script>

<template>
    <section class="account-page" aria-labelledby="account-title">
        <div class="account-page__inner">
            <h1 id="account-title">Your Account</h1>

            <div class="account-grid" aria-label="Account shortcuts">
                <a v-for="card in accountCards" :key="card.title" href="#" class="account-card">
                    <span class="account-card__icon" :class="`is-${card.accent}`" aria-hidden="true">
                        <i :class="card.icon"></i>
                    </span>

                    <span class="account-card__content">
                        <span class="account-card__title">{{ card.title }}</span>
                        <span class="account-card__description">{{ card.description }}</span>
                    </span>
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
.account-page {
    min-height: 100vh;
    background: #ffffff;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.account-page__inner {
    width: min(100%, 1220px);
    margin: 0 auto;
    padding: 34px 24px 64px;
}

.account-page h1 {
    margin: 0 0 24px;
    color: #0f1111;
    font-size: 32px;
    font-weight: 700;
    line-height: 40px;
    letter-spacing: 0;
}

.account-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.account-card {
    display: grid;
    grid-template-columns: 92px minmax(0, 1fr);
    align-items: center;
    min-height: 146px;
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background: #ffffff;
    padding: 24px 28px;
    color: #0f1111;
    text-decoration: none;
    transition:
        border-color 0.16s ease,
        box-shadow 0.16s ease,
        background 0.16s ease;
}

.account-card:hover {
    border-color: #c7cccc;
    background: #f7fafa;
    box-shadow: 0 2px 5px rgba(15, 17, 17, 0.14);
}

.account-card:focus-visible {
    outline: 3px solid #007185;
    outline-offset: 2px;
}

.account-card__icon {
    display: grid;
    width: 72px;
    height: 72px;
    place-items: center;
    border-radius: 50%;
    background: #c7ecef;
    color: #008296;
    font-size: 34px;
}

.account-card__icon.is-orders {
    background: #c8edf0;
    color: #007185;
}

.account-card__icon.is-security {
    background: #c9edf0;
    color: #017f88;
}

.account-card__icon.is-addresses {
    background: #c8edf0;
    color: #008296;
}

.account-card__icon.is-payments {
    background: #c8edf0;
    color: #007185;
}

.account-card__content {
    display: grid;
    gap: 8px;
    min-width: 0;
}

.account-card__title {
    color: #0f1111;
    font-size: 24px;
    font-weight: 700;
    line-height: 30px;
}

.account-card__description {
    color: #565959;
    font-size: 17px;
    font-weight: 600;
    line-height: 24px;
}

@media (max-width: 900px) {
    .account-page__inner {
        padding: 28px 18px 48px;
    }

    .account-grid {
        gap: 14px;
    }

    .account-card {
        grid-template-columns: 74px minmax(0, 1fr);
        min-height: 132px;
        padding: 20px;
    }

    .account-card__icon {
        width: 58px;
        height: 58px;
        font-size: 27px;
    }

    .account-card__title {
        font-size: 20px;
        line-height: 26px;
    }

    .account-card__description {
        font-size: 15px;
        line-height: 21px;
    }
}

@media (max-width: 640px) {
    .account-page__inner {
        padding: 22px 14px 40px;
    }

    .account-page h1 {
        margin-bottom: 18px;
        font-size: 28px;
        line-height: 34px;
    }

    .account-grid {
        grid-template-columns: 1fr;
    }

    .account-card {
        grid-template-columns: 64px minmax(0, 1fr);
        min-height: 118px;
        padding: 18px 16px;
    }

    .account-card__icon {
        width: 52px;
        height: 52px;
        font-size: 23px;
    }
}
</style>
