<?php

namespace Tests\Feature\Notes;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_the_users_own_notes_without_full_body(): void
    {
        $user = User::factory()->create();
        $ownNote = Note::factory()->for($user)->create([
            'title' => 'S3 bucket policies',
            'body' => 'Detailed markdown body',
        ]);
        Note::factory()->create();

        $this->actingAs($user)
            ->get(route('notes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Notes/Index')
                ->has('notes.data', 1)
                ->where('notes.data.0.id', $ownNote->id)
                ->where('notes.data.0.title', 'S3 bucket policies')
                ->missing('notes.data.0.body'));
    }

    public function test_index_searches_by_title_and_body_and_filters_by_course(): void
    {
        $user = User::factory()->create();
        $awsCourse = Course::factory()->for($user)->create(['title' => 'AWS']);
        $linuxCourse = Course::factory()->for($user)->create(['title' => 'Linux']);

        $matchingTitle = Note::factory()->for($user)->create([
            'course_id' => $awsCourse->id,
            'title' => 'IAM AssumeRole guide',
            'body' => 'Using STS temporary credentials.',
        ]);

        $matchingBody = Note::factory()->for($user)->create([
            'course_id' => $awsCourse->id,
            'title' => 'CLI commands',
            'body' => 'Run aws sts assume-role with the role ARN.',
        ]);

        Note::factory()->for($user)->create([
            'course_id' => $linuxCourse->id,
            'title' => 'IAM on Linux',
            'body' => 'Unrelated course note.',
        ]);

        $this->actingAs($user)
            ->get(route('notes.index', [
                'search' => 'assume',
                'course_id' => $awsCourse->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Notes/Index')
                ->has('notes.data', 2)
                ->where('notes.data.0.id', $matchingBody->id)
                ->where('notes.data.1.id', $matchingTitle->id)
                ->where('filters.search', 'assume')
                ->where('filters.course_id', $awsCourse->id));
    }

    public function test_index_paginates_notes(): void
    {
        $user = User::factory()->create();
        Note::factory()->for($user)->count(13)->create();

        $this->actingAs($user)
            ->get(route('notes.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('notes.data', 1)
                ->where('notes.meta.last_page', 2));
    }

    public function test_search_treats_sql_wildcards_as_literal_characters(): void
    {
        $user = User::factory()->create();
        $note = Note::factory()->for($user)->create(['title' => '100%_done!', 'body' => 'Literal match']);
        Note::factory()->for($user)->create(['title' => '100 percent done', 'body' => 'Not a match']);

        $this->actingAs($user)
            ->get(route('notes.index', ['search' => '%_done!']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('notes.data', 1)
                ->where('notes.data.0.id', $note->id));
    }

    public function test_user_can_create_a_standalone_note(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('notes.store'), [
            'title' => 'General AWS CLI flags',
            'body' => 'Use `--profile` and `--region` explicitly.',
        ]);

        $note = $user->notes()->sole();

        $response->assertRedirect(route('notes.show', $note))
            ->assertInertiaFlash('success', 'Note created.');

        $this->assertSame('General AWS CLI flags', $note->title);
        $this->assertNull($note->course_id);
        $this->assertNull($note->lesson_id);
    }

    public function test_user_can_create_a_note_linked_to_course_and_lesson(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        $response = $this->actingAs($course->user)->post(route('notes.store'), [
            'title' => 'VPC CIDR blocks',
            'body' => '10.0.0.0/16 gives 65,536 IPv4 addresses.',
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
        ]);

        $note = $course->user->notes()->sole();

        $response->assertRedirect(route('notes.show', $note));
        $this->assertSame($course->id, $note->course_id);
        $this->assertSame($lesson->id, $note->lesson_id);
    }

    public function test_linking_a_lesson_without_course_id_automatically_sets_the_course(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->for($course)->create();

        $this->actingAs($course->user)->post(route('notes.store'), [
            'title' => 'EC2 key pairs',
            'body' => 'chmod 400 key.pem',
            'lesson_id' => $lesson->id,
        ])->assertRedirect();

        $note = $course->user->notes()->sole();

        $this->assertSame($course->id, $note->course_id);
        $this->assertSame($lesson->id, $note->lesson_id);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidNotes(): array
    {
        return [
            'missing title' => [['title' => ''], 'title'],
            'title too long' => [['title' => str_repeat('a', 256)], 'title'],
            'missing body' => [['body' => ''], 'body'],
        ];
    }

    /**
     * @param  array<string, mixed>  $override
     */
    #[DataProvider('invalidNotes')]
    public function test_invalid_note_is_rejected(array $override, string $field): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('notes.store'), [
                'title' => 'Valid title',
                'body' => 'Valid markdown body',
                ...$override,
            ])
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_backend_rejects_linking_a_note_to_another_users_course_or_lesson(): void
    {
        $user = User::factory()->create();
        $otherCourse = Course::factory()->create();
        $otherLesson = Lesson::factory()->for($otherCourse)->create();

        $this->actingAs($user)
            ->post(route('notes.store'), [
                'title' => 'Cross-account course link',
                'body' => 'Should fail',
                'course_id' => $otherCourse->id,
            ])
            ->assertSessionHasErrors('course_id');

        $this->actingAs($user)
            ->post(route('notes.store'), [
                'title' => 'Cross-account lesson link',
                'body' => 'Should fail',
                'lesson_id' => $otherLesson->id,
            ])
            ->assertSessionHasErrors('lesson_id');

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_backend_rejects_a_lesson_that_does_not_belong_to_the_selected_course(): void
    {
        $user = User::factory()->create();
        $firstCourse = Course::factory()->for($user)->create();
        $secondCourse = Course::factory()->for($user)->create();
        $lessonFromSecondCourse = Lesson::factory()->for($secondCourse)->create();

        $this->actingAs($user)
            ->post(route('notes.store'), [
                'title' => 'Mismatched course and lesson',
                'body' => 'Should fail',
                'course_id' => $firstCourse->id,
                'lesson_id' => $lessonFromSecondCourse->id,
            ])
            ->assertSessionHasErrors('lesson_id');

        $this->assertDatabaseCount('notes', 0);
    }

    public function test_user_can_view_update_and_delete_their_note(): void
    {
        $note = Note::factory()->create([
            'title' => 'Original title',
            'body' => '# Original body',
        ]);

        $this->actingAs($note->user)
            ->get(route('notes.show', $note))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Notes/Show')
                ->where('note.data.title', 'Original title')
                ->where('note.data.body', '# Original body'));

        $this->actingAs($note->user)
            ->put(route('notes.update', $note), [
                'title' => 'Updated title',
                'body' => '# Updated body',
            ])
            ->assertRedirect(route('notes.show', $note))
            ->assertInertiaFlash('success', 'Note updated.');

        $note->refresh();

        $this->assertSame('Updated title', $note->title);
        $this->assertSame('# Updated body', $note->body);

        $this->actingAs($note->user)
            ->delete(route('notes.destroy', $note))
            ->assertRedirect(route('notes.index'))
            ->assertInertiaFlash('success', 'Note deleted.');

        $this->assertModelMissing($note);
    }

    public function test_markdown_with_raw_html_and_unsafe_links_does_not_render_executable_script(): void
    {
        $payload = "<script>alert('xss')</script>\n\n[Click me](javascript:alert(1))";
        $note = Note::factory()->create(['body' => $payload]);

        $response = $this->actingAs($note->user)
            ->get(route('notes.show', $note));

        $response->assertOk()
            ->assertDontSee("<script>alert('xss')</script>", false)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Notes/Show')
                ->where('note.data.body', $payload));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $note = Note::factory()->create();

        $this->get(route('notes.index'))->assertRedirect(route('login'));
        $this->get(route('notes.create'))->assertRedirect(route('login'));
        $this->post(route('notes.store'), [])->assertRedirect(route('login'));
        $this->get(route('notes.show', $note))->assertRedirect(route('login'));
        $this->get(route('notes.edit', $note))->assertRedirect(route('login'));
        $this->put(route('notes.update', $note), [])->assertRedirect(route('login'));
        $this->delete(route('notes.destroy', $note))->assertRedirect(route('login'));
    }

    public function test_another_user_cannot_reach_a_note_by_id(): void
    {
        $note = Note::factory()->create(['title' => 'Private note']);

        $this->actingAs(User::factory()->create());

        $this->get(route('notes.show', $note))->assertForbidden();
        $this->get(route('notes.edit', $note))->assertForbidden();
        $this->put(route('notes.update', $note), [
            'title' => 'Hijacked',
            'body' => 'Hijacked body',
        ])->assertForbidden();
        $this->delete(route('notes.destroy', $note))->assertForbidden();

        $this->assertSame('Private note', $note->refresh()->title);
    }
}
