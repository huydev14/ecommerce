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
        allowClose: false,
        onDestroyStarted: () => {
            if (!driverObj.hasNextStep() || confirm('Anh/Chị có chắc muốn bỏ qua phần hướng dẫn này?')) {
                markTourCompleted();
                driverObj.destroy();
            }
        },
        steps: [
    {
        popover: {
            title: 'Welcome HR! 👋 ',
            description: `
                <div class="hr-tour-content">
                    <p>
                        Cảm ơn Anh/Chị đã dành thời gian tham quan dự án của em.
                        <br>
                        Anh/Chị có thể truy cập trang admin tại đây:
                    </p>

                    <a class="hr-tour-admin-link" href="${ADMIN_URL}" target="_blank" rel="noopener noreferrer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        Mở tab Admin
                    </a>

                    <p>
                        Sau đây là một số <strong>tính năng kỹ thuật nổi bật</strong>
                        mà em đã triển khai ở phần homepage.
                    </p>
                </div>
            `,
            side: 'over',
            align: 'center',
            onPopoverRender: (popover) => {
                const adminLink = popover.wrapper.querySelector('.hr-tour-admin-link');

                adminLink?.addEventListener('click', () => {
                    markTourCompleted();
                });
            },
        },
    },
    {
        element: '.open-location-modal',
        popover: {
            title: 'Tính phí vận chuyển bằng GHN API',
            description:
                'Người dùng có thể chọn địa chỉ giao hàng, hệ thống sẽ gọi GHN API để tính phí vận chuyển theo khu vực tương ứng.',
            side: 'bottom',
            align: 'start',
        },
    },
    {
        element: '.trending-keywords',
        popover: {
            title: 'Lưu và hiển thị từ khóa tìm kiếm phổ biến',
            description:
                'Mỗi lượt tìm kiếm của khách hàng được ghi nhận vào Redis Sorted Set. Hệ thống sẽ lấy top 10 từ khóa có điểm số cao nhất để hiển thị.',
            side: 'bottom',
            align: 'start',
        },
    },
    {
        element: '.client-cart',
        popover: {
            title: 'Tối ưu Giỏ hàng',
            description:
                'Các thao tác thêm, sửa, xóa sản phẩm trong giỏ hàng được xử lý thông qua Redis Hash, giúp giảm truy vấn database và tăng tốc độ phản hồi.',
            side: 'bottom',
            align: 'start',
        },
    },
    {
        element: '.best-sellers',
        popover: {
            title: 'Caching dữ liệu homepage',
            description:
                'Các section hiển thị ở homepage được lấy từ cache nhằm giảm tải cho database và tăng tốc độ tải trang.',
            side: 'bottom',
            align: 'start',
        },
    },
    {
        element: '.client-product-card__image',
        popover: {
            title: 'Tối ưu hình ảnh với Cloudinary',
            description:
                'Tất cả hình ảnh được tối ưu với Cloudinary, đảm bảo chất lượng hiển thị.',
            side: 'bottom',
            align: 'start',
        },
    },
    {
        element: '.client-product-card__shipping',
        popover: {
            title: 'Hiển thị phí ship theo địa chỉ',
            description:
                'Phí vận chuyển được tính toán dựa trên địa chỉ giao hàng mà người dùng đã chọn.',
            side: 'bottom',
            align: 'start',
        },
    },
    {
        popover: {
            title: 'Trải nghiệm thử quy trình đặt hàng',
            description:
                'Anh/Chị có thể thử tìm kiếm sản phẩm, thêm vào giỏ hàng và thực hiện quy trình đặt hàng để xem luồng xử lý thực tế của hệ thống.',
            side: 'bottom',
            align: 'start',
            onNextClick: () => {
                markTourCompleted();
                driverObj.destroy();
                location.reload();
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
