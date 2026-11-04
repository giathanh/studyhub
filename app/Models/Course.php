<?php

namespace App\Models;

use App\Enums\StudyStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'status', 'target_date'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StudyStatus::class,
            'target_date' => 'date',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withLessonCounts(Builder $query): void
    {
        $query->withCount([
            'lessons',
            'lessons as completed_lessons_count' => fn (Builder $lessonQuery) => $lessonQuery
                ->where('status', StudyStatus::Completed),
        ]);
    }

    public function loadLessonCounts(): self
    {
        return $this->loadCount([
            'lessons',
            'lessons as completed_lessons_count' => fn (Builder $lessonQuery) => $lessonQuery
                ->where('status', StudyStatus::Completed),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * @return HasMany<Note, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
