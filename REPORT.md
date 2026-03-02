# 📋 BÁO CÁO REVIEW CODE - Server Health Monitor

> **Ngày review:** 2026-03-02  
> **Dự án:** Server Health Monitor (Laravel 10)  
> **Mục đích:** Hệ thống giám sát tài nguyên máy chủ (CPU, RAM, Disk) với phát hiện bất thường tự động

---

## 1. TỔNG QUAN DỰ ÁN

| Thành phần | Số lượng | Trạng thái |
|---|---|---|
| Controllers | 1 chính + 1 base | ✅ Hoạt động |
| Models | 1 (User) | ✅ Chuẩn Laravel |
| Routes | 2 (1 web, 1 API) | ✅ Đơn giản |
| Migrations | 4 (mặc định) | ✅ Chuẩn Laravel |
| Views (Blade) | 2 files | ✅ Giao diện đẹp |
| Tests | 2 example tests | ⚠️ Thiếu test nghiệp vụ |
| Middleware | 9 (mặc định) | ✅ Đầy đủ |
| Config files | 15 | ✅ Chuẩn Laravel |

---

## 2. BẢNG THỐNG KÊ CÁC VẤN ĐỀ VÀ GIẢI PHÁP

### 🔴 Vấn đề nghiêm trọng (Critical)

| # | Vấn đề | File | Dòng | Mô tả chi tiết | Giải pháp đề xuất | Trạng thái |
|---|---|---|---|---|---|---|
| 1 | Thiếu error handling cho `file_get_contents("/proc/meminfo")` | `ServerMonitorController.php` | 16 | Hàm đọc file `/proc/meminfo` không có try-catch. Nếu file không tồn tại (Windows/macOS), ứng dụng sẽ crash hoàn toàn | Bọc trong try-catch, dùng `@` suppression, kiểm tra kết quả `!== false` trước khi xử lý | ✅ Đã sửa |
| 2 | Thiếu validation cho kết quả `preg_match` | `ServerMonitorController.php` | 17-18 | Truy cập `$totalMatches[1]` và `$availableMatches[1]` mà không kiểm tra regex match thành công hay không. Nếu format `/proc/meminfo` thay đổi → lỗi `Undefined array key` | Kiểm tra giá trị trả về của `preg_match()` trước khi truy cập kết quả | ✅ Đã sửa |
| 3 | `sys_getloadavg()` không có kiểm tra lỗi | `ServerMonitorController.php` | 27-28 | Hàm này trả về `false` trên Windows. Code truy cập `$load[0]` mà không kiểm tra → crash trên Windows | Kiểm tra `$load !== false` trước khi sử dụng | ✅ Đã sửa |
| 4 | `disk_total_space(".")` / `disk_free_space(".")` có thể trả về `false` | `ServerMonitorController.php` | 31-32 | Dùng `"."` (thư mục hiện tại) thay vì `"/"` (root). Cả hai hàm đều có thể trả về `false` nếu lỗi → chia cho 0 hoặc lỗi type | Đổi sang `"/"` cho root filesystem, kiểm tra giá trị trả về `!== false` và `> 0` | ✅ Đã sửa |

### 🟡 Vấn đề logic/chức năng (Medium)

| # | Vấn đề | File | Dòng | Mô tả chi tiết | Giải pháp đề xuất | Trạng thái |
|---|---|---|---|---|---|---|
| 5 | CPU Load tính sai - nhân trực tiếp `load_avg * 100` | `ServerMonitorController.php` | 28 | `sys_getloadavg()` trả về load average (vd: 0.5, 1.0, 2.0), KHÔNG phải phần trăm. Trên máy 4 nhân, load 4.0 = 100%. Nhân trực tiếp `* 100` cho kết quả sai (vd: load 0.5 → hiển thị 50% thay vì 12.5% trên 4 nhân) | Chia load average cho số CPU cores rồi mới nhân 100: `($load[0] / $cpuCores) * 100` | ✅ Đã sửa |
| 6 | Ngưỡng cảnh báo hardcoded | `ServerMonitorController.php` | 42, 47, 52 | RAM 80%, Disk 90%, CPU 80% cố định trong code. Không thể thay đổi mà không sửa code | Tạo file `config/monitoring.php` với giá trị từ `.env` | ✅ Đã sửa |
| 7 | Truy cập trực tiếp `$_SERVER['SERVER_ADDR']` trong Blade | `monitor.blade.php` | 91 | Vi phạm nguyên tắc MVC: View không nên truy cập trực tiếp superglobals. Có thể gây vấn đề bảo mật (XSS nếu dùng `{!! !!}`) | Truyền `server_ip` từ Controller qua `request()->server()` | ✅ Đã sửa |

