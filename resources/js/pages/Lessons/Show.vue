<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import MagicCard from '@/components/MagicCard.vue';
import MarkdownContent from '@/components/MarkdownContent.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Lesson, Note, Paginated, StatusOption } from '@/types/course';

const props = defineProps<{
    course: { data: Course };
    lesson: { data: Lesson };
    statuses: StatusOption[];
    notes: Paginated<Note>;
}>();

const lessonUrl = `/courses/${props.course.data.id}/lessons/${props.lesson.data.id}`;

const secondaryButtonClass =
    'cursor-pointer rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';

const dangerButtonClass =
    'cursor-pointer rounded-lg border border-red-600 bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:border-red-700 hover:bg-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600';

function changeStatus(status: string): void {
    router.patch(`${lessonUrl}/status`, { status }, { preserveScroll: true });
}

function deleteLesson(): void {
    if (window.confirm(`Delete "${props.lesson.data.title}"? This cannot be undone.`)) {
        router.delete(lessonUrl);
    }
}
</script>

<template>
    <AppLayout :title="lesson.data.title">
        <template #heading>
            <Link :href="`/courses/${course.data.id}`" class="text-sm font-medium text-brand">
                ← {{ course.data.title }}
            </Link>
            <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p v-if="lesson.data.section" class="text-sm font-medium text-gray-500">
                        {{ lesson.data.section }}
                    </p>
                    <h1 class="text-2xl font-semibold tracking-tight wrap-anywhere text-gray-900">
                        {{ lesson.data.title }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ lesson.data.status_label }}
                        <template v-if="lesson.data.completed_at">
                            · Completed {{ lesson.data.completed_at }}
                        </template>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        aria-label="Lesson status"
                        :value="lesson.data.status"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none"
                        @change="changeStatus(($event.target as HTMLSelectElement).value)"
                    >
                        <option v-for="option in statuses" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <Link :href="`${lessonUrl}/edit`" :class="[secondaryButtonClass, 'text-gray-700']">
                        Edit
                    </Link>
                    <button type="button" :class="dangerButtonClass" @click="deleteLesson">
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <MagicCard class="p-6">
            <MarkdownContent v-if="lesson.data.content" :content="lesson.data.content" />
            <p v-else class="text-sm text-gray-600">This lesson has no content yet.</p>
        </MagicCard>

        <section class="mt-8">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-900">Notes for this lesson</h2>
                <ShimmerButton
                    :as="Link"
                    :href="`/notes/create?course_id=${course.data.id}&lesson_id=${lesson.data.id}`"
                >
                    Add note
                </ShimmerButton>
            </div>

            <MagicCard v-if="notes.data.length === 0" class="p-6 text-center">
                <p class="text-sm text-gray-600">No notes linked to this lesson yet.</p>
            </MagicCard>

            <ul v-else class="divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white">
                <li v-for="note in notes.data" :key="note.id">
                    <Link
                        :href="`/notes/${note.id}`"
                        class="block px-4 py-3 transition-colors hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                    >
                        <p class="text-sm font-medium wrap-anywhere text-gray-900">{{ note.title }}</p>
                        <p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ note.excerpt }}</p>
                    </Link>
                </li>
            </ul>

            <PaginationLinks :pagination="notes" />
        </section>
    </AppLayout>
</template>
