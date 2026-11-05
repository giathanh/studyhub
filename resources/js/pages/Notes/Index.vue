<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CourseOption, Note, NoteFilters, Paginated } from '@/types/course';

const props = defineProps<{
    notes: Paginated<Note>;
    courses: CourseOption[];
    filters: NoteFilters;
}>();

const search = ref(props.filters.search ?? '');
const courseId = ref(props.filters.course_id ? String(props.filters.course_id) : '');

const hasActiveFilters = computed(
    () => props.filters.search.trim() !== '' || props.filters.course_id !== null,
);

function applyFilters(): void {
    const query: Record<string, string> = {};

    if (search.value.trim() !== '') {
        query.search = search.value.trim();
    }

    if (courseId.value !== '') {
        query.course_id = courseId.value;
    }

    router.get('/notes', query, { preserveState: true, replace: true });
}

function clearFilters(): void {
    search.value = '';
    courseId.value = '';
    router.get('/notes');
}
</script>

<template>
    <AppLayout title="Notes">
        <template #heading>
            <PageHeader title="Notes">
                <template #actions>
                    <AppButton :as="Link" href="/notes/create">New note</AppButton>
                </template>
            </PageHeader>
        </template>

        <form class="mb-6 flex flex-wrap items-end gap-3" @submit.prevent="applyFilters">
            <div class="min-w-52 flex-1">
                <label for="search" class="mb-1.5 block text-sm font-medium text-ink">Search title or body</label>
                <input
                    id="search"
                    v-model="search"
                    type="search"
                    name="search"
                    placeholder="e.g. aws s3 sync, IAM policy…"
                    class="control"
                />
            </div>

            <div class="min-w-48">
                <label for="course_filter" class="mb-1.5 block text-sm font-medium text-ink">Course</label>
                <select id="course_filter" v-model="courseId" name="course_id" class="control" @change="applyFilters">
                    <option value="">All courses</option>
                    <option v-for="course in courses" :key="course.id" :value="String(course.id)">
                        {{ course.title }}
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <AppButton type="submit" variant="secondary">Search</AppButton>
                <AppButton v-if="hasActiveFilters" type="button" variant="ghost" @click="clearFilters">Clear</AppButton>
            </div>
        </form>

        <EmptyState
            v-if="notes.data.length === 0"
            :title="hasActiveFilters ? 'No matching notes' : 'No notes yet'"
            :description="
                hasActiveFilters
                    ? 'Try a different keyword or course filter.'
                    : 'Save commands, links, and study takeaways in Markdown.'
            "
        />

        <ul v-else class="grid gap-4 md:grid-cols-2">
            <li v-for="note in notes.data" :key="note.id" class="min-w-0">
                <AppCard
                    :as="Link"
                    :href="`/notes/${note.id}`"
                    class="flex h-full flex-col p-5 transition-colors hover:border-line-strong"
                >
                    <h2 class="font-serif text-lg leading-snug font-medium wrap-anywhere text-ink">{{ note.title }}</h2>
                    <p v-if="note.excerpt" class="mt-1 line-clamp-2 text-sm text-ink-soft">{{ note.excerpt }}</p>
                    <span class="mt-auto flex flex-wrap items-center gap-2 pt-4 text-xs text-ink-faint">
                        <span v-if="note.course" class="rounded-md bg-highlight-soft px-2 py-0.5 font-medium text-ink">
                            {{ note.course.title }}
                        </span>
                        <span v-if="note.lesson" class="rounded-md bg-sunken px-2 py-0.5 font-medium text-ink-soft">
                            {{ note.lesson.title }}
                        </span>
                        <span v-if="note.updated_at" class="ml-auto">{{ note.updated_at }}</span>
                    </span>
                </AppCard>
            </li>
        </ul>

        <PaginationLinks :pagination="notes" />
    </AppLayout>
</template>