### 🟢 Vấn đề nhỏ / Cải thiện (Low)

| # | Vấn đề | File | Dòng | Mô tả chi tiết | Giải pháp đề xuất | Trạng thái |
|---|---|---|---|---|---|---|
| 8 | Không có test cho nghiệp vụ chính | `tests/` | - | Chỉ có 2 example test (1 trivial `true === true`, 1 basic route test). Không có test nào cho logic monitoring hoặc detection | Thêm Feature test cho `ServerMonitorController` kiểm tra view data, kiểu dữ liệu, phạm vi giá trị | ✅ Đã sửa |
| 9 | Auto-refresh bằng meta tag thay vì AJAX | `monitor.blade.php` | 9 | `<meta http-equiv="refresh" content="5">` reload toàn bộ trang mỗi 5 giây, gây nhấp nháy và lãng phí bandwidth | Dùng AJAX/Fetch API polling hoặc WebSocket để cập nhật chỉ phần dữ liệu thay đổi | ℹ️ Ghi nhận |
| 10 | Route không có tên (name) | `web.php` | 8 | `Route::get('/', ...)` không có `->name('dashboard')`, khó reference trong code | Thêm `->name('monitor.index')` | ℹ️ Ghi nhận |
| 11 | Dependencies không sử dụng | `composer.json` | 9 | `guzzlehttp/guzzle` được import nhưng không dùng trong code | Xóa dependency không cần thiết để giảm kích thước | ℹ️ Ghi nhận |
| 12 | DatabaseSeeder rỗng | `database/seeders/` | - | Seeder mặc định, không có dữ liệu mẫu | Có thể thêm user mẫu cho testing | ℹ️ Ghi nhận |
| 13 | Chưa có API endpoint cho monitoring data | `routes/api.php` | - | Chỉ có 1 API route `/api/user` (mặc định). Không có API trả JSON cho monitoring data | Thêm route API trả dữ liệu JSON cho tích hợp bên ngoài | ℹ️ Ghi nhận |

---

## 3. CÁC TIÊU CHUẨN BẢO MẬT

| # | Tiêu chuẩn | Trạng thái | Ghi chú |
|---|---|---|---|
| 1 | CSRF Protection | ✅ Đạt | Middleware `VerifyCsrfToken` được bật cho web routes |
| 2 | XSS Prevention | ✅ Đạt | Blade dùng `{{ }}` (escaped output) cho tất cả biến |
| 3 | SQL Injection | ✅ Đạt | Không có raw query, dùng Eloquent ORM |
| 4 | API Authentication | ✅ Đạt | Route `/api/user` được bảo vệ bằng Sanctum middleware |
| 5 | Rate Limiting | ✅ Đạt | API có throttle 60 requests/phút |
| 6 | Input Validation | ✅ Đạt | Không có user input (chỉ đọc system metrics) |
| 7 | Error Handling | ✅ Đã sửa | Đã thêm try-catch và validation cho system calls |
| 8 | Superglobals trong View | ✅ Đã sửa | Đã chuyển `$_SERVER` access sang Controller |
| 9 | Debug Mode | ⚠️ Cảnh báo | `.env.example` có `APP_DEBUG=true` - cần tắt trong production |
| 10 | Sensitive Data Exposure | ⚠️ Cảnh báo | Hiển thị Server IP trên giao diện - có thể là rủi ro bảo mật |

---

## 4. TÓM TẮT

### Đã sửa (8 vấn đề):
- ✅ Error handling cho `file_get_contents("/proc/meminfo")`
- ✅ Validation cho kết quả `preg_match`
- ✅ Kiểm tra `sys_getloadavg()` trả về false
- ✅ Kiểm tra `disk_total_space()` / `disk_free_space()` trả về false
- ✅ Sửa công thức tính CPU Load (chia cho số cores)
- ✅ Đưa ngưỡng cảnh báo vào config (`config/monitoring.php`)
- ✅ Di chuyển `$_SERVER` access từ View sang Controller
- ✅ Thêm 9 test cases cho ServerMonitorController

### Ghi nhận để cải thiện (5 đề xuất):
- ℹ️ Chuyển auto-refresh sang AJAX polling
- ℹ️ Thêm tên cho routes
- ℹ️ Xóa dependencies không sử dụng
- ℹ️ Thêm API endpoint trả JSON cho monitoring data
- ℹ️ Thêm database seeder

### Đánh giá tổng thể:
> Dự án **hoạt động được** với mục đích giám sát server cơ bản. Sau khi sửa các lỗi nghiêm trọng (error handling, logic CPU), code đã đạt chất lượng tốt hơn đáng kể. Tuy nhiên vẫn còn một số điểm có thể cải thiện thêm cho production-ready.
