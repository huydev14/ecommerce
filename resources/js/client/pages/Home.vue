<template>
    <div class="tw-relative">
        <div class="tw-relative tw-w-full">
            <HeroCarousel />
            <div class="tw-absolute tw-bottom-0 tw-h-40 tw-w-full tw-bg-gradient-to-t tw-from-[#EAEDED] tw-to-transparent"></div>
        </div>

        <div class="home-grid-container">
            <div class="home-grid-primary">
                <div class="grid-card">
                    <CategoryCard title="Trang trí nhà cửa" />
                </div>
                <div class="grid-card">
                    <CategoryCard title="Thiết bị điện tử" />
                </div>
                <div class="grid-card">
                    <CategoryCard title="Ưu đãi mỗi ngày" />
                </div>
                <div class="grid-card"></div>
            </div>

            <div class="grid-card tw-mt-5">
                <h2 class="tw-mb-4 tw-text-xl tw-font-bold">Sản phẩm bán chạy nhất</h2>
            </div>

            <div class="home-grid-primary tw-mt-5">
                <div v-for="i in 4" :key="i" class="grid-card"></div>
            </div>
        </div>
    </div>
</template>
