# Hướng Dẫn Cài Đặt & Khởi Chạy NomReNom Trên XAMPP (Windows)

Tài liệu này cung cấp hướng dẫn từng bước để cài đặt, thiết lập và chạy hoàn chỉnh dự án website **NomReNom** trên máy tính cá nhân bằng bộ phần mềm **XAMPP**.

---

## 1. Yêu Cầu Hệ Thống

- **Hệ điều hành:** Windows 10 / 11 (hoặc macOS / Linux)
- **XAMPP:** Phiên bản hỗ trợ **PHP 8.1 trở lên** (khuyên dùng PHP 8.2) và **MariaDB / MySQL 10.4+**
- **Trình duyệt:** Google Chrome, Microsoft Edge, Firefox hoặc Safari

---

## 2. Vị Trí Thư Mục Dự Án

Toàn bộ mã nguồn dự án NomReNom được đặt trong thư mục gốc `htdocs` của XAMPP:

```
C:\xampp\htdocs\nomrenom\
```

Khi máy chủ Apache khởi chạy, bạn có thể truy cập website tại đường dẫn:
```
http://localhost/nomrenom/
```

Và cổng quản trị dành cho ban quản trị tại:
```
http://localhost/nomrenom/admin/
```

---

## 3. Khởi Động Dịch Vụ Trong XAMPP Control Panel

1. Mở ứng dụng **XAMPP Control Panel** (chạy với quyền *Run as administrator* nếu cần).
2. Tại dòng **Apache**, nhấn nút **Start** (Đèn trạng thái chuyển sang màu xanh lá, hiển thị cổng 80, 443).
3. Tại dòng **MySQL**, nhấn nút **Start** (Đèn trạng thái chuyển sang màu xanh lá, hiển thị cổng 3306).

---

## 4. Thiết Lập Cơ Sở Dữ Liệu MySQL (`nomrenom_db`)

Có 2 cách nhanh chóng để tạo và nạp dữ liệu mẫu ban đầu:

### Cách 1: Sử dụng giao diện đồ họa phpMyAdmin (Khuyên dùng)
1. Mở trình duyệt web và truy cập: `http://localhost/phpmyadmin/`
2. Nhấn vào tab **Import** (Nhập) trên thanh menu trên cùng.
3. Nhấn **Choose File** (Chọn tệp) và tìm đến file:
   ```
   C:\xampp\htdocs\nomrenom\database\nomrenom_db.sql
   ```
4. Cuộn xuống cuối trang và nhấn nút **Import** (Thực hiện).
5. Hệ thống sẽ tự động tạo cơ sở dữ liệu `nomrenom_db` cùng 13 bảng và nạp đầy đủ dữ liệu mẫu (Tiệm bánh, hộp bánh, tài khoản quản trị và khách hàng).

### Cách 2: Sử dụng Command Line (CMD / PowerShell)
Mở cửa sổ dòng lệnh và chạy lệnh:
```bash
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\nomrenom\database\nomrenom_db.sql"
```

---

## 5. Tài Khoản Đăng Nhập Mẫu Ban Đầu

Hệ thống đã nạp sẵn 2 tài khoản mẫu đã được mã hóa bảo mật chuẩn Bcrypt:

### 1. Tài Khoản Quản Trị Viên (Admin)
- **Đường dẫn đăng nhập:** `http://localhost/nomrenom/admin/login.php`
- **Email:** `admin@nomrenom.vn`
- **Mật khẩu:** `Admin@12345`
- **Quyền hạn:** Quản lý toàn bộ đơn đặt chỗ, sản phẩm, tiệm bánh, khách hàng, biểu đồ Chart.js và xuất file CSV.

### 2. Tài Khoản Khách Hàng Sinh Viên (Customer)
- **Đường dẫn đăng nhập:** `http://localhost/nomrenom/login.php`
- **Email:** `student@university.edu.vn`
- **Mật khẩu:** `Student@12345`
- **Trạng thái:** Đã xác thực email, có thể đặt giữ chỗ ngay lập tức.
