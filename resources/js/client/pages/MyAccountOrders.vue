<script setup>
const orderTabs = ['Orders', 'Buy Again', 'Not Yet Shipped'];
</script>

<template>
    <section class="orders-page" aria-labelledby="orders-title">
        <div class="orders-page__inner">
            <header class="orders-header">
                <h1 id="orders-title">My Orders</h1>

                <form class="orders-search" role="search">
                    <label class="orders-search__box">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" placeholder="Search all orders" />
                    </label>

                    <button type="submit">Search Orders</button>
                </form>
            </header>

            <nav class="orders-tabs" aria-label="Order views">
                <a v-for="tab in orderTabs" :key="tab" href="#" :class="{ 'is-active': tab === 'Orders' }">
                    {{ tab }}
                </a>
            </nav>

            <div class="orders-filter">
                <span><strong>0 orders</strong> placed in</span>
                <button type="button">
                    past 3 months
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>
            </div>

            <div class="orders-empty" role="status">
                Looks like you haven't placed an order in the last 3 months.
                <a href="#">View orders in 2026</a>
            </div>
        </div>

    </section>
</template>

<style scoped>
.orders-page {
    min-height: 100vh;
    background: #ffffff;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.orders-page__inner {
    width: min(100%, 1320px);
    margin: 0 auto;
    padding: 18px 28px 92px;
}

.orders-breadcrumb {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 22px;
    font-size: 14px;
    line-height: 20px;
}

.orders-breadcrumb a,
.orders-breadcrumb span:last-child,
.orders-empty a,
.orders-history a {
    color: #007185;
    text-decoration: none;
}

.orders-breadcrumb span:last-child {
    color: #c45500;
    font-weight: 700;
}

.orders-breadcrumb a:hover,
.orders-empty a:hover,
.orders-history a:hover,
.orders-tabs a:hover {
    color: #c45500;
    text-decoration: underline;
}

.orders-header {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) minmax(360px, 642px);
    align-items: center;
    gap: 28px;
}

.orders-header h1 {
    margin: 0;
    color: #0f1111;
    font-size: 36px;
    font-weight: 500;
    line-height: 44px;
    letter-spacing: 0;
}

.orders-search {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
}

.orders-search__box {
    display: flex;
    align-items: center;
    min-width: 0;
    height: 44px;
    border: 1px solid #888c8c;
    border-radius: 8px;
    background: #ffffff;
    padding: 0 14px;
    color: #0f1111;
}

.orders-search__box i {
    margin-right: 10px;
    font-size: 20px;
}

.orders-search__box input {
    min-width: 0;
    flex: 1;
    border: 0;
    color: #0f1111;
    font-size: 18px;
    line-height: 24px;
    outline: none;
}

.orders-search__box input::placeholder {
    color: #6f7373;
    opacity: 1;
}

.orders-search button {
    min-height: 44px;
    border: 0;
    border-radius: 22px;
    background: #303333;
    padding: 0 22px;
    color: #ffffff;
    font-size: 18px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
}

.orders-search button:hover {
    background: #1f2323;
}

.orders-tabs {
    display: flex;
    align-items: flex-end;
    gap: 26px;
    margin-top: 28px;
    border-bottom: 1px solid #d5d9d9;
    overflow-x: auto;
}

.orders-tabs a {
    position: relative;
    flex: 0 0 auto;
    padding: 0 2px 10px;
    color: #007185;
    font-size: 18px;
    font-weight: 600;
    line-height: 24px;
    text-decoration: none;
}

.orders-tabs a.is-active {
    color: #0f1111;
}

.orders-tabs a.is-active::after {
    position: absolute;
    right: 0;
    bottom: -1px;
    left: 0;
    height: 3px;
    border-radius: 3px 3px 0 0;
    background: #f08804;
    content: '';
}

.orders-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 22px;
    font-size: 18px;
    line-height: 24px;
}

.orders-filter button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    border: 1px solid #888c8c;
    border-radius: 8px;
    background: #ffffff;
    padding: 0 14px;
    color: #0f1111;
    font-size: 18px;
    cursor: pointer;
}

.orders-filter button:hover {
    background: #f7fafa;
}

.orders-empty {
    margin-top: 62px;
    text-align: center;
    font-size: 18px;
    font-weight: 700;
    line-height: 26px;
}

.orders-empty a {
    font-weight: 500;
}

.orders-history {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 180px;
    align-items: center;
    gap: 28px;
    min-height: 156px;
    border: 1px solid #d5d9d9;
    border-radius: 4px;
    background: #ffffff;
    padding: 28px 44px;
}

.orders-history p {
    margin: 0;
    color: #0f1111;
    font-size: 17px;
    font-style: italic;
    font-weight: 700;
    line-height: 24px;
}

.orders-history a {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    line-height: 16px;
}

@media (max-width: 900px) {
    .orders-page__inner {
        padding: 18px 18px 64px;
    }

    .orders-header {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .orders-search {
        grid-template-columns: 1fr;
    }

    .orders-tabs {
        gap: 22px;
    }

    .orders-history {
        grid-template-columns: 1fr;
        padding: 24px 20px;
    }
}

@media (max-width: 640px) {
    .orders-page__inner {
        padding: 16px 14px 46px;
    }

    .orders-header h1 {
        font-size: 30px;
        line-height: 36px;
    }

    .orders-search__box,
    .orders-search button,
    .orders-filter button {
        min-height: 40px;
    }

    .orders-search__box input,
    .orders-search button,
    .orders-tabs a,
    .orders-filter,
    .orders-filter button,
    .orders-empty {
        font-size: 16px;
    }

    .orders-filter {
        align-items: flex-start;
        flex-direction: column;
    }

    .orders-empty {
        margin-top: 42px;
        text-align: left;
    }
}
</style>
