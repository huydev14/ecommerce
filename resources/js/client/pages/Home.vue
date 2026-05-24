<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../services/api';

const categoryCards = [
    {
        title: 'Trang trí nhà cửa',
        link: 'Khám phá thêm',
        items: [
            { icon: '🛋', label: 'Sofa & phòng khách' },
            { icon: '💡', label: 'Đèn trang trí' },
            { icon: '🪴', label: 'Cây & kệ' },
            { icon: '🖼', label: 'Khung ảnh' },
        ],
    },
    {
        title: 'Thiết bị điện tử',
        link: 'Mua ngay',
        items: [
            { icon: '📱', label: 'Điện thoại' },
            { icon: '💻', label: 'Laptop' },
            { icon: '🎧', label: 'Âm thanh' },
            { icon: '⌚', label: 'Wearables' },
        ],
    },
    {
        title: 'Ưu đãi mỗi ngày',
        link: 'Xem tất cả deals',
        items: [
            { icon: '🔥', label: 'Flash sale' },
            { icon: '🎁', label: 'Voucher' },
            { icon: '🚚', label: 'Freeship' },
            { icon: '⭐', label: 'Top rated' },
        ],
    },
];

const bestSellers = [
    { icon: '💻', name: 'Laptop WorkPro 14 inch', discount: '-18%', rating: '★★★★★ 2,143', price: '₫18.990.000' },
    { icon: '🎧', name: 'Tai nghe chống ồn', discount: '-25%', rating: '★★★★☆ 871', price: '₫1.290.000' },
    { icon: '🪑', name: 'Ghế công thái học', discount: '-12%', rating: '★★★★★ 654', price: '₫2.690.000' },
    { icon: '📷', name: 'Camera văn phòng', discount: '-10%', rating: '★★★★☆ 342', price: '₫990.000' },
    { icon: '⌚', name: 'Smartwatch Active', discount: '-30%', rating: '★★★★☆ 1,120', price: '₫1.790.000' },
    { icon: '🖨', name: 'Máy in laser Wi-Fi', discount: '-8%', rating: '★★★★★ 431', price: '₫3.190.000' },
    { icon: '🧴', name: 'Combo chăm sóc nhà', discount: '-22%', rating: '★★★★☆ 221', price: '₫259.000' },
    { icon: '💡', name: 'Đèn bàn LED', discount: '-15%', rating: '★★★★★ 588', price: '₫399.000' },
];

const featuredProducts = [
    { icon: '💻', name: 'WorkPro 14 i7 2026', price: '₫18.990.000', tag: 'Best choice' },
    { icon: '🎧', name: 'Noise Cancel Pro', price: '₫1.290.000', tag: 'Limited deal' },
    { icon: '🪑', name: 'ErgoChair Flex', price: '₫2.690.000', tag: 'Top rated' },
    { icon: '⌚', name: 'Smartwatch Active', price: '₫1.790.000', tag: 'Prime deal' },
    { icon: '🖨', name: 'Laser Wi-Fi Printer', price: '₫3.190.000', tag: 'Office pick' },
    { icon: '📷', name: 'OfficeCam 2K', price: '₫990.000', tag: 'Hot' },
    { icon: '💡', name: 'LED Desk Lamp', price: '₫399.000', tag: 'Save 15%' },
    { icon: '🧴', name: 'Home Care Combo', price: '₫259.000', tag: 'Daily deal' },
    { icon: '⌨', name: 'Mechanical Keyboard', price: '₫1.090.000', tag: 'New' },
    { icon: '🖱', name: 'Wireless Mouse Pro', price: '₫459.000', tag: 'WorkHub pick' },
];

const newProducts = ref([]);

const recommendationRows = [
    {
        title: 'Tiếp tục mua sắm theo danh mục',
        link: 'Xem thêm',
        products: [
            { icon: '🧳', name: 'Du lịch & vali' },
            { icon: '👕', name: 'Thời trang nam' },
            { icon: '🍳', name: 'Nhà bếp' },
            { icon: '📚', name: 'Sách & học tập' },
            { icon: '🧸', name: 'Đồ chơi' },
            { icon: '🛠', name: 'Dụng cụ' },
        ],
    },
    {
        title: 'Gợi ý cho văn phòng của bạn',
        link: 'Xem tất cả',
        products: [
            { icon: '🖥', name: 'Màn hình' },
            { icon: '⌨', name: 'Bàn phím' },
            { icon: '🖱', name: 'Chuột' },
            { icon: '📓', name: 'Sổ tay' },
            { icon: '☕', name: 'Ly giữ nhiệt' },
            { icon: '🗄', name: 'Tủ tài liệu' },
        ],
    },
];

