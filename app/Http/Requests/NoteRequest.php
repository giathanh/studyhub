<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use App\Models\Note;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class NoteRequest extends FormRequest
{
    /**
     * Any signed-in user may create a note; only the owner may change one.
     */
    public function authorize(): bool
    {
        $note = $this->route('note');

        return ! $note instanceof Note || $this->user()->can('update', $note);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:'.Note::MAX_BODY_LENGTH],
            'course_id' => [
                'nullable',
                'integer',
                Rule::exists('courses', 'id')->where('user_id', $this->user()->id),
            ],
            'lesson_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Verify that the chosen lesson belongs to the user and matches the chosen course.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('lesson_id') || $validator->errors()->has('lesson_id')) {
                    return;
                }

                $lesson = Lesson::with('course:id,user_id')->find($this->integer('lesson_id'));

                if (! $lesson || $lesson->course->user_id !== $this->user()->id) {
                    $validator->errors()->add('lesson_id', 'The selected lesson is invalid.');

                    return;
                }

                if ($this->filled('course_id') && $lesson->course_id !== $this->integer('course_id')) {
                    $validator->errors()->add('lesson_id', 'The selected lesson does not belong to the selected course.');
                }
            },
        ];
    }

    /**
     * Normalize validated note fields, pairing a lesson with its course when course_id was omitted.
     *
     * @return array{title: string, body: string, course_id: ?int, lesson_id: ?int}
     */
    public function validatedAttributes(): array
    {
        $validated = $this->validated();
        $lessonId = isset($validated['lesson_id']) ? (int) $validated['lesson_id'] : null;
        $courseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;

        if ($lessonId !== null && $courseId === null) {
            $courseId = Lesson::whereKey($lessonId)->value('course_id');
        }

        return [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
        ];
    }
}
