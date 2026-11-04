<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LessonForm from '@/components/LessonForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Lesson } from '@/types/course';

defineProps<{
    course: { data: Course };
    lesson: { data: Lesson };
}>();
</script>

<template>
    <AppLayout title="Edit lesson">
        <Link
            :href="`/courses/${course.data.id}/lessons/${lesson.data.id}`"
            class="mb-4 inline-block text-sm font-medium text-brand"
        >
            ← {{ lesson.data.title }}
        </Link>
        <LessonForm
            :action="`/courses/${course.data.id}/lessons/${lesson.data.id}`"
            method="put"
            :lesson="lesson.data"
            submit-label="Save changes"
        />
    </AppLayout>
</template>
