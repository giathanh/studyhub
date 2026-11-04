<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import MagicCard from '@/components/MagicCard.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Lesson, Paginated, StatusOption } from '@/types/course';

const props = defineProps<{
    course: { data: Course };
    lessons: Paginated<Lesson>;
    statuses: StatusOption[];
}>();

const course = computed(() => props.course.data);

const lessonIndexById = computed(() => {
    const map = new Map<number, number>();
    props.lessons.data.forEach((lesson, index) => {
        map.set(lesson.id, index);
    });
    return map;
});

const lessonGroups = computed(() => {
    const groups: { key: number; section: string | null; lessons: Lesson[] }[] = [];

    for (const lesson of props.lessons.data) {
        const lastGroup = groups.at(-1);

        if (lastGroup && lastGroup.section === lesson.section) {
            lastGroup.lessons.push(lesson);
        } else {
            groups.push({ key: lesson.id, section: lesson.section, lessons: [lesson] });
        }
    }

    return groups;
});

const lessonCountLabel = computed(
    () => `${course.value.lessons_count} ${course.value.lessons_count === 1 ? 'lesson' : 'lessons'}`,
);

const secondaryButtonClass =
    'cursor-pointer rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';

const moveButtonClass =
    'cursor-pointer rounded-md border border-gray-200 bg-white px-2 py-1 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:cursor-not-allowed disabled:opacity-40';

function isFirstLesson(lessonId: number): boolean {
    return props.lessons.meta.current_page === 1 && (lessonIndexById.value.get(lessonId) ?? 0) === 0;
}

function isLastLesson(lessonId: number): boolean {
    return props.lessons.meta.current_page === props.lessons.meta.last_page
        && (lessonIndexById.value.get(lessonId) ?? 0) === props.lessons.data.length - 1;
}

function moveLesson(lessonId: number, direction: 'up' | 'down'): void {
    router.patch(
        `/courses/${course.value.id}/lessons/${lessonId}/move?page=${props.lessons.meta.current_page}`,
        { direction },
        { preserveScroll: true },
    );
}

function changeLessonStatus(lessonId: number, status: string): void {
    router.patch(
        `/courses/${course.value.id}/lessons/${lessonId}/status`,
        { status },
        { preserveScroll: true },
    );
}

function deleteCourse(): void {
    const isConfirmed = window.confirm(
        `Delete "${course.value.title}" and its ${lessonCountLabel.value}? Linked notes are kept. This cannot be undone.`,
    );

    if (isConfirmed) {
        router.delete(`/courses/${course.value.id}`);
    }
}
</script>

<template>
    <AppLayout :title="course.title">
        <template #heading>
            <Link href="/courses" class="text-sm font-medium text-brand">← Courses</Link>
            <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight wrap-anywhere text-gray-900">
                        {{ course.title }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ course.completed_lessons_count ?? 0 }}/{{ course.lessons_count ?? 0 }} completed ({{
                            course.progress ?? 0
                        }}%) · {{ course.status_label }}
                        <template v-if="course.target_date">· Target: {{ course.target_date }}</template>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="`/notes/create?course_id=${course.id}`"
                        :class="[secondaryButtonClass, 'text-gray-700']"
                    >
                        New note
                    </Link>
                    <Link :href="`/courses/${course.id}/edit`" :class="[secondaryButtonClass, 'text-gray-700']">
                        Edit
                    </Link>
                    <button type="button" :class="[secondaryButtonClass, 'text-red-700']" @click="deleteCourse">
                        Delete
                    </button>
                    <ShimmerButton :as="Link" :href="`/courses/${course.id}/lessons/create`">
                        Add lesson
                    </ShimmerButton>
                </div>
            </div>
        </template>

        <div class="mb-6">
            <div
                class="h-2 w-full overflow-hidden rounded-full bg-gray-200"
                role="progressbar"
                :aria-valuenow="course.progress ?? 0"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="Course progress"
            >
                <div
                    class="h-full rounded-full bg-brand transition-[width] duration-300"
                    :style="{ width: `${course.progress ?? 0}%` }"
                />
            </div>
        </div>

        <p v-if="course.description" class="mb-6 max-w-3xl whitespace-pre-line text-gray-600">
            {{ course.description }}
        </p>

        <MagicCard v-if="lessons.data.length === 0" class="p-10 text-center">
            <h2 class="font-semibold text-gray-900">No lessons yet</h2>
            <p class="mt-1 text-sm text-gray-600">Add the first lesson to start filling this course.</p>
        </MagicCard>

        <div v-else class="space-y-6">
            <section v-for="group in lessonGroups" :key="group.key">
                <h2 v-if="group.section" class="mb-2 text-sm font-semibold text-gray-900">
                    {{ group.section }}
                </h2>
                <ul class="divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white">
                    <li
                        v-for="lesson in group.lessons"
                        :key="lesson.id"
                        class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm"
                    >
                        <Link
                            :href="`/courses/${course.id}/lessons/${lesson.id}`"
                            class="min-w-0 flex-1 font-medium wrap-anywhere text-gray-900 hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        >
                            {{ lesson.title }}
                        </Link>
                        <div class="flex shrink-0 items-center gap-2">
                            <select
                                :aria-label="`Status for ${lesson.title}`"
                                :value="lesson.status"
                                class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none"
                                @change="changeLessonStatus(lesson.id, ($event.target as HTMLSelectElement).value)"
                            >
                                <option v-for="option in statuses" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <button
                                type="button"
                                :aria-label="`Move ${lesson.title} up`"
                                :disabled="isFirstLesson(lesson.id)"
                                :class="moveButtonClass"
                                @click="moveLesson(lesson.id, 'up')"
                            >
                                ↑
                            </button>
                            <button
                                type="button"
                                :aria-label="`Move ${lesson.title} down`"
                                :disabled="isLastLesson(lesson.id)"
                                :class="moveButtonClass"
                                @click="moveLesson(lesson.id, 'down')"
                            >
                                ↓
                            </button>
                        </div>
                    </li>
                </ul>
            </section>
        </div>

        <PaginationLinks :pagination="lessons" />
    </AppLayout>
</template>
