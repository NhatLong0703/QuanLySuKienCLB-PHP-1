# Hệ thống quản lý sự kiện / câu lạc bộ sinh viên

## 1. Thành viên nhóm
| MSV | Họ và tên | Vai trò | Nhiệm vụ |
| --- | --- | --- | --- |
| 224001778 | Đặng Quang Doanh (Nhóm trưởng) | Project Manager | Thiết kế database, quản lý dự án, triển khai tính năng, phân tích nghiệp vụ |
| 224001811 | Nguyễn Nhật Long | Backend | Code backend, test tính năng |
| 224001829 | Cao Bá Sơn | Frontend | UI, Figma, code frontend |
| 224001815 | Nguyễn Đức Minh | Backend | Code backend, test API |
| 224001775 | Dương Thị Chi | Frontend | UI, Figma, code frontend |
| 223001676 | Nguyễn Phương Thủy | QA / Documentation | Viết báo cáo, Figma, test dự án |

## 2. Mô tả bài toán
Xây dựng cổng thông tin cho câu lạc bộ hoặc khoa để công bố sự kiện, nhận đăng ký, điểm danh và thống kê người tham gia.

### Người dùng mục tiêu
- **Khách / Thành viên**: Xem danh sách sự kiện, lọc theo ngày/câu lạc bộ, xem chi tiết, đăng ký hoặc hủy đăng ký sự kiện.
- **Ban tổ chức (BTC)**: Tạo sự kiện, quản lý đăng ký, đóng mở đăng ký, điểm danh người tham gia và xem danh sách tham gia.
- **Quản trị viên (Admin)**: Quản lý câu lạc bộ, tài khoản người dùng, toàn bộ sự kiện và thống kê hệ thống.

### Luồng nghiệp vụ chính
1. **Khách / Thành viên**: Duyệt danh sách sự kiện (lọc theo ngày/câu lạc bộ, trạng thái) -> Xem chi tiết -> Đăng ký tham gia (nếu còn chỗ/trong hạn) -> Hủy đăng ký trước hạn.

---

Đồ án (Bài tập lớn) mở rộng trên nền tảng kết nối sinh viên với các Câu lạc bộ (CLB) trong trường Đại học/Cao đẳng. Hệ thống giúp sinh viên dễ dàng khám phá, tham gia CLB, đăng ký các sự kiện, đồng thời giúp Ban quản lý và Nhà trường kiểm soát, thống kê các hoạt động ngoại khóa một cách chuyên nghiệp.

---

## 💻 Công nghệ & Kiến trúc
- **Backend:** PHP thuần (Native PHP) sử dụng PDO để thao tác với cơ sở dữ liệu.
- **Frontend:** HTML5, CSS3 thuần, JavaScript (Sử dụng Fetch API để gọi AJAX) theo phong cách thiết kế hiện đại, responsive. Sử dụng font chữ **Inter** đồng nhất trên toàn hệ thống.
- **Database:** MySQL (Sử dụng Engine InnoDB với các ràng buộc khóa ngoại Foreign Keys).
- **Architecture:** Thiết kế theo mô hình **MVC** (Model-View-Controller) đơn giản kết hợp với **Repository Pattern** để tách biệt logic truy vấn CSDL. Có Router xử lý URL.

---

## 👥 Các Role (Phân quyền) và Chức năng

Hệ thống bao gồm 3 phân quyền (Role) với các tính năng chuyên biệt và luồng bảo mật nghiêm ngặt.

