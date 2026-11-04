<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NoteController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $courseId = $request->filled('course_id') ? $request->integer('course_id') : null;

        $notes = $request->user()->notes()
            ->with(['course:id,title', 'lesson:id,course_id,title'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);

                $query->where(function (Builder $subQuery) use ($escaped): void {
                    $subQuery->whereRaw("title LIKE ? ESCAPE '!'", ["%{$escaped}%"])
                        ->orWhereRaw("body LIKE ? ESCAPE '!'", ["%{$escaped}%"]);
                });
            })
            ->when($courseId !== null, function (Builder $query) use ($courseId): void {
                $query->where('course_id', $courseId);
            })
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Notes/Index', [
            'notes' => NoteResource::collection($notes),
            'courses' => $this->courseOptions($request),
            'filters' => [
                'search' => $search,
                'course_id' => $courseId,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Notes/Create', [
            'courses' => $this->courseOptions($request),
            'initial_course_id' => $request->filled('course_id') ? $request->integer('course_id') : null,
            'initial_lesson_id' => $request->filled('lesson_id') ? $request->integer('lesson_id') : null,
        ]);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        $note = $request->user()->notes()->create($request->validatedAttributes());

        Inertia::flash('success', 'Note created.');

        return to_route('notes.show', $note);
    }

    public function show(Note $note): Response
    {
        Gate::authorize('view', $note);

        $note->load(['course:id,title', 'lesson:id,course_id,title']);

        return Inertia::render('Notes/Show', [
            'note' => new NoteResource($note),
        ]);
    }

    public function edit(Request $request, Note $note): Response
    {
        Gate::authorize('update', $note);

        return Inertia::render('Notes/Edit', [
            'note' => new NoteResource($note),
            'courses' => $this->courseOptions($request),
        ]);
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $note->update($request->validatedAttributes());

        Inertia::flash('success', 'Note updated.');

        return to_route('notes.show', $note);
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('delete', $note);

        $note->delete();

        Inertia::flash('success', 'Note deleted.');

        return to_route('notes.index');
    }

    /**
     * @return list<array{id: int, title: string, lessons: list<array{id: int, title: string}>}>
     */
    private function courseOptions(Request $request): array
    {
        return $request->user()->courses()
            ->with(['lessons' => fn ($query) => $query->select(['id', 'course_id', 'title'])->orderBy('position')])
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'lessons' => $course->lessons->map(fn (Lesson $lesson): array => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
