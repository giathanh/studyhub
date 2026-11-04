<?php

namespace Database\Seeders;

use App\Enums\StudyStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Seed the sample AWS course for the test user.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $course = $user->courses()->firstOrCreate(
            ['title' => 'AWS cơ bản'],
            [
                'description' => 'Các dịch vụ AWS nền tảng cần nắm trước khi triển khai ứng dụng.',
                'status' => StudyStatus::InProgress,
            ],
        );

        if (! $course->wasRecentlyCreated) {
            return;
        }

        $lessons = $course->lessons()->createMany([
            [
                'title' => 'IAM',
                'position' => 1,
                'status' => StudyStatus::Completed,
                'completed_at' => now(),
                'content' => "## Nguyên tắc phân quyền\n\n- Bật **MFA** cho tài khoản root.\n- Áp dụng nguyên tắc quyền tối thiểu (*least privilege*).\n- Dùng IAM Role thay vì gắn cứng access key trên máy chủ.\n\n```bash\naws sts get-caller-identity\n```",
            ],
            [
                'title' => 'VPC',
                'position' => 2,
                'status' => StudyStatus::InProgress,
                'content' => "## Mạng riêng ảo\n\n- Tách public subnet cho tầng web và private subnet cho database.\n- Giới hạn Security Group theo cổng và nguồn truy cập.",
            ],
            [
                'title' => 'EC2',
                'position' => 3,
                'status' => StudyStatus::NotStarted,
                'content' => "## Máy chủ ảo\n\n- Chọn Ubuntu LTS và cấu hình Nginx + PHP-FPM.\n- Gắn IAM Role để truy cập S3 không cần lưu key trong `.env`.",
            ],
            [
                'title' => 'S3',
                'position' => 4,
                'status' => StudyStatus::NotStarted,
                'content' => "## Lưu trữ đối tượng\n\n- Bật **Block Public Access** cho bucket tài liệu riêng tư.\n- Cấp quyền tải xuống tạm thời bằng presigned URL.",
            ],
        ]);

        $user->notes()->create([
            'course_id' => $course->id,
            'lesson_id' => $lessons->first()->id,
            'title' => 'Kiểm tra danh tính AWS CLI và MFA cho IAM',
            'body' => "Trước khi tạo tài nguyên AWS, luôn xác nhận tài khoản và profile đang dùng:\n\n```bash\naws sts get-caller-identity --profile studyhub\n```\n\nTài liệu tham khảo: [IAM Best Practices](https://docs.aws.amazon.com/IAM/latest/UserGuide/best-practices.html)",
        ]);
    }
}
