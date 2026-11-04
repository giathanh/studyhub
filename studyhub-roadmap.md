# StudyHub — Kế hoạch Laravel + Vue.js từ local đến AWS

## 1. Mục tiêu và phạm vi

Xây dựng ứng dụng quản lý việc học và ghi chú cá nhân, dùng chính quá trình học AWS làm dữ liệu thực tế.

Thời lượng dự kiến: **8 tuần, 6–10 giờ/tuần**. Hoàn thành theo tiêu chí của từng chặng, không bắt buộc theo lịch cố định.

Trạng thái: **kế hoạch, chưa triển khai hoặc kiểm chứng**. Các đường dẫn dưới đây là vị trí dự kiến; không có nghĩa ứng dụng đã tồn tại.

### MVP

| Chức năng | Nội dung |
| --- | --- |
| Tài khoản | Đăng nhập, đăng xuất, đổi mật khẩu |
| Khóa học | Tạo, sửa, xóa khóa học; ngày mục tiêu; trạng thái |
| Bài học | Chia khóa học thành bài; sắp xếp; cập nhật trạng thái |
| Ghi chú | Ghi kiến thức, đoạn lệnh và liên kết; tìm kiếm, lọc |
| Dashboard | Tiến độ khóa học, bài chưa hoàn thành, ghi chú gần đây |

Sau MVP: nhật ký thời gian học, tệp đính kèm, email tổng kết tuần. Chia sẻ nhóm, AI tóm tắt, Pomodoro và đồng bộ thời gian thực là phần mở rộng tùy chọn.

### Stack và vị trí mã nguồn

- Laravel + Vue 3 + Inertia.js + TypeScript.
- Laravel Fortify cho xác thực; giao diện tự viết bằng Vue và Tailwind CSS.
- MySQL ở local và AWS; test chạy trên SQLite in-memory.
- Laravel Policies, Filesystem, Queue và Scheduler.
- Local dùng **Lerd**; không dựng thêm Docker Compose cho database hoặc chạy PHP development server song song.
- Ứng dụng dự kiến: `examples/studyhub/`.
- Hạ tầng dự kiến: `infra/studyhub/`.
- Tài liệu vận hành và bằng chứng kiểm tra được bổ sung cùng từng chặng.

Chọn phiên bản Laravel ổn định tương thích với PHP mà Lerd cung cấp tại thời điểm khởi tạo. Ghi phiên bản đã chọn và commit lockfile; không suy đoán phiên bản từ kế hoạch này.

```text
Trình duyệt / Vue
      ↓
Laravel routes → Controller → Policy → MySQL
      ↓
Inertia trả dữ liệu để Vue hiển thị
```

Frontend build thành tài nguyên tĩnh, phục vụ cùng Laravel. Chưa cần tách Vue thành ứng dụng riêng hoặc bổ sung API cho mobile.

## 2. Tuần 1 — Chuẩn bị local bằng Lerd

### Các bước

1. Qua Lerd MCP, gọi `site` với `action: list` trước để khám phá site hiện có.
2. Đọc `diag` với `action: status` để kiểm tra môi trường và DNS. Lấy domain thực tế từ kết quả, không mặc định `.test`; không bật TLS khi DNS managed bị tắt.
3. Kiểm tra phiên bản PHP, Node và framework scaffolding mà Lerd hỗ trợ.
4. Khởi tạo ứng dụng tại `examples/studyhub/` bằng `framework` / `project_new` nếu definition hỗ trợ lựa chọn mong muốn.
5. Cài Laravel Fortify, tự viết trang xác thực bằng Vue + TypeScript; framework test là PHPUnit.
6. Đăng ký site qua `site` / `link`; đọc kết quả và cảnh báo domain.
7. Chọn MySQL qua `db` / `set`, rồi chạy `env` / `setup`, sau đó **luôn chạy `framework` / `setup`** để thực hiện migration và các bước hậu cài đặt.
8. Để Lerd cấu hình biến kết nối dịch vụ; không sửa tay `.env` để nối database. Nếu có `env_provider`, giữ secrets trong provider thay vì chép vào `.env`.
9. Xác định package manager từ `packageManager` và lockfile. Dùng đúng công cụ và chế độ cài đặt tương ứng; không mặc định npm.
10. Gọi `worker` / `list`, rồi bật worker Vite được khai báo. Truy cập domain mà Lerd báo.
11. Viết README hướng dẫn tái tạo local; commit mã nguồn và lockfile, không commit secrets hoặc dependency đã cài.

