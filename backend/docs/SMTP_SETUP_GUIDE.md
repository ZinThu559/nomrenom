# Hướng Dẫn Cấu Hình Email SMTP & Cơ Chế Kiểm Thử Cục Bộ

Tài liệu này hướng dẫn cách cấu hình gửi email thực tế qua máy chủ **SMTP** (Gmail, Mailtrap, SendGrid) và cách kiểm thử tính năng kích hoạt tài khoản ngay trên môi trường **XAMPP Localhost**.

---

## 1. Tệp Cấu Hình SMTP

Mở tệp cấu hình tại:
```
C:\xampp\htdocs\nomrenom\config\mail.php
```

Nội dung cấu hình:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // 587 (TLS) hoặc 465 (SSL)
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password'); // Mật khẩu ứng dụng 16 ký tự của Google
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', 'no-reply@nomrenom.vn');
define('SMTP_FROM_NAME', 'NomReNom Surplus Food');
```

---

## 2. Hướng Dẫn Tạo Mật Khẩu Ứng Dụng Gmail (Google App Password)

Nếu bạn muốn gửi email thật đến hòm thư người dùng qua Gmail:
1. Truy cập trang Quản lý tài khoản Google: https://myaccount.google.com/
2. Chọn mục **Bảo mật** (Security) và bật **Xác minh 2 bước** (2-Step Verification).
3. Tìm kiếm mục **Mật khẩu ứng dụng** (App passwords).
4. Nhập tên ứng dụng (ví dụ: `NomReNom XAMPP`) và nhấn **Tạo** (Create).
5. Google sẽ cấp cho bạn một chuỗi 16 chữ cái (ví dụ: `abcd efgh ijkl mnop`).
6. Dán chuỗi này vào `SMTP_PASS` trong file `config/mail.php` và dán địa chỉ Gmail của bạn vào `SMTP_USER`.

---

## 3. Cơ Chế Kiểm Thử Cục Bộ XAMPP (Local Audit Dispatcher)

Để sinh viên và giảng viên có thể kiểm thử toàn diện quy trình kích hoạt tài khoản và xác nhận đơn hàng ngay trên máy cá nhân mà không nhất thiết phải có kết nối Internet hoặc thông tin SMTP:

1. **Ghi nhật ký thời gian thực:** Mọi email gửi đi (bao gồm tiêu đề, người nhận, nội dung và **liên kết Token kích hoạt duy nhất**) đều được ghi tự động vào tệp:
   ```
   C:\xampp\htdocs\nomrenom\storage\logs\mail.log
   ```
2. **Thanh thông báo trực tiếp (Developer Notification Banner):** Khi bạn đăng ký tài khoản mới hoặc đặt giữ chỗ, một thanh thông báo màu xanh dương sẽ xuất hiện ngay trên giao diện web kèm nút:
   ```
   [Nhấp Để Mở Liên Kết Xác Thực (Token Link)]
   ```
   Giúp bạn kích hoạt tài khoản chỉ bằng một cú click chuột mà không cần mở hộp thư điện tử!
