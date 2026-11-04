<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudySessionRequest;
use App\Http\Resources\StudySessionResource;
use App\Models\Lesson;
use App\Models\StudySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudySessionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', StudySession::class);
        $search = mb_substr($request->string('lesson_search')->trim()->toString(), 0, 255);
        $lessons = Lesson::query()->whereHas('course', fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($search !== '', fn ($query) => $query->whereRaw("title LIKE ? ESCAPE '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%']))
            ->orderBy('id')->paginate(50, ['id', 'course_id', 'title'], 'lessons_page')->withQueryString();

        return Inertia::render('StudySessions/Index', [
            'sessions' => StudySessionResource::collection($request->user()->studySessions()
                ->with('lesson:id,course_id,title')->orderByDesc('studied_on')->orderByDesc('id')
                ->paginate(12)->withQueryString()),
            'lessons' => JsonResource::collection($lessons->through(fn (Lesson $lesson): array => $lesson->only('id', 'title'))),
            'lesson_search' => $search,
            'today' => today()->toDateString(),
        ]);
    }

    public function store(StoreStudySessionRequest $request): RedirectResponse
    {
        $request->user()->studySessions()->create($request->validated());
        Inertia::flash('success', 'Study session recorded.');

        return to_route('study-sessions.index');
    }

    public function destroy(StudySession $studySession): RedirectResponse
    {
        Gate::authorize('delete', $studySession);
        $studySession->delete();
        Inertia::flash('success', 'Study session deleted.');

        return to_route('study-sessions.index');
    }
}
