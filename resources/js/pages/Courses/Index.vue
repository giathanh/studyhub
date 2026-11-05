<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Paginated } from '@/types/course';

defineProps<{
    courses: Paginated<Course>;
}>();
</script>

<template>
    <AppLayout title="Courses">
        <template #heading>
            <PageHeader title="Courses">
                <template #actions>
                    <AppButton :as="Link" href="/courses/import" variant="secondary">Import Markdown</AppButton>
                    <AppButton :as="Link" href="/courses/create">New course</AppButton>
                </template>
            </PageHeader>
        </template>

        <EmptyState
            v-if="courses.data.length === 0"
            title="No courses yet"
            description="Create a course by hand, or import the Markdown files you already have."
        />

        <ul v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="course in courses.data" :key="course.id" class="min-w-0">
                <AppCard
                    :as="Link"
                    :href="`/courses/${course.id}`"
                    class="flex h-full flex-col p-5 transition-colors hover:border-line-strong"
                >
                    <span class="flex flex-wrap items-center justify-between gap-2">
                        <StatusBadge :status="course.status" :label="course.status_label" />
                        <span v-if="course.target_date" class="text-xs text-ink-faint">
                            Target: {{ course.target_date }}
                        </span>
                    </span>
                    <h2 class="mt-3 font-serif text-xl leading-snug font-medium tracking-tight wrap-anywhere text-ink">
                        {{ course.title }}
                    </h2>
                    <p v-if="course.description" class="mt-1 line-clamp-2 text-sm text-ink-soft">
                        {{ course.description }}
                    </p>
                    <span class="mt-auto flex flex-col gap-2 pt-5">
                        <span class="flex justify-between gap-2 text-xs text-ink-soft">
                            <span>
                                {{ course.completed_lessons_count ?? 0 }} of {{ course.lessons_count ?? 0 }}
                                {{ course.lessons_count === 1 ? 'lesson' : 'lessons' }}
                            </span>
                            <span class="font-medium text-ink">{{ course.progress ?? 0 }}%</span>
                        </span>
                        <ProgressBar :value="course.progress ?? 0" :label="`${course.title} progress`" />
                    </span>
                </AppCard>
            </li>
        </ul>

        <PaginationLinks :pagination="courses" />
    </AppLayout>
</template>
