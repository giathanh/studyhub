<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import MagicCard from '@/components/MagicCard.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Lesson, Paginated } from '@/types/course';

const props = defineProps<{
    course: { data: Course };
    lessons: Paginated<Lesson>;
}>();

const course = computed(() => props.course.data);

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
                        {{ lessonCountLabel }} · {{ course.status_label }}
                        <template v-if="course.target_date">· Target: {{ course.target_date }}</template>
                    </p>
                </div>
                <div class="flex items-center gap-2">
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
                    <li v-for="lesson in group.lessons" :key="lesson.id">
                        <Link
                            :href="`/courses/${course.id}/lessons/${lesson.id}`"
                            class="flex items-center justify-between gap-4 px-4 py-3 text-sm transition-colors hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        >
                            <span class="min-w-0 wrap-anywhere text-gray-900">{{ lesson.title }}</span>
                            <span class="shrink-0 text-gray-500">{{ lesson.status_label }}</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>

        <PaginationLinks :pagination="lessons" />
    </AppLayout>
</template>
