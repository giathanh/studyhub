<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import MarkdownContent from '@/components/MarkdownContent.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Lesson, Note, Paginated, StatusOption } from '@/types/course';

const props = defineProps<{
    course: { data: Course };
    lesson: { data: Lesson };
    statuses: StatusOption[];
    notes: Paginated<Note>;
}>();

const lessonUrl = `/courses/${props.course.data.id}/lessons/${props.lesson.data.id}`;

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
            <PageHeader :title="lesson.data.title" :back-href="`/courses/${course.data.id}`" :back-label="course.data.title">
                <template v-if="lesson.data.section" #eyebrow>{{ lesson.data.section }}</template>
                <template v-if="lesson.data.completed_at" #meta>Completed {{ lesson.data.completed_at }}</template>
                <template #actions>
                    <select
                        aria-label="Lesson status"
                        :value="lesson.data.status"
                        class="control w-auto cursor-pointer py-2.5 font-medium"
                        @change="changeStatus(($event.target as HTMLSelectElement).value)"
                    >
                        <option v-for="option in statuses" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <AppButton :as="Link" :href="`${lessonUrl}/edit`" variant="secondary">Edit</AppButton>
                    <AppButton type="button" variant="danger" @click="deleteLesson">Delete</AppButton>
                </template>
            </PageHeader>
        </template>

        <div class="grid items-start gap-8 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <AppCard as="article" class="min-w-0 px-6 py-8 sm:px-10 sm:py-10">
                <MarkdownContent v-if="lesson.data.content" :content="lesson.data.content" class="mx-auto max-w-[68ch]" />
                <p v-else class="text-sm text-ink-soft">This lesson has no content yet.</p>
            </AppCard>

            <section aria-labelledby="lesson-notes-heading" class="min-w-0 xl:sticky xl:top-6">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 id="lesson-notes-heading" class="font-serif text-xl font-medium tracking-tight text-ink">Notes</h2>
                    <AppButton
                        :as="Link"
                        :href="`/notes/create?course_id=${course.data.id}&lesson_id=${lesson.data.id}`"
                        variant="secondary"
                        size="sm"
                    >
                        Add note
                    </AppButton>
                </div>

                <p v-if="notes.data.length === 0" class="text-sm text-ink-soft">No notes linked to this lesson yet.</p>

                <AppCard v-else as="ul" class="p-1.5">
                    <li v-for="note in notes.data" :key="note.id">
                        <Link :href="`/notes/${note.id}`" class="block rounded-lg px-3 py-2.5 transition-colors hover:bg-sunken">
                            <span class="block text-sm font-medium wrap-anywhere text-ink">{{ note.title }}</span>
                            <span class="mt-0.5 line-clamp-2 text-xs text-ink-faint">{{ note.excerpt }}</span>
                        </Link>
                    </li>
                </AppCard>

                <PaginationLinks :pagination="notes" />
            </section>
        </div>
    </AppLayout>
</template>
