# NomReNom – Cấu Trúc Thư Mục & Bản Đồ Tệp Tin Dự Án

Cây thư mục hoàn chỉnh của dự án NomReNom tại `C:\xampp\htdocs\nomrenom\`:

```
C:\xampp\htdocs\nomrenom\
│
├── index.php                      # Trang chủ: Hero, bộ lọc, hộp bánh nổi bật, tiệm bánh đối tác, tác động thực tế
├── boxes.php                      # Danh mục thực đơn hộp bánh (Snack Box 40k & Meal Box 70k) kèm bộ lọc đa tiêu chí
├── box_details.php                # Chi tiết hộp bánh, địa chỉ tiệm, sức chứa khung giờ nhận và đánh giá
├── checkout.php                   # Đặt chỗ & thanh toán (Hỗ trợ kép: Thành viên & Khách vãng lai, khóa giao dịch chống quá tải)
├── confirmation.php               # Xác nhận đơn đặt chỗ, mã định danh, chi tiết lấy bánh và phiếu thu in ấn
├── reservation_lookup.php         # Tra cứu đơn đặt chỗ bảo mật (yêu cầu mã đơn + email/SĐT)
├── how_it_works.php               # Hướng dẫn chi tiết 3 bước giải cứu bánh và câu hỏi thường gặp (FAQ)
├── about.php                      # Sứ mệnh dự án và hồ sơ chi tiết 4 thành viên nhóm sinh viên
├── contact.php                    # Liên hệ ban quản trị và biểu mẫu đăng ký đối tác dành cho tiệm bánh
├── register.php                   # Đăng ký tài khoản thành viên (kiểm tra email, băm mật khẩu Bcrypt)
├── login.php                      # Đăng nhập tài khoản khách hàng kèm trợ giúp tài khoản mẫu
├── logout.php                     # Đăng xuất an toàn và hủy phiên làm việc
├── verify_email.php               # Xử lý kích hoạt tài khoản qua Token xác thực
├── forgot_password.php            # Yêu cầu gửi email khôi phục mật khẩu
├── reset_password.php             # Thiết lập mật khẩu mới bằng Token một lần
├── profile.php                    # Quản lý hồ sơ cá nhân và đổi mật khẩu
├── my_reservations.php            # Lịch sử đơn đặt chỗ của thành viên kèm chức năng hủy đơn hoàn kho
├── feedback.php                   # Biểu mẫu gửi đánh giá xếp hạng sao và danh sách đánh giá công khai
├── terms.php                      # Điều khoản dịch vụ và tiêu chuẩn an toàn thực phẩm
├── privacy.php                    # Chính sách bảo mật thông tin và bảo vệ quyền riêng tư
│
├── admin/                         # BẢNG ĐIỀU KHIỂN QUẢN TRỊ VIÊN (ADMIN DASHBOARD)
│   ├── index.php                  # Dashboard tổng quan: Chỉ số KPI thực tế, biểu đồ xu hướng 7 ngày & tỉ lệ sản phẩm
│   ├── login.php                  # Đăng nhập riêng biệt cho quản trị viên (yêu cầu role = 'admin')
│   ├── logout.php                 # Đăng xuất quản trị viên
│   ├── boxes.php                  # Quản lý hộp bánh: Danh sách, bật/tắt mở bán, xóa/ngừng bán
│   ├── box_form.php               # Biểu mẫu thêm/sửa hộp bánh, quản lý giá và tải lên ảnh sản phẩm
│   ├── reservations.php           # Quản lý đơn đặt chỗ: Bộ lọc trạng thái, tiệm bánh, ngày nhận và xuất file CSV
│   ├── reservation_view.php       # Xem chi tiết đơn hàng, xử lý đổi trạng thái và tự động hoàn trả tồn kho nếu hủy
│   ├── bakeries.php               # Quản lý tiệm bánh đối tác: Địa chỉ, giờ mở/đóng cửa, trạng thái hợp tác
│   ├── bakery_form.php            # Thêm/sửa tiệm bánh và tải lên logo
│   ├── customers.php              # Quản lý thành viên đăng ký và danh sách khách vãng lai
│   ├── feedback.php               # Kiểm duyệt đánh giá khách hàng (Công khai / Ẩn / Xóa)
│   ├── reports.php                # Báo cáo phân tích chuyên sâu: Tỉ lệ hoàn tất, tỉ lệ khách quay lại, xếp hạng bánh
│   └── activity_logs.php          # Nhật ký kiểm toán bảo mật lưu vết mọi thao tác quản trị
│
├── config/                        # TỆP CẤU HÌNH HỆ THỐNG
│   ├── config.php                 # Hằng số ứng dụng, đường dẫn Base URL, định dạng tiền tệ VND, múi giờ
│   ├── database.php               # Khởi tạo kết nối PDO MySQL (utf8mb4, chế độ bắt ngoại lệ)
│   ├── mail.php                   # Cấu hình máy chủ SMTP và đường dẫn tệp nhật ký mail
│   └── payment.php                # Cấu hình cổng thanh toán VNPay Sandbox & MoMo Sandbox
│
├── includes/                      # THÀNH PHẦN TÁI SỬ DỤNG & THƯ VIỆN LÕI
│   ├── auth.php                   # Quản lý phiên làm việc, phân quyền vai trò (Customer vs Admin)
│   ├── csrf.php                   # Sinh mã và kiểm tra mã xác thực CSRF Token
│   ├── functions.php              # Định dạng tiền tệ VND, làm sạch dữ liệu, thông báo Flash, mã đơn hàng
│   ├── mailer.php                 # Dịch vụ gửi email kích hoạt, xác nhận đơn hàng qua SMTP và bộ ghi cục bộ
│   ├── header.php                 # Header khách hàng, thanh điều hướng và thông báo hệ thống
│   ├── footer.php                 # Footer khách hàng, liên kết chính sách và thông tin dự án
│   ├── admin_header.php           # Sidebar và Topbar giao diện quản trị viên
│   └── admin_footer.php           # Footer giao diện quản trị viên
│
├── payments/                      # MÔ-ĐUN TÍCH HỢP THANH TOÁN
│   ├── vnpay_create.php           # Tạo yêu cầu thanh toán VNPay kèm băm mật mã HMAC-SHA512
│   ├── vnpay_return.php           # Xử lý kết quả trả về từ VNPay, kiểm tra chữ ký số và cập nhật đơn hàng
│   ├── momo_create.php            # Khởi tạo yêu cầu thanh toán MoMo Sandbox với chữ ký HMAC-SHA256
│   ├── momo_return.php            # Xử lý kết quả trả về từ MoMo, xác thực chữ ký số
│   └── sandbox_simulator.php      # Trình giả lập Sandbox phục vụ kiểm thử cục bộ (Thành công, Hủy, Giả mạo chữ ký)
│
├── database/                      # CƠ SỞ DỮ LIỆU
│   ├── nomrenom_db.sql            # Script SQL tạo cơ sở dữ liệu hoàn chỉnh, 13 bảng InnoDB và dữ liệu mẫu ban đầu
│   └── schema_erd.md              # Sơ đồ quan hệ thực thể ERD chi tiết bằng Mermaid
│
├── assets/                        # TÀI NGUYÊN TĨNH (CSS, JS, ẢNH)
│   ├── css/
│   │   ├── style.css              # Phong cách nhận diện NomReNom: Cream, White, Dark Green, Typography
│   │   └── admin.css              # Giao diện bảng điều khiển quản trị viên
│   ├── js/
│   │   ├── main.js                # Xử lý tương tác chung, hiệu ứng cuộn và thông báo
│   │   ├── checkout.js            # Tính toán tiền tự động, kiểm soát tồn kho và chọn phương thức thanh toán
│   │   └── admin.js               # Khởi tạo các biểu đồ phân tích thống kê Chart.js
│   └── img/
│       ├── food_boxes/            # Hình ảnh minh họa hộp bánh (Snack Box, Meal Box)
│       ├── bakeries/              # Logo và hình ảnh tiệm bánh đối tác
│       └── team/                  # Hình ảnh đại diện cho 4 thành viên nhóm sinh viên
│
├── storage/                       # THƯ MỤC LƯU TRỮ TỆP NỘI BỘ
│   ├── uploads/                   # Ảnh sản phẩm và logo tiệm bánh được quản trị viên tải lên
│   └── logs/
│       └── mail.log               # Nhật ký email gửi đi và liên kết kích hoạt phục vụ kiểm thử cục bộ
│
└── docs/                          # TÀI LIỆU KỸ THUẬT & HƯỚNG DẪN
    ├── XAMPP_INSTALLATION.md      # Hướng dẫn cài đặt và khởi chạy trên XAMPP
    ├── DATABASE_SETUP.md          # Hướng dẫn cấu hình và giải thích lược đồ cơ sở dữ liệu
    ├── SMTP_SETUP_GUIDE.md        # Hướng dẫn cấu hình SMTP và cơ chế kiểm thử cục bộ
    ├── PAYMENT_GATEWAY_GUIDE.md   # Hướng dẫn kiểm thử cổng thanh toán VNPay và MoMo
    ├── TESTING_CHECKLIST.md       # Bảng kiểm thử toàn diện nghiệm thu đồ án
    ├── DEPLOYMENT_GUIDE.md        # Hướng dẫn triển khai thực tế lên môi trường Production
    ├── TEAM_WORKFLOW.md           # Hướng dẫn phân công nhiệm vụ và luồng làm việc Git nhóm 4 người
    └── PROJECT_STRUCTURE.md       # Bản đồ cấu trúc thư mục toàn dự án
```