### 1. Admin (Quản trị viên toàn hệ thống)
*Role cao nhất, có khả năng quản lý dữ liệu toàn trường và theo dõi mọi hoạt động.*
- **Dashboard:** Thống kê tổng quan số lượng CLB, Sự kiện, Vé đăng ký, và Doanh thu giả lập toàn trường.
- **Quản lý Câu lạc bộ:** Xem toàn bộ CLB, Thêm mới, Sửa, Xóa.
- **Phân công Nhân sự (Nổi bật):** Cấp quyền hoặc tước quyền Quản lý CLB cho sinh viên. (Hệ thống có logic **Tự động thăng cấp / Giáng cấp** từ Member ↔ Organizer dựa trên quyền quản lý).
- **Quản lý Sự kiện:** Xem và quản lý toàn bộ các sự kiện do tất cả các CLB tạo ra.
- **Nhật ký hệ thống (Audit Logs):** Theo dõi lịch sử thao tác (Ai đã tạo/sửa/xóa cái gì, vào lúc nào) được phân trang rõ ràng.

### 2. Organizer (Ban quản lý Câu lạc bộ)
*Dành cho Ban chủ nhiệm/Core team của CLB. Dữ liệu trên Dashboard của role này được **cá nhân hóa hoàn toàn**, chỉ hiển thị các CLB và Sự kiện thuộc quyền quản lý.*
- **Quản lý CLB của mình:** Xem thông tin, chỉnh sửa giới thiệu, upload ảnh đại diện CLB.
- **Quản lý Thành viên:** Xem danh sách người xin gia nhập, tiến hành phê duyệt (Approve) hoặc Từ chối (Reject), hoặc Xóa thành viên cũ.
- **Tổ chức Sự kiện:** Tạo mới sự kiện (có giới hạn số lượng vé/capacity, thời gian), chỉnh sửa, hủy sự kiện.
- **Quản lý Đăng ký & Điểm danh:** Kiểm soát danh sách sinh viên đã mua vé/đăng ký, tiến hành Điểm danh (Attendance) tại cửa.

### 3. Member (Sinh viên thông thường)
*Dành cho toàn bộ sinh viên trong trường.*
- **Khám phá CLB:** Xem danh sách tất cả CLB trong trường, bấm **Xin gia nhập** hoặc **Rời CLB**.
- **Xem thông tin minh bạch:** Có thể xem được ai đang làm **👑 Quản lý** và ai là **Thành viên** của một CLB.
- **Tham gia Sự kiện:** Xem lịch sự kiện, Đăng ký tham gia (Lấy vé). Hệ thống không cho đăng ký nếu đã quá hạn hoặc hết sức chứa (Capacity).
- **Cổng thông tin cá nhân:** Xem thông báo hệ thống, lịch sử các sự kiện đã tham gia, và để lại Đánh giá (Feedback/Rate sao) cho sự kiện.

---

## 🔄 Luồng Dữ liệu (Data Flows) Nổi Bật

1. **Luồng Gia nhập & Rời Câu lạc bộ:**
   - Member gửi yêu cầu ➔ Bảng `club_members` sinh ra một dòng với trạng thái `pending`.
   - Organizer nhận được thông báo ➔ Bấm Duyệt ➔ Cập nhật trạng thái thành `approved`.
   - Member trở thành thành viên chính thức, có quyền xem các sự kiện nội bộ (nếu có mở rộng). Member có quyền bấm Rời CLB (Xóa bản ghi).

2. **Luồng Vòng đời Sự kiện (Event Lifecycle):**
   - Organizer tạo Sự kiện mới (Bảng `events`).
   - Member lướt xem và Đăng ký (Ghi vào bảng `registrations`).
   - Khi Sự kiện diễn ra, Organizer cầm thiết bị check-in ➔ Ghi dữ liệu vào bảng `attendance`.
   - Sau sự kiện, Member để lại bình luận và đánh giá (Bảng `event_feedbacks`).

3. **Luồng Tự động Phân quyền (Auto Role Management):**
   - Admin vào trang Clubs, chọn 1 Member và bấm **"Phân công Quản lý"**.
   - Bảng `club_managers` được insert dữ liệu.
   - Code Backend tự động nhận diện tài khoản này đang là `member` và tiến hành `UPDATE users SET role = 'organizer'`. Tài khoản này ngay lập tức có quyền truy cập trang quản lý.
   - Khi Admin bấm **"Hủy phân công"**, hệ thống kiểm tra nếu tài khoản này không còn quản lý CLB nào khác, sẽ tự động trả role về `member`.

