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

## Kiểm tra

```bash
php artisan test --compact   # feature test
vendor/bin/pint --format agent   # định dạng PHP
npm run types:check          # kiểm tra kiểu TypeScript và Vue
npm run build                # build frontend
```

## Xác thực

Dùng Laravel Fortify với trang Inertia tự viết. Chỉ bật đăng ký và đổi mật khẩu trong [config/fortify.php](config/fortify.php). Trang hiện có: `/login`, `/register`, `/dashboard`, `/settings/password`.
