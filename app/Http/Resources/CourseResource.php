<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'target_date' => $this->target_date?->toDateString(),
            'lessons_count' => $this->whenCounted('lessons'),
            'completed_lessons_count' => $this->whenCounted('completed_lessons_count'),
            'progress' => $this->when(
                $this->lessons_count !== null && $this->completed_lessons_count !== null,
                fn (): int => $this->lessons_count > 0
                    ? (int) round(($this->completed_lessons_count / $this->lessons_count) * 100)
                    : 0,
            ),
        ];
    }
}