const featuredSlider = ref(null);

const normalizeProducts = (payload) => {
    if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
};

const formatPrice = (price) => {
    const amount = Number(price);

    if (!Number.isFinite(amount) || amount <= 0) {
        return 'Liên hệ';
    }

    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(amount);
};

const newProductRows = computed(() => {
    const chunkSize = 4;
    const rows = [];

    for (let index = 0; index < newProducts.value.length; index += chunkSize) {
        rows.push(newProducts.value.slice(index, index + chunkSize));
    }

    return rows;
});

const fetchNewArrivals = async () => {
    try {
        const response = await api.get('/products/new-arrivals');

        if (response.data?.success) {
            newProducts.value = normalizeProducts(response.data);
        }
    } catch (error) {
        console.error('Lỗi khi lấy sản phẩm mới:', error);
    }
};

const scrollFeatured = (direction) => {
    featuredSlider.value?.scrollBy({
        left: direction * 360,
        behavior: 'smooth',
    });
};

onMounted(() => {
    fetchNewArrivals();
});
</script>

<template>
    <div class="amazon-home">
        <section class="home-hero" aria-label="WorkHub deals">
            <div class="home-hero__copy">
                <h1>Mua sắm mọi thứ cho nhà và công việc</h1>
                <p>Deals mỗi ngày, giao nhanh, trải nghiệm WorkHub theo phong cách marketplace giống Amazon.</p>
                <a href="#" class="amazon-pill-button">Xem ưu đãi hôm nay</a>
            </div>
        </section>

        <div class="amazon-home__content">
            <section class="amazon-card-grid" aria-label="Danh mục nổi bật">
                <article v-for="category in categoryCards" :key="category.title" class="amazon-card">
                    <h2>{{ category.title }}</h2>

                    <div class="amazon-card__tiles">
                        <a v-for="item in category.items" :key="item.label" href="#" class="amazon-tile">
                            <span class="amazon-tile__visual">{{ item.icon }}</span>
                            <span>{{ item.label }}</span>
                        </a>
                    </div>

                    <a href="#" class="amazon-link">{{ category.link }}</a>
                </article>

                <aside class="amazon-signin-card" aria-label="Đăng nhập">
                    <div class="amazon-card amazon-card--compact">
                        <h2>Đăng nhập để có trải nghiệm tốt nhất</h2>
                        <router-link :to="{ name: 'Login'}" class="amazon-signin-card__button a-button-primary">
                            Đăng nhập
                        </router-link>

                        <router-link :to="{ name: 'Register'}" class="amazon-link">Tạo tài khoản mới </router-link>

                    </div>

                    <div class="amazon-ad-card">
                        <span>SALE</span>
                    </div>
                </aside>
            </section>

            <section class="amazon-rail" aria-label="Sản phẩm bán chạy nhất">
                <div class="amazon-section-heading">
                    <h2>Sản phẩm bán chạy nhất</h2>
                    <a href="#" class="amazon-link">Xem thêm</a>
                </div>

                <div class="amazon-product-row">
                    <article v-for="product in bestSellers" :key="product.name" class="amazon-product">
                        <a href="#" class="amazon-product__image">{{ product.icon }}</a>
                        <div class="amazon-product__deal">
                            <span>{{ product.discount }}</span>
                            <b>Deal</b>
                        </div>
                        <a href="#" class="amazon-product__name">{{ product.name }}</a>
                        <div class="amazon-product__rating">{{ product.rating }}</div>
                        <div class="amazon-product__price">{{ product.price }}</div>
                    </article>
                </div>
            </section>

            <section class="amazon-rail amazon-featured-products" aria-label="Featured products">
                <div class="amazon-section-heading">
                    <div>
                        <span class="amazon-featured-products__eyebrow">Featured products</span>
                        <h2>Sản phẩm nổi bật</h2>
                    </div>

                    <div class="amazon-slider-controls" aria-label="Điều hướng sản phẩm nổi bật">
                        <button type="button" aria-label="Cuộn sang trái" @click="scrollFeatured(-1)">‹</button>
                        <button type="button" aria-label="Cuộn sang phải" @click="scrollFeatured(1)">›</button>
                    </div>
                </div>

                <div ref="featuredSlider" class="amazon-featured-slider">
                    <article v-for="product in featuredProducts" :key="product.name" class="amazon-featured-card">
                        <span class="amazon-featured-card__tag">{{ product.tag }}</span>
                        <a href="#" class="amazon-featured-card__image">{{ product.icon }}</a>
                        <a href="#" class="amazon-featured-card__name">{{ product.name }}</a>
                        <span class="amazon-featured-card__price">{{ product.price }}</span>
                    </article>
                </div>
            </section>

            <section class="amazon-rail amazon-new-products" aria-label="New products">
                <div class="amazon-section-heading">
                    <h2>Sản phẩm mới</h2>
                    <a href="#" class="amazon-link">Xem hàng mới về</a>
                </div>

                <div class="amazon-new-products__rows">
                    <div v-for="(row, rowIndex) in newProductRows" :key="rowIndex" class="amazon-new-products__row">
                        <article v-for="product in row" :key="product.id || product.slug || product.name" class="amazon-new-product">
                            <a href="#" class="amazon-new-product__image">
                                <img :src="product.thumbnail" :alt="product.name" loading="lazy" />
                            </a>
                            <div>
                                <a href="#" class="amazon-new-product__name">{{ product.name }}</a>
                                <span>{{ formatPrice(product.price) }}</span>
                            </div>
                        </article>
                    </div>

                    <p v-if="newProductRows.length === 0" class="amazon-new-products__empty">Chưa có sản phẩm mới.</p>
                </div>
            </section>

            <section class="amazon-feature-band" aria-label="Prime delivery">
                <div>
                    <span class="amazon-feature-band__eyebrow">Prime giao nhanh</span>
                    <h2>Giao hàng ngày mai cho hàng nghìn sản phẩm</h2>
                    <p>Ưu tiên các sản phẩm còn hàng, freeship và đổi trả dễ dàng ngay trong tài khoản WorkHub.</p>
                </div>
                <a href="#" class="amazon-outline-button">Khám phá Prime</a>
            </section>

            <section v-for="row in recommendationRows" :key="row.title" class="amazon-rail amazon-rail--compact" :aria-label="row.title">
                <div class="amazon-section-heading">
                    <h2>{{ row.title }}</h2>
                    <a href="#" class="amazon-link">{{ row.link }}</a>
                </div>

                <div class="amazon-category-row">
                    <a v-for="product in row.products" :key="product.name" href="#" class="amazon-category-product">
                        <span>{{ product.icon }}</span>
                        <b>{{ product.name }}</b>
                    </a>
                </div>
            </section>
        </div>
    </div>
