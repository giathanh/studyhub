<?php

namespace Database\Seeders;

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
            ['description' => 'Các dịch vụ AWS nền tảng cần nắm trước khi triển khai ứng dụng.'],
        );

        if (! $course->wasRecentlyCreated) {
            return;
        }

        $course->lessons()->createMany([
            ['title' => 'IAM', 'position' => 1],
            ['title' => 'VPC', 'position' => 2],
            ['title' => 'EC2', 'position' => 3],
            ['title' => 'S3', 'position' => 4],
        ]);
    }
}
