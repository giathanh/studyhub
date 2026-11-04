<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lesson
 */
class LessonResource extends JsonResource
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
            'course_id' => $this->course_id,
            'section' => $this->section,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'position' => $this->position,
            'completed_at' => $this->completed_at?->toDateString(),
            'content' => $this->whenHas('content'),
        ];
    }
}