Nếu Lerd yêu cầu tải image, thông báo image và kích thước cho người dùng trước khi xác nhận tải. Nếu Lerd chưa chạy, hướng dẫn người dùng chạy `lerd start`.

### Tiêu chí hoàn thành

- [ ] Trang ứng dụng và HMR hoạt động qua domain Lerd.
- [ ] Đăng ký, đăng nhập, đăng xuất được.
- [ ] Dữ liệu tài khoản còn sau khi khởi động lại.
- [ ] README ghi đúng phiên bản, package manager và quy trình setup.

## 3. Tuần 2 — Dữ liệu và CRUD khóa học

### Schema ban đầu

| Bảng | Trường chính |
| --- | --- |
| `users` | Thông tin tài khoản |
| `courses` | `user_id`, `title`, `description`, `status`, `target_date` |
| `lessons` | `course_id`, `section` nullable, `title`, `content` nullable (Markdown), `status`, `position`, `completed_at` |
| `notes` | `user_id`, `course_id` nullable, `lesson_id` nullable, `title`, `body` |

Ghi chú có thể độc lập, thuộc khóa học hoặc gắn với bài học. Nếu có cả `course_id` và `lesson_id`, backend phải kiểm tra bài học thuộc đúng khóa. Mọi quan hệ được gửi từ client đều phải được kiểm tra quyền sở hữu.

### Tạo nội dung: thủ công và import Markdown

Cả hai cách ghi vào cùng bảng `courses` và `lessons`; sau khi import, bài học sửa được như bài tạo tay.

| Đầu vào | Kết quả |
| --- | --- |
| Nhiều file hoặc một thư mục | Mỗi file là một bài; H1 là tiêu đề bài; thư mục cấp 1 là `section`; thứ tự theo đường dẫn |
| Một file | H1 là tên khóa; phần trước H2 đầu tiên là mô tả; mỗi H2 là một bài |

- Trình duyệt đọc file và gửi text dạng JSON, vì `max_file_uploads` của PHP mặc định là 20 và sẽ cắt bớt thư mục lớn.
- Heading trong code fence không được tính là heading.
- Import luôn tạo khóa mới, chưa đồng bộ lại khi file gốc thay đổi.

Tuần 2 làm trước phần tạo, sửa, xóa bài học vì nội dung khóa cần chỗ nhập tay. Sắp xếp, đổi trạng thái và hiển thị Markdown vẫn thuộc Tuần 3.

### Các bước

1. Tạo migration, model, relationship, factory và seeder.
2. Seed khóa “AWS cơ bản” với các bài IAM, VPC, EC2 và S3.
3. Xây CRUD khóa học bằng routes, controller và Vue pages.
4. Validation bằng Form Request; phân quyền bằng Policy.
5. Hiển thị lỗi form, thông báo thành công, trạng thái rỗng và phân trang.
6. Hành vi xóa khóa học: xóa hẳn, bài học bị xóa theo, ghi chú được giữ lại và bỏ liên kết (`nullOnDelete`). Không dùng soft delete.
7. Kiểm tra với hai tài khoản, bao gồm truy cập trực tiếp ID của nhau.

### Tiêu chí hoàn thành

- [x] Người dùng tạo, sửa, xóa và liệt kê khóa học của mình.
- [x] Không đọc hoặc sửa được khóa học của người khác.
- [x] Dữ liệu sai bị từ chối ở backend.
- [x] Tạo được nội dung khóa học thủ công và bằng import Markdown.

## 4. Tuần 3 — Bài học, ghi chú và tìm kiếm

### Các bước

1. Xây CRUD và sắp xếp bài học trong khóa.
2. Hỗ trợ trạng thái chưa học, đang học, hoàn thành; cập nhật `completed_at` tương ứng.
3. Tính tiến độ từ số bài hoàn thành; quy ước khóa chưa có bài có tiến độ 0%.
4. Xây CRUD ghi chú, bắt đầu bằng textarea Markdown.
5. Thêm preview Markdown; vô hiệu hóa HTML thô và xử lý liên kết không an toàn.
6. Tìm theo tiêu đề và nội dung, lọc theo khóa học, có phân trang. Chưa cần dịch vụ tìm kiếm riêng.
7. Thêm xác nhận xóa và cảnh báo khi rời form có thay đổi chưa lưu.

### Màn hình chính

```text
/dashboard
/courses
/courses/{course}
/notes
/notes/{note}
```

### Tiêu chí hoàn thành

- [x] Học một bài AWS thật và lưu ghi chú bằng ứng dụng.
- [x] Tìm lại ghi chú và đổi trạng thái bài học được.
- [x] Backend từ chối liên kết ghi chú với bài hoặc khóa của người khác.
- [x] Nội dung Markdown không thực thi script.