</template>

<style scoped>
.amazon-home {
    min-height: 100%;
    background: #e3e6e6;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.home-hero {
    position: relative;
    min-height: 410px;
    overflow: hidden;
    background: linear-gradient(100deg, #f5d39e 0%, #e9eef2 45%, #b7d8e8 100%);
    padding: 0 50px;
    display: flex;
    justify-content: center;
}

.home-hero::after {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    height: 210px;
    background: linear-gradient(to bottom, rgba(227, 230, 230, 0), #e3e6e6);
    content: '';
}

.home-hero__copy {
    max-width: 1500px;
    width: 100%;
    position: relative;
    z-index: 1;
    padding: 58px 20px;
}

.home-hero__copy h1 {
    margin: 0 0 14px;
    font-size: clamp(30px, 4vw, 42px);
    font-weight: 700;
    line-height: 1.14;
    letter-spacing: 0;
}

.home-hero__copy p {
    margin: 0 0 20px;
    color: #27323a;
    font-size: 18px;
    line-height: 26px;
}

.amazon-pill-button,
.amazon-signin-card__button,
.amazon-outline-button,
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

.amazon-pill-button,
.amazon-signin-card__button,
.a-button-primary {
    background: #f0c14b;
    border-color: #a88734 #9c7e31 #846a29;
    color: #111;
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.4) inset;
}

.amazon-pill-button:hover,
.amazon-signin-card__button:hover,
.a-button-primary:hover {
    background: #f4d078;
}

.amazon-outline-button {
    border: 1px solid #d5d9d9;
    background: #fff;
    color: #0f1111;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.12);
}

.home-hero__products {
    position: absolute;
    top: 42px;
    right: 6%;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(3, 150px);
    gap: 18px;
}

.home-hero__product {
    display: grid;
    height: 220px;
    place-items: center;
    background: rgba(255, 255, 255, 0.86);
    box-shadow: 0 6px 18px rgba(15, 17, 17, 0.12);
    font-size: 62px;
}

.amazon-home__content {
    position: relative;
    z-index: 2;
    max-width: 1500px;
    margin: -8% auto 0;
    padding: 0 20px 38px;
}

.amazon-card-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
}

