# ==========================================
# STAGE 1: Base Image (Cài đặt PHP, Nginx, Supervisor và Extensions)
# ==========================================
FROM php:8.2-fpm-bookworm AS base

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Cài đặt các thư viện hệ thống cần thiết
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libxml2-dev \
    libonig-dev \
    mariadb-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Cài đặt Redis extension thông qua PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Sao chép Composer từ image chính thức
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Sao chép cấu hình Nginx, Supervisor, PHP và Opcache vào Container
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom-php.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Thiết lập ghi log Nginx qua stdout/stderr
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# ==========================================
# STAGE 2: Build Stage (Build Composer và Assets Frontend)
# ==========================================
FROM base AS builder

# Cài đặt Node.js (cần thiết để build assets qua Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Thiết lập thư mục làm việc tạm thời để build
WORKDIR /app

# Sao chép file cấu hình trước để tối ưu hóa Docker Cache
COPY composer.json composer.lock package.json package-lock.json artisan ./

# Cài đặt dependencies PHP (chưa tối ưu hóa tự động tải để chuẩn bị build frontend)
RUN composer install --no-interaction --no-scripts --no-progress

# Sao chép toàn bộ mã nguồn của dự án
COPY . .

# Cài đặt thư viện JS và chạy build assets qua Vite
# LƯU Ý: Dự án có lệnh `npm run lang:js` phụ thuộc vào `php artisan` nên bắt buộc chạy trong môi trường PHP
RUN npm ci \
    && npm run build

# Xóa thư mục node_modules và cài lại composer tối ưu cho production
RUN rm -rf node_modules \
    && composer install --no-interaction --no-dev --optimize-autoloader

# ==========================================
# STAGE 3: Final Production Image (Tối ưu hóa dung lượng nhẹ và bảo mật)
# ==========================================
FROM base AS production

# Sao chép toàn bộ mã nguồn đã build sạch từ builder stage
COPY --from=builder /app /var/www/html

# Sao chép script entrypoint và cấp quyền thực thi
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Thiết lập quyền sở hữu cho thư mục lưu trữ của Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Cổng dịch vụ Web
EXPOSE 80

# Chỉ định Script Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
