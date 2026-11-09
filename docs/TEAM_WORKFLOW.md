# Quy Trình Làm Việc Nhóm 4 Thành Viên (Team Workflow & Git Strategy)

Tài liệu này mô tả cách thức tổ chức, phân chia trách nhiệm kỹ thuật và luồng phối hợp (Git workflow) để 4 thành viên trong nhóm sinh viên có thể đồng phát triển dự án NomReNom hiệu quả, tránh xung đột mã nguồn (merge conflicts) và trùng lặp mã.

---

## 1. Phân Chia Trách Nhiệm Kỹ Thuật (4 Thành Viên)

| Thành Viên | Vai Trò Dự Án | Trách Nhiệm Chi Tiết | Các Tệp Tin Phụ Trách Chính |
|---|---|---|---|
| **Thành Viên 1** | **Project Leader, UI/UX Design & Frontend Architecture** | Quản lý tiến độ chung, thiết kế phong cách màu sắc (Cream, White, Dark Green), thiết kế giao diện Responsive và trang giới thiệu đồ án. | `assets/css/style.css`, `index.php`, `about.php`, `how_it_works.php`, `contact.php`, `terms.php`, `privacy.php` |
| **Thành Viên 2** | **Customer Auth & Reservation Interface** | Phụ trách cổng thành viên, luồng đăng ký/đăng nhập, xác thực email, giao diện đặt chỗ kép (Member & Guest) và lịch sử đơn hàng. | `register.php`, `login.php`, `verify_email.php`, `profile.php`, `my_reservations.php`, `reservation_lookup.php`, `checkout.php` |
| **Thành Viên 3** | **PHP Backend, MySQL Engineering & Security** | Thiết kế lược đồ cơ sở dữ liệu `nomrenom_db`, xử lý giao dịch khóa kho chống bán trùng (Concurrency), bảo mật CSRF, XSS, PDO Prepared Statements và tích hợp băm chữ ký HMAC (VNPay/MoMo). | `config/database.php`, `database/nomrenom_db.sql`, `includes/csrf.php`, `includes/functions.php`, `includes/mailer.php`, `payments/` |
| **Thành Viên 4** | **Admin Dashboard, Analytics & QA** | Xây dựng bảng điều khiển quản trị, trực quan hóa biểu đồ phân tích kinh doanh (Chart.js), quản lý CRUD sản phẩm/tiệm bánh, kiểm thử toàn diện và tài liệu kỹ thuật. | `admin/`, `assets/css/admin.css`, `assets/js/admin.js`, `docs/`, `feedback.php` |

---

## 2. Quy Chuẩn Đặt Nhánh Git (Git Branching Model)

Để tránh xung đột khi làm việc đồng thời, nhóm áp dụng mô hình Git Feature Branch:

- **`main`**: Nhánh ổn định cao nhất, chỉ merge khi tính năng đã được kiểm thử và chạy mượt trên XAMPP.
- **`dev`**: Nhánh phát triển chung để tích hợp các tính năng của 4 thành viên.
- **Nhánh tính năng theo thành viên:**
  - `feature/ui-landing-pages` (Thành viên 1)
  - `feature/auth-customer-portal` (Thành viên 2)
  - `feature/db-concurrency-payments` (Thành viên 3)
  - `feature/admin-analytics-reports` (Thành viên 4)

---

## 3. Quy Tắc Lập Trình & Bảo Mật Chung

1. **Tuyệt đối không lưu mật khẩu thô:** Luôn sử dụng `password_hash($pwd, PASSWORD_DEFAULT)` và `password_verify()`.
2. **Bắt buộc sử dụng PDO Prepared Statements:** Mọi truy vấn có tham số từ người dùng (`$_GET`, `$_POST`) đều phải dùng `prepare()` và `execute([$param])` để chống SQL Injection.
3. **Mọi biểu mẫu POST đều phải có CSRF Token:** Nhúng `<?= csrfField() ?>` trong `<form>` và gọi `requireCsrfToken()` ở đầu script xử lý POST.
4. **Bảo toàn tính nhất quán của dữ liệu:** Không xóa cứng các sản phẩm hoặc tiệm bánh đã phát sinh đơn hàng lịch sử nhằm bảo toàn tính toàn vẹn của báo cáo kế toán.