.amazon-card,
.amazon-rail,
.amazon-feature-band {
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.08);
}

.amazon-card {
    display: flex;
    min-height: 420px;
    flex-direction: column;
    padding: 20px;
}

.amazon-card--compact {
    min-height: 150px;
}

.amazon-card h2,
.amazon-section-heading h2,
.amazon-feature-band h2 {
    margin: 0;
    color: #0f1111;
    font-size: 21px;
    font-weight: 700;
    line-height: 27px;
    letter-spacing: 0;
}

.amazon-card__tiles {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 16px;
}

.amazon-tile {
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

.amazon-tile__visual {
    display: grid;
    flex: 1;
    place-items: center;
    margin-bottom: 8px;
    background: #fff;
    font-size: 42px;
}

.amazon-link {
    display: inline-flex;
    width: fit-content;
    margin-top: auto;
    color: #007185;
    font-size: 13px;
    line-height: 18px;
    text-decoration: none;
}

.amazon-link:hover,
.amazon-product__name:hover,
.amazon-category-product:hover b {
    color: #c7511f;
    text-decoration: underline;
}

.amazon-signin-card {
    display: grid;
    grid-template-rows: auto 1fr;
    gap: 20px;
}

.amazon-signin-card__button {
    width: 100%;
    margin: 10px 0 14px;
}

.amazon-ad-card {
    display: grid;
    min-height: 250px;
    place-items: center;
    background: #f7fafa;
    color: #566;
    font-size: 54px;
    letter-spacing: 0;
}

.amazon-rail {
    margin-top: 20px;
    padding: 18px 20px;
}

.amazon-section-heading {
    display: flex;
    align-items: baseline;
    gap: 14px;
    justify-content: space-between;
    margin-bottom: 14px;
}

.amazon-section-heading .amazon-link {
    margin-top: 0;
}

.amazon-product-row {
    display: grid;
    grid-template-columns: repeat(8, minmax(140px, 1fr));
    gap: 18px;
}

.amazon-product {
    min-width: 0;
}

.amazon-product__image {
    display: grid;
    height: 150px;
    place-items: center;
    margin-bottom: 10px;
    background: #f7fafa;
    font-size: 50px;
    text-decoration: none;
}

.amazon-product__deal {
    display: flex;
    align-items: center;
    gap: 6px;
    min-height: 24px;
    color: #cc0c39;
    font-size: 12px;
    font-weight: 700;
}

.amazon-product__deal span {
    display: inline-flex;
    align-items: center;
    min-height: 22px;
    padding: 3px 6px;
    background: #cc0c39;
    color: #fff;
}

.amazon-product__name {
    display: block;
    margin: 8px 0 4px;
    color: #0f1111;
    font-size: 14px;
    line-height: 18px;
    text-decoration: none;
}

.amazon-product__rating {
    color: #ffa41c;
    font-size: 13px;
    line-height: 18px;
}

.amazon-product__price {
    margin-top: 4px;
    font-size: 20px;
    line-height: 24px;
}

.amazon-featured-products__eyebrow {
    display: block;
    margin-bottom: 3px;
    color: #cc0c39;
    font-size: 13px;
    font-weight: 700;
    line-height: 18px;
}

.amazon-slider-controls {
    display: flex;
    flex: none;
    gap: 8px;
}

.amazon-slider-controls button {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border: 1px solid #d5d9d9;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.12);
    color: #0f1111;
    cursor: pointer;
    font-size: 28px;
    line-height: 1;
}

.amazon-slider-controls button:hover {
    background: #f7fafa;
}

.amazon-featured-slider {
    display: grid;
    grid-auto-columns: 220px;
    grid-auto-flow: column;
    gap: 16px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scroll-snap-type: x proximity;
    padding: 2px 2px 12px;
}

.amazon-featured-slider::-webkit-scrollbar {
    height: 8px;
}

.amazon-featured-slider::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: #c7c7c7;
}

.amazon-featured-card {
    scroll-snap-align: start;
    min-height: 300px;
    border: 1px solid #e3e6e6;
    background: #fff;
    padding: 14px;
}

.amazon-featured-card__tag {
    display: inline-flex;
    min-height: 22px;
    align-items: center;
    padding: 3px 7px;
    background: #cc0c39;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
}

