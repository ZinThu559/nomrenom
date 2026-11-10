# Hướng Dẫn Tích Hợp & Kiểm Thử Cổng Thanh Toán VNPay & MoMo Sandbox

Hệ thống NomReNom tích hợp 2 cổng thanh toán trực tuyến hàng đầu Việt Nam (**VNPay** & **MoMo**) theo chuẩn mật mã thương mại điện tử thực tế cùng hình thức **Thanh toán tại quầy (Pay at Pickup)**.

---

## 1. Tệp Cấu Hình Cổng Thanh Toán

Toàn bộ thông tin cấu hình Sandbox được lưu trữ tại:
```
C:\xampp\htdocs\nomrenom\config\payment.php
```

### Thông Số VNPay Sandbox:
- **Terminal ID (`vnp_TmnCode`):** `2QXUI4J4` (Mã thử nghiệm chính thức của VNPay)
- **Secret Key (`vnp_HashSecret`):** `RAIQUIOWGHGUDGUTRHGUDGUT`
- **Thuật toán băm chữ ký:** `HMAC-SHA512`
- **API URL:** `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html`

### Thông Số MoMo Sandbox:
- **Partner Code:** `MOMO_DEMO_PARTNER`
- **Access Key:** `MOMO_DEMO_ACCESS_KEY`
- **Secret Key:** `MOMO_DEMO_SECRET_KEY`
- **Thuật toán băm chữ ký:** `HMAC-SHA256`

---

## 2. Quy Trình Bảo Mật & Xác Thực Chữ Ký Số

Hệ thống tuân thủ nghiêm ngặt nguyên tắc **không tự động ghi nhận thanh toán thành công nếu chưa qua kiểm tra chữ ký số (Checksum Verification)**:

1. Khi khách hàng bấm thanh toán, hệ thống sắp xếp các tham số theo bảng chữ cái alphabet và tính mã băm bằng Secret Key.
2. Khi cổng thanh toán trả kết quả về URL Return (`vnpay_return.php` hoặc `momo_return.php`):
   - Hệ thống tách riêng `vnp_SecureHash` hoặc `signature`.
   - Tính toán lại mã băm trên toàn bộ tham số nhận được bằng `hash_hmac()`.
   - So sánh hai chuỗi băm bằng hàm so sánh an toàn `hash_equals()`.
3. Chỉ khi chữ ký số khớp 100% và mã kết quả là `00` (Thành công), hệ thống mới chuyển `payment_status` sang **'Paid'** và `reservation_status` sang **'Confirmed'**.

---

## 3. Trình Giả Lập Sandbox Cục Bộ (Local Sandbox Simulator)

Vì môi trường XAMPP trên máy tính cá nhân (`http://localhost/`) không thể nhận trực tiếp Webhook từ máy chủ VNPay/MoMo qua mạng Internet công cộng, NomReNom cung cấp công cụ:
```
http://localhost/nomrenom/payments/sandbox_simulator.php?code=MÃ_ĐƠN
```

Công cụ này cho phép bạn thử nghiệm 3 kịch bản thực tế:
1. **Mô phỏng Thanh Toán Thành Công:** Tính toán chữ ký HMAC-SHA512 chuẩn xác, trả về mã `00` và cập nhật đơn hàng thành công kèm gửi email.
2. **Mô phỏng Khách Hàng Hủy:** Trả về mã lỗi `24`, cập nhật trạng thái thanh toán sang `Failed` nhưng bảo toàn tồn kho để khách có thể thanh toán lại.
3. **Mô phỏng Lỗi Chữ Ký (Tampered Signature):** Cố tình can thiệp sai chữ ký số để kiểm tra cơ chế phòng vệ chống gian lận tài chính của hệ thống.
