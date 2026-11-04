<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import MagicCard from '@/components/MagicCard.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Paginated } from '@/types/course';

defineProps<{
    courses: Paginated<Course>;
}>();

const secondaryLinkClass =
    'rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';
</script>

<template>
    <AppLayout title="Courses">
        <template #heading>
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Courses</h1>
                <div class="flex items-center gap-2">
                    <Link href="/courses/import" :class="secondaryLinkClass">Import Markdown</Link>
                    <ShimmerButton :as="Link" href="/courses/create">New course</ShimmerButton>
                </div>
            </div>
        </template>

        <MagicCard v-if="courses.data.length === 0" class="p-10 text-center">
            <h2 class="font-semibold text-gray-900">No courses yet</h2>
            <p class="mt-1 text-sm text-gray-600">
                Create a course by hand, or import the Markdown files you already have.
            </p>
        </MagicCard>

        <ul v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="course in courses.data" :key="course.id">
                <Link
                    :href="`/courses/${course.id}`"
                    class="block h-full rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                >
                    <MagicCard class="h-full p-5">
                        <h2 class="font-semibold wrap-anywhere text-gray-900">{{ course.title }}</h2>
                        <p v-if="course.description" class="mt-1 line-clamp-2 text-sm text-gray-600">
                            {{ course.description }}
                        </p>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>
                                    {{ course.completed_lessons_count ?? 0 }}/{{ course.lessons_count ?? 0 }}
                                    {{ course.lessons_count === 1 ? 'lesson' : 'lessons' }}
                                </span>
                                <span class="font-medium text-gray-700">{{ course.progress ?? 0 }}%</span>
                            </div>
                            <div
                                class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100"
                                role="progressbar"
                                :aria-valuenow="course.progress ?? 0"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                :aria-label="`${course.title} progress`"
                            >
                                <div
                                    class="h-full rounded-full bg-brand transition-[width] duration-300"
                                    :style="{ width: `${course.progress ?? 0}%` }"
                                />
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-gray-500">
                            {{ course.status_label }}
                            <template v-if="course.target_date">· Target: {{ course.target_date }}</template>
                        </p>
                    </MagicCard>
                </Link>
            </li>
        </ul>

        <PaginationLinks :pagination="courses" />
    </AppLayout>
</template>
