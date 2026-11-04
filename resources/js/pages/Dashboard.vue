<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PaginationLinks from '@/components/PaginationLinks.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Paginated } from '@/types/course';

defineProps<{
    courses: Paginated<Course>;
    lessons: Paginated<{ id: number; course_id: number; title: string; course_title: string }>;
    notes: Paginated<{ id: number; title: string }>;
    week: { start: string; end: string; minutes: number };
}>();

const linkClass = 'font-medium text-brand underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';
</script>

<template>
    <AppLayout title="Dashboard">
        <section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-6">
            <div>
                <h2 class="text-sm font-medium text-gray-600">Study time this week · Monday–Sunday</h2>
                <p class="mt-1 text-3xl font-semibold text-gray-900">{{ week.minutes }} <span class="text-base font-normal">minutes</span></p>
                <p class="mt-1 text-sm text-gray-500">{{ week.start }} – {{ week.end }}</p>
            </div>
            <Link href="/study-sessions" :class="linkClass">Log study time →</Link>
        </section>

        <section class="mb-6 rounded-2xl border border-gray-200 bg-white p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">Courses in progress</h2>
                <Link href="/courses" :class="linkClass">All courses</Link>
            </div>
            <p v-if="courses.data.length === 0" class="text-sm text-gray-600">No courses in progress. Open a course and set its status to “In progress” to start.</p>
            <ul v-else class="grid gap-5 sm:grid-cols-2">
                <li v-for="course in courses.data" :key="course.id" class="min-w-0">
                    <Link :href="`/courses/${course.id}`" :class="[linkClass, 'wrap-anywhere']">{{ course.title }}</Link>
                    <p class="mt-2 text-sm text-gray-600">{{ course.completed_lessons_count }} / {{ course.lessons_count }} lessons · {{ course.progress }}%</p>
                    <progress :value="course.progress" max="100" :aria-label="`${course.title} progress`" class="mt-1 h-2 w-full accent-brand" />
                </li>
            </ul>
            <PaginationLinks :pagination="courses" />
        </section>

        <div class="grid items-start gap-6 md:grid-cols-2">
            <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">Unfinished lessons</h2>
                <p v-if="lessons.data.length === 0" class="text-sm text-gray-600">No unfinished lessons. Add a lesson from a course when you are ready.</p>
                <ul v-else class="flex flex-col gap-4">
                    <li v-for="lesson in lessons.data" :key="lesson.id">
                        <Link :href="`/courses/${lesson.course_id}/lessons/${lesson.id}`" :class="[linkClass, 'wrap-anywhere']">{{ lesson.title }}</Link>
                        <p class="text-sm wrap-anywhere text-gray-500">{{ lesson.course_title }}</p>
                    </li>
                </ul>
                <PaginationLinks :pagination="lessons" />
            </section>
            <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold">Recent notes</h2>
                    <Link href="/notes/create" :class="linkClass">New note</Link>
                </div>
                <p v-if="notes.data.length === 0" class="text-sm text-gray-600">No notes yet. Save a takeaway from your next lesson.</p>
                <ul v-else class="flex flex-col gap-4">
                    <li v-for="note in notes.data" :key="note.id">
                        <Link :href="`/notes/${note.id}`" :class="[linkClass, 'wrap-anywhere']">{{ note.title }}</Link>
                    </li>
                </ul>
                <PaginationLinks :pagination="notes" />
            </section>
        </div>
    </AppLayout>
</template>