---

## 🗄️ Cấu trúc Cơ sở dữ liệu (Database Schema)

Hệ thống gồm **10 bảng chính** liên kết với nhau bằng Foreign Key:

1. `users`: Tài khoản sinh viên/admin (Họ tên, Email, Mật khẩu, Role).
2. `clubs`: Thông tin Câu lạc bộ (Tên, Ảnh, Mô tả).
3. `club_managers`: Bảng trung gian gán User làm Quản lý cho Club.
4. `club_members`: Bảng trung gian gán User làm Thành viên của Club (kèm trạng thái duyệt).
5. `events`: Thông tin sự kiện (Tên, Thời gian, Sức chứa, Địa điểm).
6. `registrations`: Vé đăng ký sự kiện của User.
7. `attendance`: Bảng lưu trữ lịch sử check-in sự kiện của User.
8. `notifications`: Hệ thống thông báo gửi tới User.
9. `audit_logs`: Bảng lưu vết (Log) các hành động quan trọng do Admin/Organizer thực hiện.
10. `event_feedbacks`: Review, đánh giá số sao của sinh viên sau sự kiện.

*Lưu ý: Mọi liên kết khóa ngoại đều sử dụng cơ chế `ON DELETE CASCADE`. Ví dụ, nếu Xóa 1 Câu lạc bộ, toàn bộ Thành viên, Sự kiện, Vé đăng ký của CLB đó cũng sẽ tự động bị xóa theo để dọn dẹp bộ nhớ.*

---

## 🛠️ Hướng dẫn Cài đặt & Khởi chạy dự án (Setup)

**Bước 1: Clone dự án hoặc tải mã nguồn về máy**
Giải nén dự án vào thư mục `htdocs` (nếu dùng XAMPP) hoặc thư mục làm việc của bạn.

**Bước 2: Cài đặt Cơ sở dữ liệu (Database)**
1. Khởi động MySQL (qua XAMPP, Laragon, v.v.).
2. Tạo một database mới (ví dụ: `clubhub_db`).
3. Mở phpMyAdmin hoặc DBeaver, import file SQL khởi tạo (nếu có) để tạo bảng và đổ dữ liệu mẫu.
4. Mở file `database/Database.php` (hoặc cấu hình kết nối DB tương đương) và chỉnh sửa thông tin cho đúng với Localhost của bạn:
   ```php
   $host = '127.0.0.1';
   $db   = 'clubhub_db'; // Tên database của bạn
   $user = 'root';
   $pass = '';
   ```

**Bước 3: Khởi chạy máy chủ PHP (Local Server)**
Mở Terminal / Command Prompt tại thư mục gốc của dự án và chạy lệnh sau (hoặc cấu hình vhost trên XAMPP trỏ vào thư mục `public`):
```bash
php -S localhost:8080 -t public
```

**Bước 4: Truy cập ứng dụng**
- Mở trình duyệt và truy cập: `http://localhost:8080`
- Trình duyệt sẽ tự động điều hướng đến các trang Giao diện.

---

## 🔑 Danh sách Tài khoản Test (Mẫu)
Để thuận tiện cho Giảng viên chấm bài, dưới đây là các tài khoản mặc định (Nên tạo ra bằng script seed nếu cần):

- **Tài khoản Admin:**
  - Email: `admin@example.com`
  - Mật khẩu: `123456`
- **Tài khoản Organizer:**
  - Email: `organizer@example.com`
  - Mật khẩu: `123456`
- **Tài khoản Member (Sinh viên):**
  - Email: `member@example.com`
  - Mật khẩu: `123456`

---

## 📂 Cấu trúc Thư mục (Folder Structure)
Dự án được xây dựng theo chuẩn MVC tự định nghĩa (Custom MVC), dễ dàng quản lý code:

