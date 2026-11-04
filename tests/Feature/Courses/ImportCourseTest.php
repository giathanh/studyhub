<?php

namespace Tests\Feature\Courses;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ImportCourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('courses.import.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Courses/Import'));
    }

    public function test_guest_cannot_import(): void
    {
        $this->post(route('courses.import.store'), [])->assertRedirect(route('login'));
    }

    public function test_each_file_becomes_a_lesson_grouped_by_top_level_folder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('courses.import.store'), [
            'title' => 'Engineering Lab',
            'files' => [
                ['path' => '10-security/01-owasp.md', 'content' => "# OWASP\n\nInjection"],
                ['path' => '02-php/10-symfony.md', 'content' => "# Symfony\n\nBundles"],
                ['path' => '02-php/02-runtime.md', 'content' => "# Runtime\n\nFPM"],
            ],
        ]);

        $course = $user->courses()->sole();
        $lessons = $course->lessons()->orderBy('position')->get();

        $response->assertRedirect(route('courses.show', $course));

        $this->assertSame('Engineering Lab', $course->title);
        $this->assertSame(['Runtime', 'Symfony', 'OWASP'], $lessons->pluck('title')->all());
        $this->assertSame([1, 2, 3], $lessons->pluck('position')->all());
        $this->assertSame(['Php', 'Php', 'Security'], $lessons->pluck('section')->all());
        $this->assertSame('FPM', $lessons->first()->content);
    }

    public function test_single_file_splits_into_lessons_at_second_level_headings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('courses.import.store'), [
            'files' => [[
                'path' => 'roadmap.md',
                'content' => "# AWS Roadmap\n\nLearning order.\n\n## 1. IAM\n\n- [ ] Users\n\n## 2. VPC\n\n- [ ] Subnets\n",
            ]],
        ]);

        $course = $user->courses()->sole();
        $lessons = $course->lessons()->orderBy('position')->get();

        $this->assertSame('AWS Roadmap', $course->title);
        $this->assertSame('Learning order.', $course->description);
        $this->assertSame(['1. IAM', '2. VPC'], $lessons->pluck('title')->all());
        $this->assertSame('- [ ] Users', $lessons->first()->content);
    }

    public function test_headings_inside_code_fences_do_not_split_or_title_a_lesson(): void
    {
        $user = User::factory()->create();
        $snippet = "```bash\n# 1. Delete the bucket\n## not a heading\naws s3 rb\n```";

        $this->actingAs($user)->post(route('courses.import.store'), [
            'files' => [['path' => 'lab.md', 'content' => "{$snippet}\n\n# Lab 02\n\n## Cleanup\n\n{$snippet}\n"]],
        ]);

        $course = $user->courses()->sole();
        $lesson = $course->lessons()->sole();

        $this->assertSame('Lab 02', $course->title);
        $this->assertSame('Cleanup', $lesson->title);
        $this->assertSame($snippet, $lesson->content);
    }

    public function test_file_without_headings_is_titled_from_its_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('courses.import.store'), [
            'files' => [['path' => '03-cost-management.md', 'content' => "\u{FEFF}Plain text\r\nSecond line"]],
        ]);

        $course = $user->courses()->sole();
        $lesson = $course->lessons()->sole();

        $this->assertSame('Cost Management', $course->title);
        $this->assertSame('Cost Management', $lesson->title);
        $this->assertSame("Plain text\nSecond line", $lesson->content);
    }

    public function test_long_folder_and_file_names_are_cut_to_fit_their_columns(): void
    {
        $user = User::factory()->create();
        $longName = str_repeat('a', 400);

        $this->actingAs($user)->post(route('courses.import.store'), [
            'files' => [
                ['path' => "{$longName}/{$longName}.md", 'content' => 'Body'],
                ['path' => 'b.md', 'content' => 'Body'],
            ],
        ])->assertRedirect();

        $lesson = $user->courses()->sole()->lessons()->where('position', 1)->sole();

        $this->assertSame(255, mb_strlen($lesson->section));
        $this->assertSame(255, mb_strlen($lesson->title));
    }

    public function test_empty_files_are_skipped(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('courses.import.store'), [
            'title' => 'Mixed',
            'files' => [
                ['path' => 'a.md', 'content' => "# A\n\nBody"],
                ['path' => 'b.md', 'content' => '   '],
                ['path' => 'c.md', 'content' => "# C\n\nBody"],
            ],
        ]);

        $this->assertSame(['A', 'C'], $user->courses()->sole()->lessons()->orderBy('position')->pluck('title')->all());
    }

    public function test_import_of_only_empty_files_creates_nothing(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('courses.import.store'), ['files' => [['path' => 'a.md', 'content' => '']]])
            ->assertSessionHasErrors(['files' => 'The selected files are empty.']);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_import_requires_files(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('courses.import.store'), ['title' => 'AWS'])
            ->assertSessionHasErrors(['files' => 'Choose at least one markdown file.']);
    }

    public function test_non_markdown_file_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('courses.import.store'), ['files' => [['path' => 'shell.php', 'content' => 'text']]])
            ->assertSessionHasErrors(['files.0.path' => 'Only .md and .markdown files can be imported.']);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_oversized_file_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('courses.import.store'), [
                'files' => [['path' => 'big.md', 'content' => str_repeat('a', Lesson::MAX_CONTENT_LENGTH + 1)]],
            ])
            ->assertSessionHasErrors(['files.0.content' => 'This file is too long for a single lesson.']);
    }

    public function test_imported_course_belongs_to_the_importer_only(): void
    {
        $importer = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($importer)->post(route('courses.import.store'), [
            'files' => [['path' => 'a.md', 'content' => '# A']],
        ]);

        $this->assertSame(1, $importer->courses()->count());
        $this->assertSame(0, $other->courses()->count());
    }
}