## 5. Tuần 4 — Dashboard và bản dùng hằng ngày

### Các bước

1. Xây dashboard: khóa đang học, bài chưa hoàn thành, tiến độ và ghi chú gần đây.
2. Thêm `study_sessions`: chủ sở hữu, ngày học, số phút và bài liên quan tùy chọn.
3. Nhập thời lượng thủ công; tổng hợp theo tuần.
4. Kiểm tra xác thực, phân quyền giữa tài khoản, validation và tính tiến độ.
5. Kiểm tra UI trên màn hình nhỏ, trạng thái rỗng và lỗi thao tác.
6. Thiết lập CI cho test, định dạng code, kiểm tra kiểu theo script hiện có và build frontend.
7. Dùng thử vài ngày, sửa vấn đề thực tế và tạo mốc `v0.1.0`.

Với tooling PHP local, khám phá `exec` / `vendor_bins` trước rồi chạy bằng `vendor_run`. Sau thay đổi request hoặc truy vấn, dùng capture của Lerd, gọi route liên quan và kiểm tra `optimize_route`. Sau browser test, lấy `X-Lerd-Rid` và kiểm tra request lenses nếu công cụ capture khả dụng; ghi rõ phần chưa kiểm chứng nếu không có.

### Tiêu chí hoàn thành

- [x] Dashboard hiển thị đúng dữ liệu của người đăng nhập (feature test và HTTP local).
- [ ] Các luồng quan trọng có kiểm tra phù hợp và CI chạy thành công.
- [x] Không có N+1 mới trong các route đã kiểm chứng (`/dashboard`, `/study-sessions`, tìm bài IAM; request lenses không có issues).
- [ ] Có phiên bản MVP dùng được ở local.

### Kết quả triển khai ngày 04/11/2026

- Đã thêm dashboard có phân trang: khóa đang học, tiến độ, bài chưa hoàn thành, ghi chú gần đây và tổng phút học tuần hiện tại (thứ Hai–Chủ nhật).
- Đã thêm `study_sessions`, nhập ngày và thời lượng thủ công (1–1.440 phút), bài liên quan tùy chọn, lịch sử phân trang và xóa có xác nhận. Bài bị xóa thì giữ nhật ký và bỏ liên kết.
- Backend kiểm tra quyền sở hữu bài học, không cho client chọn chủ sở hữu nhật ký; migration đã chạy trên MySQL local.
- Đã thêm `.github/workflows/ci.yml`: PHP 8.5, Node từ `.node-version`, PHPUnit, Pint, TypeScript, build và test Markdown; YAML hợp lệ. Chưa push hoặc chạy workflow trên GitHub.
- Đã kiểm tra local: 95 PHPUnit tests / 629 assertions, 3 test Markdown, Pint, `npm run types:check`, `npm run build`, `git diff --check` đều qua.
- HTTP local trả 200; request lenses sạch: dashboard `0065f6e20c3e2556207`, nhật ký `0065f6e20c4aa989a1`, tìm bài `0065f6e20c55d3f8a1`. `optimize_route` không báo route chậm.
- Đã sửa lỗi SSR 404 được capture phát hiện bằng cách tắt SSR trong `config/inertia.php`, phù hợp ứng dụng hiện chỉ cấu hình client rendering.
- Chưa kiểm chứng giao diện desktop/mobile: công cụ trình duyệt báo `The browser access host is unavailable`. Chưa chạy visual smoke/state coverage scripts vì không có phiên trình duyệt kiểm tra; đã rà trạng thái rỗng, validation và submitting trong mã nguồn.
- Build có cảnh báo không chặn: thiếu optional `fontaine`; Lerd không đồng bộ được global wrapper `uiux-skills` trong sandbox (`operation not permitted`). Không thêm dependency.
- Còn chờ chạy CI trên GitHub, kiểm tra UI trực tiếp và dùng thử vài ngày. Chưa tạo tag `v0.1.0` trước các bước nghiệm thu này.

## 6. Tuần 5 — Chuẩn bị AWS và deploy lần đầu

### Kiến trúc trung gian

```text
Internet → HTTPS → EC2
                   ├─ Nginx
                   ├─ PHP-FPM / Laravel
                   └─ MySQL
```

Database cùng EC2 chỉ là bước học triển khai ban đầu. Chuyển sang RDS ở chặng tiếp theo trước khi phụ thuộc lâu dài vào ứng dụng.

