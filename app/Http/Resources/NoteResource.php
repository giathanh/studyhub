<?php

namespace App\Http\Resources;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Note
 */
class NoteResource extends JsonResource
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
            'excerpt' => Str::limit($this->body ?? '', 160),
            'body' => $this->when(
                $request->routeIs('notes.show', 'notes.edit'),
                $this->body,
            ),
            'course_id' => $this->course_id,
            'lesson_id' => $this->lesson_id,
            'course' => $this->whenLoaded('course', fn (): ?array => $this->course ? [
                'id' => $this->course->id,
                'title' => $this->course->title,
            ] : null),
            'lesson' => $this->whenLoaded('lesson', fn (): ?array => $this->lesson ? [
                'id' => $this->lesson->id,
                'course_id' => $this->lesson->course_id,
                'title' => $this->lesson->title,
            ] : null),
            'updated_at' => $this->updated_at?->toDateString(),
        ];
    }
}
