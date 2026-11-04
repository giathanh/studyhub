<?php

namespace Database\Factories;

use App\Models\StudySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudySession> */
class StudySessionFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'studied_on' => today(), 'minutes' => 30, 'lesson_id' => null];
    }
}
