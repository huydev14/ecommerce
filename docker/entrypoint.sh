#!/bin/sh
set -e

# Khởi động tối ưu hóa Laravel khi Container chạy
echo "====> Bắt đầu thiết lập môi trường Laravel..."

# Tự động tạo tệp .env nếu chưa tồn tại (hoặc lấy từ môi trường bên ngoài)
if [ ! -f "/var/www/html/.env" ]; then
    echo "====> Không tìm thấy file .env, sao chép từ .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Chờ kết nối tới cơ sở dữ liệu nếu có cấu hình DB_HOST
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    echo "====> Đang kiểm tra kết nối database $DB_HOST:$DB_PORT..."
    # Thử kết nối tới DB bằng php
    php -r "
    \$stdout = fopen('php://stdout', 'w');
    for (\$i = 0; \$i < 30; \$i++) {
        try {
            \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), [PDO::ATTR_TIMEOUT => 2]);
            fwrite(\$stdout, '====> Kết nối Database thành công!' . PHP_EOL);
            exit(0);
        } catch (PDOException \$e) {
            fwrite(\$stdout, '====> Đang chờ Database khởi động... (' . \$e->getMessage() . ')' . PHP_EOL);
            sleep(2);
        }
    }
    exit(1);
    "
fi

# Chạy migrate tự động nếu được yêu cầu
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "====> Đang chạy database migrations..."
    php artisan migrate --force
fi

# Tối ưu hóa bộ nhớ cache Laravel cho Production
echo "====> Đang tạo cache cấu hình, route và view..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Nhận lệnh truyền vào từ docker run hoặc chạy mặc định là Supervisor
if [ "$#" -gt 0 ]; then
    echo "====> Đang chạy lệnh tùy biến: $@"
    exec "$@"
else
    echo "====> Đang khởi chạy Supervisor (PHP-FPM + Nginx)..."
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
