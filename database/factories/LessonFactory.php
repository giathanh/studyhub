<?php

namespace Database\Factories;

use App\Enums\StudyStatus;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'section' => null,
            'title' => fake()->sentence(3),
            'content' => fake()->paragraph(),
            'status' => StudyStatus::NotStarted,
            'position' => 1,
        ];
    }
}
