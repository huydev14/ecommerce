<script setup>
import { onMounted } from 'vue';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

const HR_TOUR_COMPLETED_KEY = 'hr_tour_completed';
const ADMIN_URL = '/admin';

const markTourCompleted = () => {
    localStorage.setItem(HR_TOUR_COMPLETED_KEY, 'true');
};

onMounted(() => {
    const hasSeenTour = localStorage.getItem(HR_TOUR_COMPLETED_KEY);

    if (!hasSeenTour) {
        setTimeout(() => {
            startHrTourHome();
        }, 1000);
    }
});

const startHrTourHome = () => {
    const driverObj = driver({
        showProgress: true,
        animate: true,
        doneBtnText: 'Trải nghiệm ngay',
        nextBtnText: 'Tiếp theo',
        prevBtnText: 'Quay lại',
        progressText: '{{current}} / {{total}}',
        steps: [
            {
                popover: {
                    title: '👋 Welcome HR!',
                    description: `
                        <div class="hr-tour-content">
                            <p>Cảm ơn Anh/Chị đã tham quan dự án của em. <br>Đây là link để truy cập trang Admin:</p>
                            
                            <a class="hr-tour-admin-link" href="${ADMIN_URL}" target="_blank" rel="noopener noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                Mở tab admin
                            </a>

                            <p>Em xin được giới thiệu nhanh các <strong>tính năng nổi bật nhất</strong> tại phần homepage này nhé!</p>
                        </div>
                    `,
                    side: 'over',
                    align: 'center',
                    onPopoverRender: (popover) => {
                        const adminLink = popover.wrapper.querySelector('.hr-tour-admin-link');

                        adminLink?.addEventListener('click', () => {
                            markTourCompleted();
                            driverObj.destroy();
                        });
                    },
                },
            },
            {
                element: '.open-location-modal',
                popover: {
                    title: 'Tích hợp GHN API tính toán phí ship',
                    description: 'Khi chọn địa chỉ giao hàng, API sẽ trả về phí vận chuyển dựa trên địa chỉ đó.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '.trending-keywords',
                popover: {
                    title: 'Lưu trữ trending keywords',
                    description:
                        'Mỗi khi khách hàng tìm kiếm, từ khóa sẽ được lưu vào Redis sorted set với điểm số tăng dần. Phần trending keywords sẽ lấy top 10 từ khóa có điểm số cao nhất để hiển thị.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '.client-cart',
                popover: {
                    title: 'Tối ưu Giỏ hàng',
                    description: 'Toàn bộ thao tác CRUD với giỏ hàng được xử lý qua Redis hashset, cho tốc độ phản hồi gần như tức thời.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '.best-sellers',
                popover: {
                    title: 'Caching API',
                    description: 'Các section ở homepage đều được lấy từ cache để giảm tải đáng kể cho database',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '.client-product-card__image',
                popover: {
                    title: 'Tối ưu hình ảnh',
                    description: 'Tích hợp Cloudinary để tối ưu toàn bộ hình ảnh',
                    side: 'bottom',
                    align: 'start',
                },
            },

            {
                element: '.client-product-card__shipping',
                popover: {
                    title: '',
                    description: 'Phí ship được tính toán qua GHN API dựa trên địa chỉ đã chọn',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                popover: {
                    title: 'Hãy thử đặt hàng ngay nào!',

                    side: 'bottom',
                    align: 'start',
                    onNextClick: () => {
                        markTourCompleted();
                        driverObj.destroy();
                    },
                },
            },
        ],
    });

    driverObj.drive();
};
</script>

<template>
    <div class="hr-tour-wrapper"></div>
</template>

<style>
/* Khung popup chính - Hiệu ứng Mica/Acrylic */
.driver-popover {
    border-radius: 8px !important; /* Chuẩn corner radius Win 11 */
    font-family:
        'Segoe UI Variable',
        'Segoe UI',
        -apple-system,
        BlinkMacSystemFont,
        Roboto,
        sans-serif !important;
    background-color: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(20px) !important; /* Làm mờ nền đằng sau */
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.14) !important; /* Elevation shadow */
    border: 1px solid rgba(0, 0, 0, 0.05) !important; /* Viền siêu mỏng */
    padding: 16px !important;
    max-width: 360px !important; /* Flyout của Windows thường gọn gàng */
}

/* Tiêu đề */
.driver-popover-title {
    font-size: 16px !important;
    font-weight: 600 !important;
    color: #1a1a1a !important; /* Đen nhạt, không dùng đen tuyệt đối */
    margin-bottom: 8px !important;
}

.driver-popover-description {
    font-size: 14px !important;
    font-weight: 400 !important;
    color: #3b3b3b !important;
    line-height: 1.5 !important;
}

.hr-tour-content p {
    margin: 8px 0;
}

.hr-tour-admin-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 6px;
    background-color: #005fb8;
    color: #ffffff !important;
    font-size: 14px;
    font-weight: 400;
    padding: 6px 12px;
    margin: 12px 0;
    text-decoration: none !important;
    transition: all 0.1s ease-in-out;
    width: 100%;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.15),
        0 1px 2px rgba(0, 0, 0, 0.1);
    border: 1px solid transparent;
}

.hr-tour-admin-link:hover {
    background-color: #0058ab;
}

.hr-tour-admin-link:active {
    background-color: #00519d;
    box-shadow: none;
}
</style>
