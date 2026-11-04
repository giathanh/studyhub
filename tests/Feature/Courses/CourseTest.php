<?php

namespace Tests\Feature\Courses;

use App\Enums\StudyStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Note;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_the_users_own_courses(): void
    {
        $user = User::factory()->create();
        $ownCourse = Course::factory()->for($user)->has(Lesson::factory()->count(2))->create();
        Course::factory()->create();

        $this->actingAs($user)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Courses/Index')
                ->has('courses.data', 1)
                ->where('courses.data.0.id', $ownCourse->id)
                ->where('courses.data.0.lessons_count', 2));
    }

    public function test_index_paginates_courses(): void
    {
        $user = User::factory()->create();
        Course::factory()->for($user)->count(13)->create();

        $this->actingAs($user)
            ->get(route('courses.index', ['page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('courses.data', 1)
                ->where('courses.meta.last_page', 2));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('courses.index'))->assertRedirect(route('login'));
        $this->post(route('courses.store'), [])->assertRedirect(route('login'));
    }

    public function test_create_page_offers_every_status(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('courses.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Courses/Create')
                ->has('statuses', count(StudyStatus::cases())));
    }

    public function test_user_can_create_a_course(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('courses.store'), [
            'title' => 'AWS',
            'description' => 'Cloud basics',
            'status' => StudyStatus::InProgress->value,
            'target_date' => '2027-01-31',
        ]);

        $course = $user->courses()->sole();

        $response->assertRedirect(route('courses.show', $course))
            ->assertInertiaFlash('success', 'Course created.');

        $this->assertSame('AWS', $course->title);
        $this->assertSame(StudyStatus::InProgress, $course->status);
        $this->assertSame('2027-01-31', $course->target_date->toDateString());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidCourses(): array
    {
        return [
            'missing title' => [['title' => ''], 'title'],
            'title too long' => [['title' => str_repeat('a', 256)], 'title'],
            'unknown status' => [['status' => 'paused'], 'status'],
            'invalid date' => [['target_date' => 'soon'], 'target_date'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidCourses')]
    public function test_invalid_course_is_rejected(array $override, string $field): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('courses.store'), [
                'title' => 'AWS',
                'status' => StudyStatus::NotStarted->value,
                ...$override,
            ])
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_show_lists_lessons_in_position_order_without_their_content(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->for($user)->create();
        $second = Lesson::factory()->for($course)->create(['position' => 2]);
        $first = Lesson::factory()->for($course)->create(['position' => 1]);

        $this->actingAs($user)
            ->get(route('courses.show', $course))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Courses/Show')
                ->where('course.data.lessons_count', 2)
                ->where('lessons.data.0.id', $first->id)
                ->where('lessons.data.1.id', $second->id)
                ->missing('lessons.data.0.content'));
    }

    public function test_user_can_update_their_course(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($course->user)
            ->put(route('courses.update', $course), [
                'title' => 'Renamed',
                'status' => StudyStatus::Completed->value,
            ])
            ->assertRedirect(route('courses.show', $course));

        $course->refresh();

        $this->assertSame('Renamed', $course->title);
        $this->assertSame(StudyStatus::Completed, $course->status);
    }

    public function test_deleting_a_course_removes_lessons_but_keeps_notes_unlinked(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();
        $note = Note::factory()->for($course->user)->create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
        ]);

        $this->actingAs($course->user)
            ->delete(route('courses.destroy', $course))
            ->assertRedirect(route('courses.index'));

        $this->assertModelMissing($course);
        $this->assertModelMissing($lesson);

        $note->refresh();

        $this->assertNull($note->course_id);
        $this->assertNull($note->lesson_id);
    }

    public function test_seeding_twice_keeps_a_single_sample_course(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $course = Course::where('title', 'AWS cơ bản')->sole();

        $this->assertSame(
            ['IAM', 'VPC', 'EC2', 'S3'],
            $course->lessons()->orderBy('position')->pluck('title')->all(),
        );
    }

    public function test_another_user_cannot_reach_a_course_by_id(): void
    {
        $course = Course::factory()->create(['title' => 'Private']);

        $this->actingAs(User::factory()->create());

        $this->get(route('courses.show', $course))->assertForbidden();
        $this->get(route('courses.edit', $course))->assertForbidden();
        $this->put(route('courses.update', $course), [
            'title' => 'Hijacked',
            'status' => StudyStatus::NotStarted->value,
        ])->assertForbidden();
        $this->delete(route('courses.destroy', $course))->assertForbidden();

        $this->assertSame('Private', $course->refresh()->title);
    }

    public function test_course_progress_is_zero_when_it_has_no_lessons(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($course->user)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Courses/Show')
                ->where('course.data.lessons_count', 0)
                ->where('course.data.completed_lessons_count', 0)
                ->where('course.data.progress', 0));
    }

    public function test_course_progress_is_calculated_from_completed_lessons(): void
    {
        $course = Course::factory()->create();
        Lesson::factory()->for($course)->create(['position' => 1, 'status' => StudyStatus::Completed]);
        Lesson::factory()->for($course)->create(['position' => 2, 'status' => StudyStatus::InProgress]);
        Lesson::factory()->for($course)->count(2)->create(['position' => 3, 'status' => StudyStatus::NotStarted]);

        $this->actingAs($course->user)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('courses.data.0.lessons_count', 4)
                ->where('courses.data.0.completed_lessons_count', 1)
                ->where('courses.data.0.progress', 25));
    }
}
