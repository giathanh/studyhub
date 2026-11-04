<?php

namespace Tests\Feature\Courses;

use App\Enums\StudyStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_is_created_at_the_end_of_the_course(): void
    {
        $course = Course::factory()->create();
        Lesson::factory()->for($course)->create(['position' => 4]);

        $this->actingAs($course->user)
            ->post(route('courses.lessons.store', $course), [
                'title' => 'IAM',
                'section' => 'Security',
                'content' => '# Notes',
            ])
            ->assertRedirect();

        $lesson = $course->lessons()->where('title', 'IAM')->sole();

        $this->assertSame(5, $lesson->position);
        $this->assertSame('Security', $lesson->section);
        $this->assertSame('# Notes', $lesson->content);
    }

    public function test_first_lesson_starts_at_position_one(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($course->user)
            ->post(route('courses.lessons.store', $course), ['title' => 'IAM']);

        $this->assertSame(1, $course->lessons()->sole()->position);
    }

    public function test_lesson_requires_a_title(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($course->user)
            ->post(route('courses.lessons.store', $course), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_lesson_page_shows_its_content(): void
    {
        $lesson = Lesson::factory()->create(['content' => 'Body text']);

        $this->actingAs($lesson->course->user)
            ->get(route('courses.lessons.show', [$lesson->course, $lesson]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Lessons/Show')
                ->where('lesson.data.content', 'Body text'));
    }

    public function test_user_can_update_and_delete_a_lesson(): void
    {
        $lesson = Lesson::factory()->create();
        $course = $lesson->course;

        $this->actingAs($course->user)
            ->put(route('courses.lessons.update', [$course, $lesson]), ['title' => 'Renamed'])
            ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

        $this->assertSame('Renamed', $lesson->refresh()->title);

        $this->delete(route('courses.lessons.destroy', [$course, $lesson]))
            ->assertRedirect(route('courses.show', $course));

        $this->assertModelMissing($lesson);
    }

    public function test_completing_a_lesson_records_completed_at_and_reopening_clears_it(): void
    {
        $lesson = Lesson::factory()->create(['status' => StudyStatus::NotStarted]);
        $course = $lesson->course;

        $this->travelTo('2026-11-04 09:30:00');

        $this->actingAs($course->user)
            ->patch(route('courses.lessons.status', [$course, $lesson]), [
                'status' => StudyStatus::Completed->value,
            ])
            ->assertRedirect();

        $lesson->refresh();

        $this->assertSame(StudyStatus::Completed, $lesson->status);
        $this->assertSame('2026-11-04 09:30:00', $lesson->completed_at?->format('Y-m-d H:i:s'));

        $this->travelTo('2026-11-05 14:00:00');

        $this->actingAs($course->user)
            ->put(route('courses.lessons.update', [$course, $lesson]), [
                'title' => 'Still completed',
                'status' => StudyStatus::Completed->value,
            ])
            ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

        $this->assertSame('2026-11-04 09:30:00', $lesson->refresh()->completed_at?->format('Y-m-d H:i:s'));

        $this->actingAs($course->user)
            ->patch(route('courses.lessons.status', [$course, $lesson]), [
                'status' => StudyStatus::InProgress->value,
            ])
            ->assertRedirect();

        $lesson->refresh();

        $this->assertSame(StudyStatus::InProgress, $lesson->status);
        $this->assertNull($lesson->completed_at);
    }

    public function test_user_can_reorder_lessons_within_a_course(): void
    {
        $course = Course::factory()->create();
        $first = Lesson::factory()->for($course)->create(['title' => 'IAM', 'position' => 1]);
        $second = Lesson::factory()->for($course)->create(['title' => 'VPC', 'position' => 2]);
        $third = Lesson::factory()->for($course)->create(['title' => 'EC2', 'position' => 3]);

        $this->actingAs($course->user)
            ->patch(route('courses.lessons.move', [$course, $third]), ['direction' => 'up'])
            ->assertRedirect(route('courses.show', $course));

        $this->assertSame(
            ['IAM', 'EC2', 'VPC'],
            $course->lessons()->orderBy('position')->pluck('title')->all(),
        );

        $this->actingAs($course->user)
            ->patch(route('courses.lessons.move', [$course, $first]), ['direction' => 'down'])
            ->assertRedirect(route('courses.show', $course));

        $this->assertSame(
            ['EC2', 'IAM', 'VPC'],
            $course->lessons()->orderBy('position')->pluck('title')->all(),
        );

        $this->assertSame([1, 2, 3], $course->lessons()->orderBy('position')->pluck('position')->all());
    }

    public function test_moving_with_an_invalid_direction_is_rejected(): void
    {
        $lesson = Lesson::factory()->create(['position' => 1]);

        $this->actingAs($lesson->course->user)
            ->patch(route('courses.lessons.move', [$lesson->course, $lesson]), ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');
    }

    public function test_reordering_across_pages_preserves_the_current_page(): void
    {
        $course = Course::factory()->create();
        $lessons = Lesson::factory()->for($course)->count(51)
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create();

        $this->actingAs($course->user)
            ->patch(route('courses.lessons.move', [$course, $lessons[50], 'page' => 2]), ['direction' => 'up'])
            ->assertRedirect(route('courses.show', [$course, 'page' => 2]));

        $this->assertSame(50, $lessons[50]->refresh()->position);
        $this->assertSame(51, $lessons[49]->refresh()->position);
    }

    public function test_invalid_status_does_not_change_a_lesson(): void
    {
        $lesson = Lesson::factory()->create(['status' => StudyStatus::NotStarted]);

        $this->actingAs($lesson->course->user)
            ->patch(route('courses.lessons.status', [$lesson->course, $lesson]), ['status' => 'invalid'])
            ->assertSessionHasErrors('status');

        $this->assertSame(StudyStatus::NotStarted, $lesson->refresh()->status);
        $this->assertNull($lesson->completed_at);
    }

    public function test_lesson_show_lists_linked_notes_without_full_body(): void
    {
        $lesson = Lesson::factory()->create();
        $course = $lesson->course;
        $note = Note::factory()->for($course->user)->create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => 'IAM Policy snippet',
            'body' => 'Full note markdown body',
        ]);

        $this->actingAs($course->user)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Lessons/Show')
                ->has('notes.data', 1)
                ->where('notes.data.0.id', $note->id)
                ->where('notes.data.0.title', 'IAM Policy snippet')
                ->missing('notes.data.0.body'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $lesson = Lesson::factory()->create();

        $this->get(route('courses.lessons.show', [$lesson->course, $lesson]))
            ->assertRedirect(route('login'));
        $this->patch(route('courses.lessons.status', [$lesson->course, $lesson]), ['status' => 'completed'])
            ->assertRedirect(route('login'));
        $this->patch(route('courses.lessons.move', [$lesson->course, $lesson]), ['direction' => 'up'])
            ->assertRedirect(route('login'));
    }

    public function test_another_user_cannot_reach_a_lesson(): void
    {
        $lesson = Lesson::factory()->create(['title' => 'Private']);
        $course = $lesson->course;

        $this->actingAs(User::factory()->create());

        $this->get(route('courses.lessons.create', $course))->assertForbidden();
        $this->post(route('courses.lessons.store', $course), ['title' => 'Injected'])->assertForbidden();
        $this->get(route('courses.lessons.show', [$course, $lesson]))->assertForbidden();
        $this->get(route('courses.lessons.edit', [$course, $lesson]))->assertForbidden();
        $this->put(route('courses.lessons.update', [$course, $lesson]), ['title' => 'Hijacked'])->assertForbidden();
        $this->patch(route('courses.lessons.status', [$course, $lesson]), ['status' => StudyStatus::Completed->value])->assertForbidden();
        $this->patch(route('courses.lessons.move', [$course, $lesson]), ['direction' => 'up'])->assertForbidden();
        $this->delete(route('courses.lessons.destroy', [$course, $lesson]))->assertForbidden();

        $this->assertSame('Private', $lesson->refresh()->title);
        $this->assertSame(1, $course->lessons()->count());
    }

    public function test_lesson_cannot_be_reached_through_a_different_course(): void
    {
        $lesson = Lesson::factory()->create();
        $ownCourse = Course::factory()->create();

        $this->actingAs($ownCourse->user)
            ->get(route('courses.lessons.show', [$ownCourse, $lesson]))
            ->assertNotFound();

        $this->delete(route('courses.lessons.destroy', [$ownCourse, $lesson]))->assertNotFound();
        $this->patch(route('courses.lessons.status', [$ownCourse, $lesson]), ['status' => 'completed'])->assertNotFound();
        $this->patch(route('courses.lessons.move', [$ownCourse, $lesson]), ['direction' => 'up'])->assertNotFound();

        $this->assertModelExists($lesson);
    }
}
