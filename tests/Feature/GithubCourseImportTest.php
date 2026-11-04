<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GithubCourseImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function fakeRepository(array $files = [], int $guideStatus = 200, ?string $labContent = null): void
    {
        Http::fake([
            'https://api.github.com/repos/owner/lab' => Http::response(['default_branch' => 'main']),
            'https://api.github.com/repos/owner/lab/commits/main' => Http::response(['sha' => str_repeat('a', 40)]),
            'https://api.github.com/repos/owner/lab/git/trees/*' => Http::response([
                'truncated' => false,
                'tree' => $files ?: [
                    ['path' => 'notes/02-guide.md', 'type' => 'blob', 'mode' => '100644', 'size' => 30],
                    ['path' => 'labs/01-lab.md', 'type' => 'blob', 'mode' => '100644', 'size' => 30],
                    ['path' => '_templates/template.md', 'type' => 'blob', 'mode' => '100644', 'size' => 1],
                    ['path' => '.github/private.md', 'type' => 'blob', 'mode' => '100644', 'size' => 1],
                    ['path' => 'vendor/ignored.md', 'type' => 'blob', 'mode' => '100644', 'size' => 1],
                    ['path' => 'link.md', 'type' => 'blob', 'mode' => '120000', 'size' => 1],
                    ['path' => 'index.php', 'type' => 'blob', 'mode' => '100644', 'size' => 1],
                ],
            ]),
            'https://raw.githubusercontent.com/owner/lab/*/notes/02-guide.md' => Http::response("# Guide\n\nRead this.", $guideStatus),
            'https://raw.githubusercontent.com/owner/lab/*/labs/01-lab.md' => Http::response($labContent ?? "# Lab\n\nRun this."),
        ]);
    }

    private function preview(): array
    {
        return $this->postJson(route('courses.import.github.preview'), ['repository_url' => 'https://github.com/owner/lab'])
            ->assertOk()->json();
    }

    public function test_guests_cannot_preview_or_import(): void
    {
        $this->postJson(route('courses.import.github.preview'))->assertUnauthorized();
        $this->post(route('courses.import.github.store'))->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    public function test_preview_lists_markdown_in_order_without_creating_a_course(): void
    {
        $this->fakeRepository();
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->assertSame(['labs/01-lab.md', 'notes/02-guide.md'], array_column($preview['files'], 'path'));
        $this->assertSame('Lab', $preview['title']);
        $this->assertSame(64, strlen($preview['token']));
        $this->assertDatabaseCount('courses', 0);
        Http::assertSentCount(3);
    }

    public function test_only_github_repository_urls_are_accepted(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['http://127.0.0.1/repo', 'https://github.com.evil.test/owner/lab', 'https://github.com/owner/lab/tree/main', 'https://github.com/owner/..'] as $url) {
            $this->postJson(route('courses.import.github.preview'), ['repository_url' => $url])->assertUnprocessable()->assertJsonValidationErrors('repository_url');
        }
        Http::assertNothingSent();
    }

    public function test_selected_files_create_a_course_owned_by_the_importer_at_previewed_commit(): void
    {
        $this->fakeRepository();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);
        $preview = $this->preview();
        $this->post(route('courses.import.github.store'), [
            'token' => $preview['token'], 'title' => 'My labs',
            'paths' => ['notes/02-guide.md', 'labs/01-lab.md'], 'user_id' => $other->id,
        ])->assertRedirect(route('courses.show', $user->courses()->sole()));
        $course = $user->courses()->sole();
        $this->assertSame('My labs', $course->title);
        $lessons = $course->lessons()->orderBy('position')->get();
        $this->assertSame(['Lab', 'Guide'], $lessons->pluck('title')->all());
        $this->assertSame(['Labs', 'Notes'], $lessons->pluck('section')->all());
        $this->assertSame(['Run this.', 'Read this.'], $lessons->pluck('content')->all());
        $this->assertSame(0, $other->courses()->count());
        Http::assertSent(fn ($request) => $request->url() === 'https://raw.githubusercontent.com/owner/lab/'.str_repeat('a', 40).'/labs/01-lab.md');
    }

    public function test_unselected_files_are_not_downloaded(): void
    {
        $this->fakeRepository();
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->post(route('courses.import.github.store'), ['token' => $preview['token'], 'paths' => ['labs/01-lab.md']])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lessons', 1);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'notes/02-guide.md'));
    }

    public function test_another_user_cannot_use_a_preview_token(): void
    {
        $this->fakeRepository();
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->actingAs(User::factory()->create())->post(route('courses.import.github.store'), [
            'token' => $preview['token'], 'paths' => ['labs/01-lab.md'],
        ])->assertSessionHasErrors('token');
        $this->assertDatabaseCount('courses', 0);
        Http::assertSentCount(3);
    }

    public function test_expired_preview_requires_loading_again(): void
    {
        $this->fakeRepository();
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->travel(31)->minutes();
        $this->post(route('courses.import.github.store'), ['token' => $preview['token'], 'paths' => ['labs/01-lab.md']])->assertSessionHasErrors('token');
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_unlisted_paths_and_empty_selection_are_rejected(): void
    {
        $this->fakeRepository();
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        foreach ([['../../secret.md'], []] as $paths) {
            $this->post(route('courses.import.github.store'), ['token' => $preview['token'], 'paths' => $paths])->assertSessionHasErrors('paths');
        }
        Http::assertSentCount(3);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_github_errors_are_presented_as_validation_errors(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake(['api.github.com/*' => Http::sequence()->push([], 404)->push([], 403)->push([], 429)->push([], 500)]);
        foreach (range(1, 4) as $attempt) {
            $this->postJson(route('courses.import.github.preview'), ['repository_url' => 'https://github.com/owner/lab'])->assertUnprocessable()->assertJsonValidationErrors('repository_url');
        }
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_connection_failure_is_reported(): void
    {
        Http::fake(['api.github.com/*' => Http::failedConnection()]);
        $this->actingAs(User::factory()->create())->postJson(route('courses.import.github.preview'), ['repository_url' => 'https://github.com/owner/lab'])->assertUnprocessable()->assertJsonValidationErrors('repository_url');
    }

    public function test_empty_repository_and_truncated_listing_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake([
            'api.github.com/repos/owner/lab' => Http::response(['default_branch' => 'main']),
            'api.github.com/repos/owner/lab/commits/main' => Http::response(['sha' => str_repeat('a', 40)]),
            'api.github.com/repos/owner/lab/git/trees/*' => Http::sequence()->push(['tree' => []])->push(['truncated' => true, 'tree' => []]),
        ]);
        foreach (range(1, 2) as $attempt) {
            $this->postJson(route('courses.import.github.preview'), ['repository_url' => 'https://github.com/owner/lab'])->assertUnprocessable()->assertJsonValidationErrors('repository_url');
        }
    }

    public function test_partial_download_failure_creates_nothing(): void
    {
        $this->fakeRepository(guideStatus: 404);
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->post(route('courses.import.github.store'), ['token' => $preview['token'], 'paths' => ['labs/01-lab.md', 'notes/02-guide.md']])->assertSessionHasErrors('paths');
        $this->assertDatabaseCount('courses', 0);
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_total_size_is_checked_before_downloading(): void
    {
        $this->fakeRepository([['path' => 'huge.md', 'type' => 'blob', 'mode' => '100644', 'size' => 8 * 1024 * 1024]]);
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->post(route('courses.import.github.store'), ['token' => $preview['token'], 'paths' => ['huge.md']])->assertSessionHasErrors('paths');
        Http::assertSentCount(3);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_downloaded_content_must_fit_a_lesson(): void
    {
        $this->fakeRepository(labContent: str_repeat('a', Lesson::MAX_CONTENT_LENGTH + 1));
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->post(route('courses.import.github.store'), ['token' => $preview['token'], 'paths' => ['labs/01-lab.md']])->assertSessionHasErrors('paths');
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_invalid_utf8_download_creates_nothing(): void
    {
        $this->fakeRepository(labContent: "\xFF\xFE");
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->post(route('courses.import.github.store'), ['token' => $preview['token'], 'paths' => ['labs/01-lab.md']])->assertSessionHasErrors('paths');
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_blank_download_creates_nothing(): void
    {
        $this->fakeRepository(labContent: '   ');
        $this->actingAs(User::factory()->create());
        $preview = $this->preview();
        $this->post(route('courses.import.github.store'), ['token' => $preview['token'], 'paths' => ['labs/01-lab.md']])->assertSessionHasErrors('files');
        $this->assertDatabaseCount('courses', 0);
    }
}
