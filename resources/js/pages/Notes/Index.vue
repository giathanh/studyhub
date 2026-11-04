<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MagicCard from '@/components/MagicCard.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
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

const inputClass =
    'block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs transition-shadow focus:border-brand focus:ring-3 focus:ring-brand/20 focus:outline-none';
</script>

<template>
    <AppLayout title="Notes">
        <template #heading>
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Notes</h1>
                <ShimmerButton :as="Link" href="/notes/create">New note</ShimmerButton>
            </div>
        </template>

        <form
            class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4"
            @submit.prevent="applyFilters"
        >
            <div class="min-w-52 flex-1">
                <label for="search" class="mb-1 block text-xs font-medium text-gray-700">
                    Search title or body
                </label>
                <input
                    id="search"
                    v-model="search"
                    type="search"
                    name="search"
                    placeholder="e.g. aws s3 sync, IAM policy…"
                    :class="inputClass"
                />
            </div>

            <div class="min-w-48">
                <label for="course_filter" class="mb-1 block text-xs font-medium text-gray-700">
                    Course
                </label>
                <select
                    id="course_filter"
                    v-model="courseId"
                    name="course_id"
                    :class="inputClass"
                    @change="applyFilters"
                >
                    <option value="">All courses</option>
                    <option v-for="course in courses" :key="course.id" :value="String(course.id)">
                        {{ course.title }}
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="cursor-pointer rounded-lg border border-gray-200 bg-gray-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                >
                    Search
                </button>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="cursor-pointer rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                    @click="clearFilters"
                >
                    Clear
                </button>
            </div>
        </form>

        <MagicCard v-if="notes.data.length === 0" class="p-10 text-center">
            <h2 class="font-semibold text-gray-900">
                {{ hasActiveFilters ? 'No matching notes' : 'No notes yet' }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{
                    hasActiveFilters
                        ? 'Try a different keyword or course filter.'
                        : 'Save commands, links, and study takeaways in Markdown.'
                }}
            </p>
        </MagicCard>

        <ul v-else class="space-y-3">
            <li v-for="note in notes.data" :key="note.id">
                <Link
                    :href="`/notes/${note.id}`"
                    class="block rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                >
                    <MagicCard class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h2 class="font-semibold wrap-anywhere text-gray-900">{{ note.title }}</h2>
                            <span v-if="note.updated_at" class="shrink-0 text-xs text-gray-500">
                                {{ note.updated_at }}
                            </span>
                        </div>
                        <p v-if="note.excerpt" class="mt-1.5 line-clamp-2 text-sm text-gray-600">
                            {{ note.excerpt }}
                        </p>
                        <div
                            v-if="note.course || note.lesson"
                            class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-500"
                        >
                            <span
                                v-if="note.course"
                                class="rounded-md bg-brand/10 px-2 py-0.5 font-medium text-brand"
                            >
                                {{ note.course.title }}
                            </span>
                            <span
                                v-if="note.lesson"
                                class="rounded-md bg-gray-100 px-2 py-0.5 font-medium text-gray-700"
                            >
                                {{ note.lesson.title }}
                            </span>
                        </div>
                    </MagicCard>
                </Link>
            </li>
        </ul>

        <PaginationLinks :pagination="notes" />
    </AppLayout>
</template>
