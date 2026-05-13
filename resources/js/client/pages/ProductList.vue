<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const searchQuery = computed(() => route.query.q || 'fitness clothing');

const filters = [
    {
        title: 'Gender',
        options: ['Men', 'Women', 'Boys', 'Girls', 'Babies', 'Unisex'],
    },
    {
        title: 'Brands',
        options: ['Reebok', 'Under Armour', 'adidas', 'Amazon Essentials', 'PUMA', 'BAMBOO COOL', 'COOFANDY'],
        hasMore: true,
    },
    {
        title: 'Amazon Fashion',
        options: ['Our Brands', 'Premium Brands', 'Top Brands'],
    },
    {
        title: 'Deals & Discounts',
        links: ['All Discounts', 'Buy More, Save More', 'Today Deals'],
    },
];

const colors = [
    '#111111',
    '#f5f5f5',
    '#808080',
    '#8b5a2b',
    '#e8cda5',
    '#cf2e2e',
    '#e98ab6',
    '#f59e0b',
    '#f8df21',
    '#ffffff',
    '#64b246',
    '#2f65b0',
    '#6a4ca0',
    '#d4a800',
    '#737373',
    '#d9f2ff',
];

const products = [
    {
        badge: 'Overall Pick',
        title: 'Kinglaman 1/5 Pack Mesh Workout Shirts for Men Dry Fit Gym Quick Dry Athletic Short Sleeve Sports T-Shirt',
        image: 'https://images.unsplash.com/photo-1523398002811-999ca8dec234?auto=format&fit=crop&w=640&q=80',
        rating: '4.6',
        reviews: '2.6K',
        bought: '2K+ bought in past month',
        price: '526,697',
    },
    {
        title: "Reebok Men's Small Logo Short Sleeve Crewneck T-Shirt, Standard Fit, Breathable Lightweight Stretch Knit Fabric",
        image: 'https://images.unsplash.com/photo-1578932750294-f5075e85f44a?auto=format&fit=crop&w=640&q=80',
        rating: '4.7',
        reviews: '223',
        bought: '50+ bought in past month',
        price: '250,306',
        original: 'VND283,768',
    },
    {
        title: 'High Impact Sports Bras for Women, High Support Molded Cup Crisscross Back Workout Yoga Top',
        image: 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=640&q=80',
        rating: '4.7',
        reviews: '20',
        price: '711,133',
    },
    {
        title: "PUMA Men's 5 Pack Performance Boxer Briefs",
        image: 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=640&q=80',
        rating: '4.6',
        reviews: '3.1K',
        bought: '100+ bought in past month',
        price: '617,861',
        tag: '#1 Top Rated',
    },
    {
        badge: 'Best Seller',
        title: 'SINOPHANT High Waisted Leggings with Pockets Women, Full Length Capri Buttery Soft Stretchy Yoga Pants',
        image: 'https://images.unsplash.com/photo-1506629905607-d9f297d2b17c?auto=format&fit=crop&w=640&q=80',
        rating: '4.4',
        reviews: '11.3K',
        bought: '7K+ bought in past month',
        price: '210,521',
        original: 'VND263,217',
    },
    {
        title: 'Neleus Running Shorts with Zipper Pockets, Lightweight Athletic Training Shorts',
        image: 'https://images.unsplash.com/photo-1556906781-9a412961c28c?auto=format&fit=crop&w=640&q=80',
        rating: '4.5',
        reviews: '6.8K',
        bought: '900+ bought in past month',
        price: '318,900',
    },
    {
        title: "Champion Men's Classic Cotton Long Sleeve Tee for Training and Casual Wear",
        image: 'https://images.unsplash.com/photo-1516826957135-700dedea698c?auto=format&fit=crop&w=640&q=80',
        rating: '4.6',
        reviews: '4.2K',
        price: '285,000',
    },
    {
        title: "Women's Dry Fit Running Shirts Short Sleeve Athletic Gym Workout Tops",
        image: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=640&q=80',
        rating: '4.5',
        reviews: '1.9K',
        bought: '300+ bought in past month',
        price: '199,000',
    },
    {
        title: "Men's Compression Baselayer Shirt, Long Sleeve Sports Training Top",
        image: 'https://images.unsplash.com/photo-1520975916090-3105956dac38?auto=format&fit=crop&w=640&q=80',
        rating: '4.3',
        reviews: '982',
        price: '339,500',
    },
    {
        badge: 'Best Seller',
        title: 'Workout Matching Set for Women, Ribbed Tank Top and Jogger Pants Lounge Set',
        image: 'https://images.unsplash.com/photo-1548883354-94bcfe321cbb?auto=format&fit=crop&w=640&q=80',
        rating: '4.6',
        reviews: '8.4K',
        bought: '4K+ bought in past month',
        price: '455,000',
    },
];