### Chuẩn bị tài khoản và chi phí

1. Bật MFA cho root; dùng danh tính riêng cho công việc hằng ngày.
2. Chọn Region, xác nhận account/profile trước khi tạo tài nguyên.
3. Thiết lập AWS Budget, cảnh báo và tag `Project=StudyHub`, `Environment=Learning`.
4. Ước tính EC2, EBS, IPv4 public, backup và các dịch vụ dự kiến theo giá hiện hành. Budget là cảnh báo, không phải giới hạn chi tiêu cứng.
5. Ghi cách dọn tài nguyên và những khoản vẫn tính phí khi dừng máy.

### Triển khai

1. Tạo EC2 Ubuntu LTS và EBS; chọn cấu hình sau khi kiểm tra nhu cầu và ngân sách.
2. Security Group cho HTTP/HTTPS; SSH nếu dùng chỉ cho IP quản trị. Không mở cổng MySQL ra Internet.
3. Cài Nginx, PHP-FPM, extension và MySQL tương thích.
4. Tạo user deploy, database và user database với quyền cần thiết.
5. Build artifact ở CI hoặc môi trường build; đưa dependency production và frontend build lên máy chủ.
6. Cấu hình document root tại `public/`, quyền ghi cho `storage/` và `bootstrap/cache/`.
7. Cấu hình production: `APP_DEBUG=false`, URL đúng, database và `APP_KEY` riêng, ổn định qua các lần deploy.
8. Chạy migration, cache cấu hình và kiểm tra ứng dụng.
9. Trỏ domain, cấp HTTPS và kiểm tra gia hạn chứng chỉ.
10. Thiết lập log rotation, backup database ngoài EC2, bảo vệ và lưu khả năng phục hồi `APP_KEY`.
11. Reboot và kiểm tra các dịch vụ tự chạy lại.

### Tiêu chí hoàn thành

- [ ] Truy cập HTTPS và tạo dữ liệu thành công.
- [ ] Ứng dụng hoạt động sau reboot.
- [ ] Secrets không nằm trong Git, artifact hoặc log.
- [ ] Có backup ngoài EC2 và hướng dẫn phục hồi.

## 7. Tuần 6 — RDS và tài liệu riêng tư trên S3

```text
Trình duyệt → HTTPS → EC2 / Laravel
                         ├─ RDS MySQL private
                         └─ S3 private
```

### Chuyển database sang RDS

1. Tạo DB subnet group với private subnets theo yêu cầu RDS; tắt public access.
2. Cho phép cổng database từ Security Group của EC2.
3. Bật backup tự động, chọn retention và cấu hình kết nối mã hóa.
4. Chọn cửa sổ chuyển đổi; dừng nhận ghi và xử lý job có thể ghi database.
5. Dump database cũ, restore vào RDS; đối chiếu dữ liệu quan trọng.
6. Đổi cấu hình ứng dụng, kiểm tra đăng nhập, CRUD và worker trước khi mở lại.
7. Giữ backup và ghi quy trình quay lại. Sau khi có ghi mới vào RDS, không chuyển ngược về database cũ mà bỏ qua dữ liệu phát sinh.

### Thêm attachment với S3

1. Tạo bảng `attachments`: chủ sở hữu, ghi chú, object key, tên gốc, MIME và kích thước.
2. Tạo bucket private, bật Block Public Access.
3. Gắn IAM role cho EC2, giới hạn quyền theo bucket/prefix cần dùng.
4. Upload PDF và ảnh qua backend trước; giới hạn loại và kích thước, tạo object key riêng.
5. Kiểm tra quyền trước khi cấp presigned URL tải xuống có thời hạn.
6. Xử lý xóa file, metadata và file dư khi thao tác thất bại.
7. Chỉ bổ sung upload trực tiếp bằng presigned URL nếu có nhu cầu.

### Tiêu chí hoàn thành

- [ ] Dữ liệu và tài liệu không phụ thuộc vào ổ đĩa EC2.
- [ ] Không thể truy cập database trực tiếp từ Internet.
- [ ] Người khác không thể lấy file qua URL hoặc ID attachment.
- [ ] Đã thử restore database vào một môi trường riêng.

## 8. Tuần 7 — Email, queue và giám sát

Tính năng thực hành: email tổng kết việc học mỗi tuần.

