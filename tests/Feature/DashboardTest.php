<?php

namespace Tests\Feature;

use App\Enums\StudyStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Note;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_shows_only_owned_data_and_current_week_minutes(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 4)->startOfDay());
        $user = User::factory()->create();
        $course = Course::factory()->for($user)->create(['status' => StudyStatus::InProgress]);
        Lesson::factory()->for($course)->create(['status' => StudyStatus::Completed]);
        $unfinished = Lesson::factory()->for($course)->create();
        $note = Note::factory()->for($user)->create();
        $otherCourse = Course::factory()->create(['status' => StudyStatus::InProgress]);
        Lesson::factory()->for($otherCourse)->create();
        Note::factory()->create();
        StudySession::factory()->create(['minutes' => 999]);
        StudySession::factory()->for($user)->create(['studied_on' => '2026-11-02', 'minutes' => 30]);
        StudySession::factory()->for($user)->create(['studied_on' => '2026-11-04', 'minutes' => 45]);
        StudySession::factory()->for($user)->create(['studied_on' => '2026-11-01', 'minutes' => 200]);
        StudySession::factory()->for($user)->create(['studied_on' => '2026-11-09', 'minutes' => 100]);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')->has('courses.data', 1)
            ->where('courses.data.0.id', $course->id)->where('courses.data.0.progress', 50)
            ->has('lessons.data', 1)->where('lessons.data.0.id', $unfinished->id)
            ->has('notes.data', 1)->where('notes.data.0.id', $note->id)
            ->where('week.start', '2026-11-02')->where('week.end', '2026-11-08')->where('week.minutes', 75));
    }

    public function test_empty_dashboard_has_zero_time_and_empty_lists(): void
    {
        Http::preventStrayRequests();

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard')
                ->has('courses.data', 0)->has('lessons.data', 0)->has('notes.data', 0)->where('week.minutes', 0));
    }

    public function test_dashboard_paginates_courses_and_handles_a_course_without_lessons(): void
    {
        $user = User::factory()->create();
        Course::factory()->count(7)->for($user)->create(['status' => StudyStatus::InProgress]);

        $this->actingAs($user)->get('/dashboard?courses_page=2')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('courses.data', 1)
                ->where('courses.meta.total', 7)->where('courses.data.0.progress', 0));
    }
}