.amazon-featured-card__image {
    display: grid;
    height: 156px;
    place-items: center;
    margin: 12px 0;
    background: #f7fafa;
    font-size: 54px;
    text-decoration: none;
}

.amazon-featured-card__name,
.amazon-new-product__name {
    display: block;
    color: #0f1111;
    font-size: 14px;
    line-height: 19px;
    text-decoration: none;
}

.amazon-featured-card__name:hover,
.amazon-new-product__name:hover {
    color: #c7511f;
    text-decoration: underline;
}

.amazon-featured-card__price {
    display: block;
    margin-top: 8px;
    font-size: 20px;
    line-height: 24px;
}

.amazon-new-products__rows {
    display: grid;
    gap: 12px;
}

.amazon-new-products__row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.amazon-new-product {
    display: grid;
    grid-template-columns: 92px 1fr;
    gap: 12px;
    min-width: 0;
    align-items: center;
    border: 1px solid #e3e6e6;
    background: #fff;
    padding: 10px;
}

.amazon-new-product__image {
    display: grid;
    place-items: center;
    background: #f7fafa;
    font-size: 34px;
    text-decoration: none;

    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
}

.amazon-new-product__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.amazon-new-product span {
    display: block;
    margin-top: 6px;
    font-size: 16px;
    line-height: 20px;
}

.amazon-new-products__empty {
    margin: 0;
    color: #566;
    font-size: 14px;
    line-height: 20px;
}

.amazon-feature-band {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-top: 20px;
    padding: 24px 28px;
    border-top: 4px solid #ff9900;
}

.amazon-feature-band__eyebrow {
    display: block;
    margin-bottom: 6px;
    color: #007185;
    font-size: 13px;
    font-weight: 700;
}

.amazon-feature-band p {
    max-width: 720px;
    margin: 8px 0 0;
    color: #27323a;
    font-size: 15px;
    line-height: 22px;
}

.amazon-outline-button {
    flex: none;
    border: 1px solid #d5d9d9;
    background: #fff;
    color: #0f1111;
    box-shadow: 0 1px 2px rgba(15, 17, 17, 0.12);
}

.amazon-rail--compact {
    padding-bottom: 20px;
}

.amazon-category-row {
    display: grid;
    grid-template-columns: repeat(6, minmax(140px, 1fr));
    gap: 16px;
}

.amazon-category-product {
    display: grid;
    min-height: 140px;
    place-items: center;
    padding: 16px;
    background: #f7fafa;
    color: #0f1111;
    text-align: center;
    text-decoration: none;
}

.amazon-category-product span {
    font-size: 44px;
}

.amazon-category-product b {
    margin-top: 8px;
    font-size: 14px;
    line-height: 18px;
}

@media (max-width: 1280px) {
    .home-hero__products {
        right: 4%;
        grid-template-columns: repeat(3, 128px);
    }

    .amazon-card-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .amazon-product-row {
        grid-template-columns: repeat(4, minmax(150px, 1fr));
    }

    .amazon-category-row {
        grid-template-columns: repeat(3, minmax(150px, 1fr));
    }

    .amazon-new-products__row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .home-hero {
        min-height: 430px;
    }

    .home-hero__copy {
        padding: 36px 20px 0;
    }

    .home-hero__copy p {
        font-size: 16px;
        line-height: 24px;
    }

    .home-hero__products {
        right: 20px;
        bottom: 92px;
        top: auto;
        left: 20px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .home-hero__product {
        height: 104px;
        font-size: 38px;
    }

    .amazon-home__content {
        margin-top: -76px;
        padding: 0 12px 28px;
    }

    .amazon-card-grid,
    .amazon-product-row,
    .amazon-new-products__row,
    .amazon-category-row {
        grid-template-columns: 1fr;
    }

    .amazon-card {
        min-height: auto;
    }

    .amazon-product-row,
    .amazon-category-row {
        gap: 12px;
    }

    .amazon-product {
        display: grid;
        grid-template-columns: 120px 1fr;
        column-gap: 14px;
        align-items: start;
    }

    .amazon-product__image {
        grid-row: span 4;
        height: 120px;
        margin-bottom: 0;
    }

    .amazon-slider-controls button {
        width: 34px;
        height: 34px;
    }

    .amazon-featured-slider {
        grid-auto-columns: 76%;
    }

    .amazon-new-product {
        grid-template-columns: 86px 1fr;
    }

    .amazon-feature-band {
        align-items: stretch;
        flex-direction: column;
        padding: 20px;
    }

    .amazon-outline-button {
        width: 100%;
    }
}
</style>
