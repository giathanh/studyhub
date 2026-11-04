<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GithubCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        if ($this->routeIs('courses.import.github.preview')) {
            return [
                'repository_url' => ['required', 'string', 'max:500', 'regex:~\Ahttps://github\.com/[a-zA-Z0-9_-]+/(?!\.{1,2}/?$)[a-zA-Z0-9_.-]+/?\z~'],
            ];
        }

        return [
            'token' => ['required', 'string', 'size:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'paths' => ['required', 'array', 'min:1', 'max:500'],
            'paths.*' => ['required', 'string', 'max:1000', 'distinct'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['repository_url.regex' => 'Enter a repository URL such as https://github.com/owner/repository.'];
    }
}
