# Hướng Dẫn Cấu Hình Cơ Sở Dữ Liệu MySQL NomReNom

Tài liệu này giải thích cấu trúc cơ sở dữ liệu `nomrenom_db`, quy chuẩn thiết kế và cách thay đổi thông tin kết nối nếu cần thiết.

---

## 1. Tệp Cấu Hình Kết Nối PDO

Thông tin kết nối cơ sở dữ liệu được định nghĩa tập trung tại:
```
C:\xampp\htdocs\nomrenom\config\database.php
```

Nội dung cấu hình mặc định tương thích hoàn toàn với XAMPP:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'nomrenom_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

- Nếu máy chủ MySQL của bạn có mật khẩu (ví dụ `root` / `123456`), hãy mở file trên và sửa giá trị `DB_PASS`.
- Hệ thống kích hoạt chế độ bắt lỗi ngoại lệ `PDO::ERRMODE_EXCEPTION` và thiết lập bộ ký tự `utf8mb4_unicode_ci` để hiển thị tiếng Việt hoàn hảo.

---

## 2. Danh Sách 13 Bảng Dữ Liệu Trong `nomrenom_db`

1. **`users`**: Tài khoản người dùng (Khách hàng thành viên và Quản trị viên).
2. **`guest_customers`**: Hồ sơ khách vãng lai đặt chỗ nhanh không cần tài khoản.
3. **`bakeries`**: Danh bạ các tiệm bánh đối tác, địa chỉ, số điện thoại, giờ mở/đóng cửa.
4. **`food_boxes`**: Danh mục hộp bánh thặng dư (Snack Box 40.000₫ & Meal Box 70.000₫), số lượng tồn kho theo thời gian thực.
5. **`pickup_slots`**: Khung giờ nhận bánh cụ thể trong ngày của từng tiệm bánh (giới hạn sức chứa để tránh quá tải).
6. **`reservations`**: Bảng giao dịch đơn đặt chỗ trọng tâm, lưu mã đơn duy nhất (e.g. `NRN-20261105-ABCD`).
7. **`reservation_items`**: Chi tiết các hộp bánh trong từng đơn đặt chỗ.
8. **`payments`**: Nhật ký giao dịch tài chính (Cổng trực tuyến VNPay, MoMo hoặc Thu tiền tại quầy).
9. **`email_verification_tokens`**: Token mã hóa băm ngẫu nhiên có thời hạn 24h để kích hoạt tài khoản.
10. **`phone_verification_tokens`**: Mã OTP SMS xác thực số điện thoại.
11. **`password_reset_tokens`**: Token một lần có thời hạn 60 phút để đặt lại mật khẩu.
12. **`feedback`**: Đánh giá sao (1-5 sao) và nhận xét của khách hàng đối với tiệm bánh.
13. **`admin_activity_logs`**: Nhật ký kiểm toán bảo mật ghi lại hành vi của quản trị viên kèm IP.

---

## 3. Khóa Giao Dịch Chống Bán Trùng (Concurrency Locking)

Hệ thống NomReNom sử dụng cơ chế kiểm soát giao dịch chuẩn ACID:
```sql
SELECT available_qty, price FROM food_boxes WHERE id = ? FOR UPDATE;
SELECT max_capacity, reserved_count FROM pickup_slots WHERE id = ? FOR UPDATE;
```
Nhờ sử dụng `FOR UPDATE` trong một `PDO::beginTransaction()`, hai khách hàng đặt cùng một suất bánh cuối cùng cùng một giây sẽ không bao giờ bị overselling (bán âm kho).
