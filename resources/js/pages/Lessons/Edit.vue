<script setup lang="ts">
import LessonForm from '@/components/LessonForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Lesson, StatusOption } from '@/types/course';

defineProps<{
    course: { data: Course };
    lesson: { data: Lesson };
    statuses: StatusOption[];
}>();
</script>

<template>
    <AppLayout title="Edit lesson">
        <template #heading>
            <PageHeader title="Edit lesson" :back-href="`/courses/${course.data.id}/lessons/${lesson.data.id}`" :back-label="lesson.data.title" />
        </template>

        <LessonForm
            :action="`/courses/${course.data.id}/lessons/${lesson.data.id}`"
            method="put"
            :statuses="statuses"
            :lesson="lesson.data"
            submit-label="Save changes"
        />
    </AppLayout>
</template>
