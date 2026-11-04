<?php

namespace App\Http\Requests;

use App\Models\StudySession;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StudySession::class);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'studied_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'lesson_id' => ['nullable', 'integer', Rule::exists('lessons', 'id')->where(
                fn (Builder $query) => $query->whereIn('course_id', $this->user()->courses()->select('id')),
            )],
        ];
    }
}
