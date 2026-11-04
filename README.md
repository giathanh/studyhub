# StudyHub

Ứng dụng quản lý việc học và ghi chú cá nhân. Kế hoạch triển khai nằm trong [studyhub-roadmap.md](studyhub-roadmap.md).

## Phiên bản

| Thành phần | Phiên bản |
| --- | --- |
| Lerd | 1.38.0 |
| PHP | 8.5 (`.php-version`) |
| Node | 22 (`.node-version`) |
| Package manager | npm, theo `package-lock.json` |
| MySQL | 8.4 (service của Lerd) |
| Laravel | 13.40 |
| Laravel Fortify | 1.41 |
| Inertia | 3 |
| Vue / TypeScript | 3.5 / 6.0 |
| Tailwind CSS | 4 |
| Test | PHPUnit 12, chạy trên SQLite in-memory |

Phiên bản chính xác của từng package nằm trong `composer.lock` và `package-lock.json`.

## Cài đặt local

Cần Lerd đang chạy. Nếu chưa, chạy `lerd start`.

```bash
lerd setup               # đọc .lerd.yaml, cài dependency, cấu hình .env, build và migrate
lerd worker start vite   # bật Vite và HMR
```

Ứng dụng chạy tại <https://studyhub.test>. Không dùng `php artisan serve`.

Lerd tự ghi biến kết nối vào `.env`, không cần sửa tay. `DB_HOST=lerd-mysql` chỉ phân giải được trong container, nên hãy chạy lệnh qua `php artisan` thay vì dùng MySQL client trên máy host.

## Chạy bằng Docker

Stack Docker gồm Nginx và PHP-FPM, chạy bản build production, không có HMR. Database dùng Amazon RDS for MySQL nên không nằm trong Compose. Việc phát triển hằng ngày vẫn dùng Lerd.

Khai báo các biến sau trong `.env`:

| Biến | Bắt buộc | Mặc định | Ý nghĩa |
| --- | --- | --- | --- |
| `APP_KEY` | Có | | Khóa mã hóa của ứng dụng |
| `DOCKER_DB_HOST` | Có | | Endpoint của RDS |
| `DOCKER_DB_USERNAME` | Có | | User database |
| `DOCKER_DB_PASSWORD` | Có | | Mật khẩu database |
| `DOCKER_DB_DATABASE` | Không | `studyhub` | Tên database, cần được tạo sẵn trên RDS |
| `DOCKER_DB_PORT` | Không | `3306` | Cổng database |
| `DOCKER_DB_SSL_CA` | Không | CA của AWS trong image | Để trống khi dùng MySQL local không có TLS |
| `DOCKER_APP_PORT` | Không | `8080` | Cổng trên máy host |

```bash
docker compose up -d --build   # build image, chạy migration rồi khởi động
docker compose logs -f app     # xem log
docker compose down            # dừng stack, không ảnh hưởng dữ liệu trên RDS
```

Ứng dụng chạy tại `http://localhost:8080`. Compose không copy `.env` vào image. Kết nối tới RDS dùng TLS với chứng chỉ CA của AWS có sẵn trong image.

Để dùng MySQL của Lerd trên máy local, đặt `DOCKER_DB_HOST=host.docker.internal`, `DOCKER_DB_SSL_CA=` (để trống), rồi dùng user và mật khẩu giống `DB_USERNAME`, `DB_PASSWORD` trong `.env`. Không dùng `localhost` vì trong container nó trỏ về chính container. Khi đó stack dùng chung database với Lerd.

Máy chạy Docker phải kết nối được tới RDS: Security Group của RDS cần cho phép cổng 3306 từ máy đó. RDS không nên mở ra Internet, nên thường chỉ chạy được stack này từ bên trong VPC.

## Kiểm tra

```bash
php artisan test --compact   # feature test
vendor/bin/pint --format agent   # định dạng PHP
npm run types:check          # kiểm tra kiểu TypeScript và Vue
npm run build                # build frontend
```

## Xác thực

Dùng Laravel Fortify với trang Inertia tự viết. Chỉ bật đăng ký và đổi mật khẩu trong [config/fortify.php](config/fortify.php). Trang hiện có: `/login`, `/register`, `/dashboard`, `/settings/password`.
