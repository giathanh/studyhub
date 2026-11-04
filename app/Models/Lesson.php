<?php

namespace App\Models;

use App\Enums\StudyStatus;
use Carbon\CarbonInterface;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['section', 'title', 'content', 'status', 'position', 'completed_at'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /**
     * The longest markdown body a lesson accepts, whether typed or imported.
     */
    public const int MAX_CONTENT_LENGTH = 200_000;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StudyStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Pair a status change with its matching completion timestamp.
     *
     * @return array{status: StudyStatus, completed_at: ?CarbonInterface}
     */
    public function statusAttributes(StudyStatus $status): array
    {
        return [
            'status' => $status,
            'completed_at' => $status === StudyStatus::Completed
                ? ($this->completed_at ?? now())
                : null,
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