```text
QuanLySuKienCLB-PHP/
├── controllers/       # (Controller) Chứa logic xử lý các HTTP Request, Phân quyền API.
├── database/          # Cấu hình kết nối MySQL và file SQL Seed (Tạo dữ liệu mẫu).
├── models/            # (Model) Các Class đối tượng, biểu diễn thực thể.
├── repositories/      # (Repository) Tách biệt các câu lệnh SQL INSERT, UPDATE, SELECT.
├── public/            # (Web Root) Document root để chạy Web.
│   ├── assets/        # Chứa CSS, hình ảnh, JavaScript chung.
│   ├── uploads/       # Chứa file/ảnh người dùng tải lên.
│   ├── views/         # (View) Chứa các file HTML tĩnh giao diện cho từng Role (admin, organizer, member).
│   └── index.php      # Router trung tâm. Định tuyến mọi Request API tới Controller.
└── README.md          # Tài liệu báo cáo dự án.
```

---

## 🧪 Các Kịch Bản Kiểm Thử (Test Cases Đã Vượt Qua)

Quá trình phát triển đã trải qua quá trình kiểm thử kỹ lưỡng. Dưới đây là các Test Case tiêu biểu đã được test thành công:

| ID | Tên chức năng / Kịch bản | Kịch bản kiểm thử (Action) | Kết quả mong đợi (Expected) | Trạng thái |
|---|---|---|---|:---:|
| **TC01** | **Bảo mật phân quyền (Access Control)** | Đăng nhập bằng tài khoản `Member` và cố gắng truy cập URL trang Quản trị của Admin (`/views/admin/dashboard.html`). | Hệ thống nhận diện sai Role, chặn truy cập và chuyển hướng hoặc báo lỗi 403 Forbidden. | ✅ Pass |
| **TC02** | **Logic Phân công Quản lý (Auto-Upgrade Role)** | Admin chỉ định một sinh viên (`Member`) làm Quản lý cho 1 Câu lạc bộ. | Hệ thống lưu dữ liệu quản lý, đồng thời tự động thăng cấp role của sinh viên đó lên thành `Organizer`. | ✅ Pass |
| **TC03** | **Logic Hủy quyền Quản lý (Auto-Downgrade Role)** | Admin hủy phân công CLB cuối cùng mà một Organizer đang quản lý. | Hệ thống xóa dữ liệu quản lý, kiểm tra thấy người này không còn quản lý CLB nào, tự động hạ role về `Member`. | ✅ Pass |
| **TC04** | **Tính riêng tư dữ liệu của Organizer** | Đăng nhập tài khoản Organizer A (được giao quản lý CLB IT). | Trên Dashboard và các trang Quản lý chỉ tải ra danh sách thành viên và sự kiện của riêng CLB IT. Không nhìn thấy CLB khác. | ✅ Pass |
| **TC05** | **Ngăn thao tác trái phép từ Frontend** | Đăng nhập Organizer A, dùng Postman hoặc Console để gửi API lệnh xóa Sự kiện của CLB B. | Backend (EventController) nhận lệnh nhưng kiểm tra quyền sở hữu, từ chối thực hiện vì Organizer A không quản lý CLB B. | ✅ Pass |
| **TC06** | **Giới hạn số lượng vé sự kiện (Capacity)** | Sự kiện có Capacity = 100. Người thứ 101 bấm đăng ký. | API trả về lỗi "Sự kiện đã hết chỗ", không insert thêm vào database. | ✅ Pass |
| **TC07** | **Xóa dữ liệu phân tầng (Cascade Delete)** | Admin tiến hành Xóa 1 Câu lạc bộ khỏi hệ thống. | Toàn bộ Sự kiện, Danh sách thành viên, Lịch sử đăng ký vé thuộc về CLB đó đều tự động biến mất khỏi CSDL. | ✅ Pass |
