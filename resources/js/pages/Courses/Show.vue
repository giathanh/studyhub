<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppButton from '@/components/AppButton.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatusBadge from '@/components/StatusBadge.vue';
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

const moveButtonClass =
    'flex size-7 cursor-pointer items-center justify-center rounded-md text-sm text-ink-soft transition-colors hover:bg-sunken hover:text-ink disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent';

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
            <PageHeader :title="course.title" back-href="/courses" back-label="Courses">
                <template #meta>
                    <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <StatusBadge :status="course.status" :label="course.status_label" />
                        <span v-if="course.target_date">Target: {{ course.target_date }}</span>
                    </span>
                </template>
                <template #actions>
                    <AppButton :as="Link" :href="`/notes/create?course_id=${course.id}`" variant="secondary">
                        New note
                    </AppButton>
                    <AppButton :as="Link" :href="`/courses/${course.id}/edit`" variant="secondary">Edit</AppButton>
                    <AppButton type="button" variant="danger" @click="deleteCourse">Delete</AppButton>
                    <AppButton :as="Link" :href="`/courses/${course.id}/lessons/create`">Add lesson</AppButton>
                </template>
            </PageHeader>
        </template>

        <p v-if="course.description" class="mb-6 max-w-3xl whitespace-pre-line text-ink-soft">
            {{ course.description }}
        </p>

        <div class="mb-8 max-w-3xl">
            <p class="mb-2 flex justify-between gap-3 text-sm text-ink-soft">
                <span>{{ course.completed_lessons_count ?? 0 }} of {{ lessonCountLabel }} completed</span>
                <span class="font-medium text-ink">{{ course.progress ?? 0 }}%</span>
            </p>
            <ProgressBar :value="course.progress ?? 0" label="Course progress" size="md" />
        </div>

        <EmptyState
            v-if="lessons.data.length === 0"
            title="No lessons yet"
            description="Add the first lesson to start filling this course."
        />

        <div v-else class="flex flex-col gap-8">
            <section v-for="group in lessonGroups" :key="group.key">
                <h2 v-if="group.section" class="mb-2 font-serif text-xl font-medium tracking-tight text-ink">
                    {{ group.section }}
                </h2>
                <ul class="divide-y divide-line rounded-xl border border-line bg-surface">
                    <li
                        v-for="lesson in group.lessons"
                        :key="lesson.id"
                        class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 px-4 py-2.5 text-sm"
                    >
                        <span
                            aria-hidden="true"
                            class="size-2.5 shrink-0 rounded-full"
                            :class="{
                                'bg-success': lesson.status === 'completed',
                                'bg-highlight ring-1 ring-warning-line': lesson.status === 'in_progress',
                                'border border-line-strong': lesson.status === 'not_started',
                            }"
                        />
                        <Link
                            :href="`/courses/${course.id}/lessons/${lesson.id}`"
                            class="min-w-0 flex-1 basis-48 rounded font-medium wrap-anywhere text-ink hover:text-brand"
                        >
                            {{ lesson.title }}
                        </Link>
                        <div class="flex shrink-0 items-center gap-1">
                            <select
                                :aria-label="`Status for ${lesson.title}`"
                                :value="lesson.status"
                                class="mr-1 cursor-pointer rounded-md border border-line bg-surface px-2 py-1 text-xs font-medium text-ink-soft transition-colors hover:border-line-strong focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none"
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
