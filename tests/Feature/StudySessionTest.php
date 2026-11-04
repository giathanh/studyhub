<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudySessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_create_or_delete_sessions(): void
    {
        $session = StudySession::factory()->create();

        $this->get('/study-sessions')->assertRedirect('/login');
        $this->post('/study-sessions', [])->assertRedirect('/login');
        $this->delete('/study-sessions/'.$session->id)->assertRedirect('/login');
        $this->assertModelExists($session);
        $this->assertDatabaseCount('study_sessions', 1);
    }

    public function test_owner_can_log_time_with_or_without_a_lesson_and_cannot_choose_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $lesson = Lesson::factory()->for(Course::factory()->for($user))->create();
        $date = today()->toDateString();

        $this->actingAs($user)->post('/study-sessions', [
            'studied_on' => $date, 'minutes' => 45, 'lesson_id' => $lesson->id, 'user_id' => $other->id,
        ])->assertRedirect('/study-sessions')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('study_sessions', ['user_id' => $user->id, 'minutes' => 45, 'lesson_id' => $lesson->id]);

        $this->post('/study-sessions', ['studied_on' => $date, 'minutes' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('study_sessions', ['user_id' => $user->id, 'minutes' => 1, 'lesson_id' => null]);
    }

    public function test_invalid_time_dates_and_foreign_or_missing_lessons_are_rejected(): void
    {
        $user = User::factory()->create();
        $foreignLesson = Lesson::factory()->create();
        $valid = ['studied_on' => today()->toDateString(), 'minutes' => 30];
        $cases = [
            [['minutes' => 0], 'minutes'], [['minutes' => 1441], 'minutes'],
            [['minutes' => 1.5], 'minutes'], [['minutes' => null], 'minutes'],
            [['studied_on' => null], 'studied_on'], [['studied_on' => 'bad'], 'studied_on'],
            [['studied_on' => today()->addDay()->toDateString()], 'studied_on'],
            [['lesson_id' => $foreignLesson->id], 'lesson_id'], [['lesson_id' => 999999], 'lesson_id'],
        ];

        foreach ($cases as [$input, $field]) {
            $this->actingAs($user)->post('/study-sessions', array_replace($valid, $input))->assertSessionHasErrors($field);
            $this->assertDatabaseCount('study_sessions', 0);
        }
    }

    public function test_history_and_lesson_picker_exclude_other_users_and_paginate(): void
    {
        $user = User::factory()->create();
        StudySession::factory()->count(13)->for($user)->create();
        StudySession::factory()->create();
        $lesson = Lesson::factory()->for(Course::factory()->for($user))->create(['title' => 'IAM']);
        Lesson::factory()->create(['title' => 'IAM']);

        $this->actingAs($user)->get('/study-sessions?page=2&lesson_search=IAM')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('StudySessions/Index')
                ->has('sessions.data', 1)->where('sessions.meta.total', 13)
                ->has('lessons.data', 1)->where('lessons.data.0.id', $lesson->id));
    }

    public function test_owner_can_delete_a_session_but_another_user_cannot(): void
    {
        $session = StudySession::factory()->create();

        $this->actingAs(User::factory()->create())->delete('/study-sessions/'.$session->id)->assertForbidden();
        $this->assertModelExists($session);

        $this->actingAs($session->user)->delete('/study-sessions/'.$session->id)->assertRedirect('/study-sessions');
        $this->assertModelMissing($session);
    }

    public function test_deleting_a_lesson_preserves_study_history_without_its_link(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::factory()->for(Course::factory()->for($user))->create();
        $session = StudySession::factory()->for($user)->for($lesson)->create();

        $this->actingAs($user)->delete(route('courses.lessons.destroy', [$lesson->course, $lesson]))->assertRedirect();
        $this->assertDatabaseHas('study_sessions', ['id' => $session->id, 'lesson_id' => null]);
        $this->get('/study-sessions')->assertOk()->assertInertia(fn (Assert $page) => $page->where('sessions.data.0.lesson', null));
    }
}
