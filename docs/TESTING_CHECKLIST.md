# NomReNom – Bảng Kiểm Thử Toàn Diện (Testing Checklist)

Bảng danh mục các bước kiểm thử chức năng dành cho nhóm sinh viên và giảng viên chấm đồ án để nghiệm thu toàn bộ hệ thống.

---

## 1. Kiểm Thử Cơ Sở Dữ Liệu & Môi Trường
- [x] Apache và MySQL đang chạy ổn định trong XAMPP Control Panel.
- [x] Cơ sở dữ liệu `nomrenom_db` chứa đủ 13 bảng với khóa chính và khóa ngoại InnoDB.
- [x] Dữ liệu mẫu (3 tiệm bánh, 6 hộp bánh, các khung giờ nhận) hiển thị đầy đủ.

---

## 2. Kiểm Thử Giao Diện & Trải Nghiệm Người Dùng (UI/UX)
- [x] Trang chủ `index.php` hiển thị chuẩn màu Cream, White, Dark Green, typography hiện đại.
- [x] Thanh tìm kiếm và bộ lọc loại hộp (Snack Box 40k, Meal Box 70k) hoạt động trơn tru.
- [x] Responsive hoàn hảo trên thiết bị di động, máy tính bảng và màn hình lớn.
- [x] Bảng số liệu thống kê thời gian thực (Impact Counter) tính toán từ cơ sở dữ liệu.

---

## 3. Kiểm Thử Đăng Ký & Xác Thực Tài Khoản
- [x] Đăng ký tài khoản mới với đầy đủ kiểm tra định dạng email và số điện thoại 10 số.
- [x] Ngăn chặn đăng ký trùng email đã tồn tại trong hệ thống.
- [x] Mật khẩu được mã hóa một chiều qua `password_hash()`.
- [x] Token kích hoạt tài khoản được tạo và ghi nhận trong `email_verification_tokens` (và `storage/logs/mail.log`).
- [x] Nhấp vào liên kết xác thực kích hoạt tài khoản thành công (`is_email_verified = 1`).
- [x] Đăng nhập tài khoản thành viên thành công và chuyển hướng về trang tương ứng.
- [x] Quên mật khẩu và quy trình đặt lại mật khẩu bằng token một lần.

---

## 4. Kiểm Thử Quy Trình Đặt Chỗ (Registered & Guest Checkout)
- [x] Đặt chỗ bằng tài khoản thành viên (tự động điền họ tên, email, điện thoại).
- [x] Đặt chỗ bằng khách vãng lai (Guest Checkout) với thông tin liên hệ thực tế.
- [x] Chọn khung giờ nhận bánh hợp lệ.
- [x] Bộ chọn số lượng cập nhật tự động giá tiền theo thời gian thực (JavaScript).
- [x] Khóa giao dịch `SELECT ... FOR UPDATE` ngăn chặn tình trạng bán quá số lượng có sẵn.
- [x] Số lượng tồn kho `available_qty` của hộp bánh tự động trừ chính xác khi đặt thành công.
- [x] Mã đặt chỗ duy nhất định dạng `NRN-YYYYMMDD-XXXX` được sinh ra.
- [x] Trang phiếu xác nhận `confirmation.php` hiển thị đầy đủ chi tiết và hỗ trợ in ấn (`window.print()`).

---

## 5. Kiểm Thử Tra Cứu Đơn Hàng Bảo Mật
- [x] Trang `reservation_lookup.php` cho phép tra cứu đơn đặt chỗ.
- [x] Yêu cầu nhập đúng cả Mã đặt chỗ VÀ Email/Số điện thoại để ngăn chặn người lạ xem trộm thông tin đơn.

---

## 6. Kiểm Thử Thanh Toán (Online & Pay at Pickup)
- [x] Hình thức **Thanh toán khi nhận bánh (Pay at Pickup)**: Ghi nhận đơn hàng với trạng thái `Pay at Pickup`.
- [x] Hình thức **VNPay Sandbox**: Khởi tạo yêu cầu có chữ ký số HMAC-SHA512.
- [x] Hình thức **MoMo Sandbox**: Khởi tạo yêu cầu có chữ ký số HMAC-SHA256.
- [x] Trình giả lập `payments/sandbox_simulator.php` kiểm tra thành công, thất bại và phát hiện chữ ký bị can thiệp.

---

## 7. Kiểm Thử Bảng Điều Khiển Quản Trị (Admin Dashboard)
- [x] Đăng nhập quản trị viên tại `admin/login.php` (yêu cầu role = 'admin').
- [x] Bảng điều khiển `admin/index.php` hiển thị đầy đủ số liệu KPI thực tế và biểu đồ Chart.js (Xu hướng 7 ngày và tỉ lệ hộp bánh).
- [x] Quản lý hộp bánh: Thêm mới, chỉnh sửa, bật/tắt mở bán và tải lên hình ảnh sản phẩm.
- [x] Quản lý đơn hàng: Lọc theo trạng thái, tìm kiếm, đánh dấu khách đã nhận bánh & thu tiền.
- [x] Xuất báo cáo danh sách đơn hàng sang tệp CSV Excel (`export=csv`).
- [x] Tính năng hủy đơn tự động hoàn trả số lượng bánh về lại tồn kho tiệm bánh.
- [x] Quản lý tiệm bánh: Thêm/sửa tiệm bánh, cập nhật địa chỉ, giờ hoạt động.
- [x] Kiểm duyệt đánh giá khách hàng (Feedback Moderation).
- [x] Xem nhật ký kiểm toán quản trị viên trong `admin/activity_logs.php`.