const primaryProducts = computed(() => products.slice(0, 5));
const moreProducts = computed(() => products.slice(5));
</script>

<template>
    <section class="product-listing">
        <div class="listing-summary">
            <p>
                1-48 of over 30,000 results for <strong>"{{ searchQuery }}"</strong>
            </p>

            <label class="sort-control">
                <span>Sort by:</span>
                <select>
                    <option>Featured</option>
                    <option>Price: Low to High</option>
                    <option>Avg. Customer Review</option>
                    <option>Newest Arrivals</option>
                </select>
            </label>
        </div>

        <div class="listing-shell">
            <aside class="listing-filters" aria-label="Product filters">
                <section v-for="group in filters" :key="group.title" class="filter-group">
                    <h2>{{ group.title }}</h2>

                    <div v-if="group.options" class="filter-options">
                        <label v-for="option in group.options" :key="option" class="filter-check">
                            <input type="checkbox" />
                            <span>{{ option }}</span>
                        </label>
                    </div>

                    <div v-if="group.links" class="filter-links">
                        <a v-for="link in group.links" :key="link" href="#">{{ link }}</a>
                    </div>

                    <button v-if="group.hasMore" type="button" class="filter-more">See more</button>
                </section>

                <section class="filter-group">
                    <h2>Customer Reviews</h2>
                    <a href="#" class="review-filter"><span>★★★★★</span> & Up</a>
                </section>

                <section class="filter-group">
                    <h2>Color</h2>
                    <div class="color-grid">
                        <button
                            v-for="color in colors"
                            :key="color"
                            type="button"
                            class="color-swatch"
                            :style="{ backgroundColor: color }"
                            aria-label="Filter by color"
                        ></button>
                    </div>
                </section>
            </aside>

            <main class="listing-results">
                <header class="results-header">
                    <h1>Results</h1>
                    <p>
                        Check each product page for other buying options. Price and other details may vary based on product size and color.
                    </p>
                </header>

                <div class="product-grid">
                    <article v-for="product in primaryProducts" :key="product.title" class="product-card">
                        <span v-if="product.badge" class="product-badge" :class="{ 'is-orange': product.badge === 'Best Seller' }">
                            {{ product.badge }}
                        </span>

                        <a href="#" class="product-image">
                            <img :src="product.image" :alt="product.title" />
                        </a>

                        <div class="product-content">
                            <a href="#" class="product-title">{{ product.title }}</a>
                            <p v-if="product.tag" class="product-tag">{{ product.tag }}</p>
                            <div class="product-rating">
                                <span class="stars">★★★★★</span>
                                <span>{{ product.rating }}</span>
                                <a href="#">({{ product.reviews }})</a>
                            </div>
                            <p v-if="product.bought" class="product-bought">{{ product.bought }}</p>
                            <div class="product-price"><small>VND</small>{{ product.price }}</div>
                            <p v-if="product.original" class="product-original">
                                Typical: <s>{{ product.original }}</s>
                            </p>
                            <button type="button" class="cart-button">Add to cart</button>
                        </div>
                    </article>
                </div>

                <section class="more-results">
                    <h2>More results</h2>

                    <div class="product-grid">
                        <article v-for="product in moreProducts" :key="product.title" class="product-card">
                            <span v-if="product.badge" class="product-badge is-orange">{{ product.badge }}</span>

                            <a href="#" class="product-image">
                                <img :src="product.image" :alt="product.title" />
                            </a>

                            <div class="product-content">
                                <a href="#" class="product-title">{{ product.title }}</a>
                                <div class="product-rating">
                                    <span class="stars">★★★★★</span>
                                    <span>{{ product.rating }}</span>
                                    <a href="#">({{ product.reviews }})</a>
                                </div>
                                <p v-if="product.bought" class="product-bought">{{ product.bought }}</p>
                                <div class="product-price"><small>VND</small>{{ product.price }}</div>
                                <button type="button" class="cart-button">Add to cart</button>
                            </div>
                        </article>
                    </div>
                </section>
            </main>
        </div>
    </section>
</template>

<style scoped>
.product-listing {
    min-height: 100vh;
    background: #ffffff;
    color: #0f1111;
    font-family: Arial, Helvetica, sans-serif;
}

.listing-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-height: 44px;
    padding: 0 8.5%;
    border-bottom: 1px solid #d5d9d9;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    font-size: 14px;
}

.listing-summary strong {
    color: #c45500;
}

.sort-control {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex: 0 0 auto;
    font-size: 12px;
}

