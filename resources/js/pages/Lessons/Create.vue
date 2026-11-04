<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import LessonForm from '@/components/LessonForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, StatusOption } from '@/types/course';

defineProps<{
    course: { data: Course };
    statuses: StatusOption[];
}>();
</script>

<template>
    <AppLayout title="New lesson">
        <Link :href="`/courses/${course.data.id}`" class="mb-4 inline-block text-sm font-medium text-brand">
            ← {{ course.data.title }}
        </Link>
        <LessonForm
            :action="`/courses/${course.data.id}/lessons`"
            method="post"
            :statuses="statuses"
            submit-label="Create lesson"
        />
    </AppLayout>
</template>
