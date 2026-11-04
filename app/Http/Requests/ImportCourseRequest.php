<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The browser sends file text as JSON, so `path` is only a label for
     * ordering and titles. It is never used to touch the filesystem.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'files' => ['required', 'array', 'max:500'],
            'files.*.path' => ['required', 'string', 'max:1000', 'regex:/\.(md|markdown)$/i'],
            'files.*.content' => ['nullable', 'string', 'max:'.Lesson::MAX_CONTENT_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Choose at least one markdown file.',
            'files.*.path.regex' => 'Only .md and .markdown files can be imported.',
            'files.*.content.max' => 'This file is too long for a single lesson.',
        ];
    }
}