.sort-control select {
    height: 28px;
    border: 1px solid #d5d9d9;
    border-radius: 8px;
    background: #f0f2f2;
    padding: 0 28px 0 10px;
    color: #0f1111;
    font-size: 12px;
}

.listing-shell {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 28px;
    max-width: 1760px;
    margin: 0 auto;
    padding: 24px 28px 48px;
}

.listing-filters {
    align-self: start;
    padding-top: 2px;
}

.filter-group {
    margin-bottom: 20px;
}

.filter-group h2 {
    margin: 0 0 8px;
    font-size: 14px;
    font-weight: 700;
}

.filter-options {
    display: grid;
    gap: 6px;
}

.filter-check {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 14px;
    line-height: 1.25;
}

.filter-check input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: #007185;
}

.filter-more,
.filter-links a,
.review-filter {
    display: block;
    color: #007185;
    font-size: 14px;
    text-decoration: none;
}

.filter-more {
    margin-top: 8px;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
}

.filter-links {
    display: grid;
    gap: 7px;
}

.review-filter {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #0f1111;
}

.review-filter span,
.stars {
    color: #ff9900;
    letter-spacing: 0;
}

.color-grid {
    display: grid;
    grid-template-columns: repeat(6, 26px);
    gap: 10px;
}

.color-swatch {
    width: 26px;
    height: 26px;
    border: 1px solid #d5d9d9;
    border-radius: 50%;
    cursor: pointer;
}

.results-header {
    margin-bottom: 8px;
}

.results-header h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

.results-header p {
    margin: 4px 0 0;
    color: #565959;
    font-size: 14px;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 8px;
}

.product-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    border: 1px solid #f0f2f2;
    border-radius: 4px;
    background: #ffffff;
    overflow: hidden;
}

.product-badge {
    position: absolute;
    top: 6px;
    left: 6px;
    z-index: 2;
    border-radius: 3px;
    background: #0f172a;
    padding: 3px 7px;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
}

.product-badge.is-orange {
    background: #e65a00;
}

.product-image {
    display: grid;
    place-items: center;
    height: 300px;
    background: #f7f7f7;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    max-width: 100%;
    max-height: 100%;
}

.product-content {
    padding: 12px 12px 18px;
}

.product-title {
    display: block;
    color: #0f1111;
    font-size: 16px;
    line-height: 1.35;
    text-decoration: none;
}

.product-title:hover,
.filter-links a:hover,
.review-filter:hover,
.filter-more:hover {
    color: #c45500;
    text-decoration: underline;
}

.product-tag {
    margin: 8px 0 0;
    font-size: 13px;
    font-weight: 700;
}

.product-rating {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 3px;
    margin-top: 10px;
    font-size: 13px;
}

.product-rating a {
    color: #007185;
    text-decoration: none;
}

.product-bought,
.product-original {
    margin: 4px 0 0;
    color: #565959;
    font-size: 13px;
}

.product-price {
    margin-top: 8px;
    font-size: 30px;
    line-height: 1;
    color: #0f1111;
}

.product-price small {
    margin-right: 3px;
    font-size: 12px;
    vertical-align: super;
}

.cart-button {
    margin-top: 12px;
    min-height: 32px;
    border-radius: 999px;
    padding: 0 14px;
    font-size: 13px;
    cursor: pointer;
}

.cart-button {
    border: 1px solid #ffd814;
    background: #ffd814;
    color: #0f1111;
}

.cart-button:hover {
    border-color: #f7ca00;
    background: #f7ca00;
}

.more-results {
    margin-top: 12px;
}

.more-results h2 {
    margin: 0 0 8px;
    font-size: 22px;
    font-weight: 700;
}

@media (max-width: 1400px) {
    .product-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 1100px) {
    .listing-shell {
        grid-template-columns: 220px minmax(0, 1fr);
        gap: 20px;
        padding-inline: 18px;
    }

    .product-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .product-image {
        height: 250px;
    }
}

@media (max-width: 820px) {
    .listing-summary {
        align-items: flex-start;
        padding: 10px 14px;
        flex-direction: column;
    }

    .listing-shell {
        display: block;
        padding: 14px;
    }

    .listing-filters {
        display: flex;
        gap: 16px;
        margin: 0 -14px 18px;
        padding: 0 14px 12px;
        overflow-x: auto;
        border-bottom: 1px solid #e5e7eb;
    }

    .filter-group {
        flex: 0 0 170px;
        margin: 0;
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .product-grid {
        grid-template-columns: 1fr;
    }

    .product-card {
        display: grid;
        grid-template-columns: 40% 1fr;
    }

    .product-image {
        height: auto;
        min-height: 220px;
    }

    .product-price {
        font-size: 25px;
    }
}
</style>
