<?php

namespace App\Http\Requests;

use App\Enums\StudyStatus;
use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    /**
     * Any signed-in user may create a course; only the owner may change one.
     */
    public function authorize(): bool
    {
        $course = $this->route('course');

        return ! $course instanceof Course || $this->user()->can('update', $course);
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
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(StudyStatus::class)],
            'target_date' => ['nullable', 'date'],
        ];
    }
}
