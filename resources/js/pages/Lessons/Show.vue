<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import MagicCard from '@/components/MagicCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Lesson } from '@/types/course';

const props = defineProps<{
    course: { data: Course };
    lesson: { data: Lesson };
}>();

const lessonUrl = `/courses/${props.course.data.id}/lessons/${props.lesson.data.id}`;

const secondaryButtonClass =
    'cursor-pointer rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';

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
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`${lessonUrl}/edit`" :class="[secondaryButtonClass, 'text-gray-700']">
                        Edit
                    </Link>
                    <button type="button" :class="[secondaryButtonClass, 'text-red-700']" @click="deleteLesson">
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <MagicCard class="p-6">
            <pre
                v-if="lesson.data.content"
                class="font-sans text-sm leading-relaxed wrap-anywhere whitespace-pre-wrap text-gray-800"
            >{{ lesson.data.content }}</pre>
            <p v-else class="text-sm text-gray-600">This lesson has no content yet.</p>
        </MagicCard>
    </AppLayout>
</template>
