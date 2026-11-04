<?php

namespace App\Http\Resources;

use App\Models\StudySession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StudySession */
class StudySessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'studied_on' => $this->studied_on->toDateString(),
            'minutes' => $this->minutes,
            'lesson' => $this->whenLoaded('lesson', fn (): array => [
                'id' => $this->lesson->id,
                'course_id' => $this->lesson->course_id,
                'title' => $this->lesson->title,
            ]),
        ];
    }
}
