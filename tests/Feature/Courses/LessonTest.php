<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\Lesson;
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

    public function test_guest_is_redirected_to_login(): void
    {
        $lesson = Lesson::factory()->create();

        $this->get(route('courses.lessons.show', [$lesson->course, $lesson]))
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

        $this->assertModelExists($lesson);
    }
}