1. Thử email local bằng mail driver `log` hoặc dịch vụ mail test được Lerd hỗ trợ.
2. Viết job tổng hợp thời lượng, bài hoàn thành và tiến độ.
3. Dùng database queue để hiểu worker, retry, timeout và failed jobs.
4. Dùng worker Lerd cho local; khám phá danh sách worker trước khi bật.
5. Chuyển sang SQS; kiểm tra timeout/visibility timeout và cơ chế xử lý job thất bại.
6. Production dùng systemd hoặc Supervisor để quản lý worker.
7. Thiết lập Scheduler; cron gọi `schedule:run` mỗi phút.
8. Dùng SES: xác minh danh tính gửi, cấu hình DNS phù hợp và kiểm tra sandbox/giới hạn gửi.
9. Lưu trạng thái gửi để hạn chế gửi trùng khi retry hoặc lịch chạy lặp.
10. Đưa log lên CloudWatch, chọn retention và cảnh báo lỗi, CPU, failed jobs. RAM và dung lượng đĩa cần agent hoặc metric bổ sung.

### Tiêu chí hoàn thành

- [ ] Email gửi đúng lịch và đúng người dùng.
- [ ] Retry không gây gửi trùng trong tình huống đã kiểm tra.
- [ ] Worker phục hồi sau khi dừng; job lỗi có thể kiểm tra và chạy lại.
- [ ] Có log và cảnh báo hỗ trợ điều tra lỗi.

## 9. Tuần 8 — CI/CD, rollback và phục hồi

```text
Pull request → Test + kiểm tra code + build Vue
Merge main → Artifact → Deploy → Health check
```

### Các bước

1. Đóng gói release theo commit với dependency production và frontend build.
2. Dùng GitHub Actions OIDC để nhận quyền AWS tạm thời, giới hạn trust theo repository và nhánh/environment triển khai.
3. Deploy qua Systems Manager; giữ secrets ngoài artifact.
4. Tạo thư mục release mới, liên kết `.env` và storage dùng chung.
5. Migration phải tương thích với phiên bản đang phục vụ; ưu tiên thêm trước, bỏ cột ở release sau.
6. Chuyển symlink `current`, reload dịch vụ cần thiết và restart worker.
7. Health check và smoke test đăng nhập/CRUD; giữ release cũ để rollback code.
8. Viết runbook deploy, rollback, backup, restore và dọn tài nguyên.
9. Thử một release nhỏ, rollback code và restore backup vào database riêng.

**Rollback code không tự hoàn tác schema hoặc dữ liệu.** Migration phá hủy dữ liệu cần kế hoạch riêng và backup đã được kiểm tra.

### Tiêu chí hoàn thành

- [ ] Pipeline kiểm tra và deploy thành công.
- [ ] Health check phát hiện deployment lỗi.
- [ ] Có bằng chứng rollback code và restore database.
- [ ] Ghi rõ account, Region, commit và môi trường cho từng lần kiểm chứng, không ghi secrets.

## 10. Mở rộng sau khi ứng dụng ổn định

- IaC để tái tạo hạ tầng; chọn công cụ nhất quán với mục tiêu học và repo.
- ALB, nhiều EC2 hoặc ECS khi thực hành khả năng mở rộng.
- Full-text search khi tìm kiếm hiện tại không đáp ứng.
- Pomodoro, lịch ôn tập, xuất dữ liệu và chia sẻ khóa học khi có nhu cầu thực tế.

Không cần thêm tất cả dịch vụ để hoàn thành dự án. Ưu tiên mỗi dịch vụ giải quyết một chức năng hoặc bài thực hành cụ thể.

## 11. Cách theo dõi tiến độ

Mỗi công việc nên ghi: mục tiêu, phạm vi, điều kiện nghiệm thu, cách kiểm tra và kết quả thực tế. Phân biệt rõ **đã viết mã**, **đã kiểm tra local**, **đã triển khai AWS** và **chưa kiểm chứng**.

Thứ tự bắt đầu: **khởi tạo local bằng Lerd → xác thực → CRUD khóa học → bài học → ghi chú → dashboard → AWS**.

## 12. Tài liệu tham khảo

- [Laravel Fortify](https://laravel.com/docs/fortify)
- [Laravel deployment](https://laravel.com/framework/docs/deployment)
- [Laravel Scheduler](https://laravel.com/framework/docs/scheduling)
- [AWS Budgets](https://docs.aws.amazon.com/cost-management/latest/userguide/budgets-managing-costs.html)
- [Kết nối EC2 với RDS](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/ec2-rds-connect.html)
- [S3 presigned URLs và chia sẻ file riêng tư](https://aws.amazon.com/blogs/security/how-to-securely-transfer-files-with-presigned-urls/)

Kiểm tra lại tài liệu theo phiên bản và giá hiện hành trước khi thực thi.
