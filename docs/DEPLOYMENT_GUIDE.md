# Hướng Dẫn Triển Khai Thực Tế (Production Deployment Guide)

Tài liệu này hướng dẫn các bước đưa dự án NomReNom từ môi trường phát triển XAMPP cục bộ lên môi trường máy chủ Production thực tế (Shared Hosting cPanel, VPS Ubuntu/Debian hoặc Cloud Server).

---

## 1. Yêu Cầu Máy Chủ Production

- **Web Server:** Nginx hoặc Apache 2.4+ (có bật `mod_rewrite`).
- **PHP Version:** PHP 8.1 hoặc PHP 8.2+ với các extension: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `json`, `fileinfo`.
- **Cơ sở dữ liệu:** MySQL 8.0+ hoặc MariaDB 10.4+.
- **Chứng chỉ SSL:** Miễn phí qua Let's Encrypt hoặc Cloudflare (Bắt buộc để kích hoạt HTTPS cho các cổng thanh toán).

---

## 2. Các Bước Triển Khai Chi Tiết

### Bước 1: Xuất & Nhập Cơ Sở Dữ Liệu
1. Tại XAMPP, mở phpMyAdmin và xuất dữ liệu `nomrenom_db`.
2. Tạo cơ sở dữ liệu mới trên máy chủ hosting (ví dụ: `u123456_nomrenom`).
3. Tạo người dùng MySQL và gán quyền đầy đủ.
4. Nhập file `nomrenom_db.sql` vào cơ sở dữ liệu mới.

### Bước 2: Tải Mã Nguồn Lên Máy Chủ
1. Nén toàn bộ thư mục `nomrenom` thành file `.zip`.
2. Tải lên thư mục gốc của trang web (`public_html` trên cPanel hoặc `/var/www/nomrenom` trên VPS).
3. Giải nén toàn bộ tệp tin.

### Bước 3: Cập Nhật Tệp Cấu Hình
Mở file `config/database.php` trên máy chủ và cập nhật thông số kết nối:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_nomrenom');
define('DB_USER', 'u123456_admin');
define('DB_PASS', 'MatKhauBaoMatCuaBan!2026');
```

Mở file `config/mail.php` và điền tài khoản SMTP chính thức để gửi email thông báo tự động.

### Bước 4: Thiết Lập Phân Quyền Thư Mục (File Permissions)
Đảm bảo thư mục lưu trữ có quyền ghi (Write Permission):
```bash
chmod -R 775 storage/
chmod -R 775 storage/uploads/
chmod -R 775 storage/logs/
```

### Bước 5: Cập Nhật Cổng Thanh Toán Sang Chế Độ Live
1. Liên hệ với VNPay / MoMo để đăng ký hợp đồng thương mại điện tử chính thức.
2. Thay thế `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET` và chuyển endpoint từ `sandbox.vnpayment.vn` sang máy chủ thanh toán thật `pay.vnpayment.vn`.
3. Khai báo Return URL và IPN URL chính thức theo tên miền thật của bạn (ví dụ: `https://nomrenom.vn/payments/vnpay_return.php`).

---

## 3. Cấu Hình Bảo Mật Khuyên Dùng Cho Production

- Bật cờ `session.cookie_secure = 1` trong `config/config.php` khi chạy trên giao thức HTTPS.
- Ngăn chặn duyệt thư mục (`Options -Indexes` trong `.htaccess`).
- Ẩn hiển thị lỗi kỹ thuật ra màn hình (`display_errors = Off`) và chỉ ghi vào file error log.
