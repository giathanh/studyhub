<script setup lang="ts">
import LessonForm from '@/components/LessonForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, StatusOption } from '@/types/course';

defineProps<{
    course: { data: Course };
    statuses: StatusOption[];
}>();
</script>

<template>
    <AppLayout title="New lesson">
        <template #heading>
            <PageHeader title="New lesson" :back-href="`/courses/${course.data.id}`" :back-label="course.data.title" />
        </template>

        <LessonForm
            :action="`/courses/${course.data.id}/lessons`"
            method="post"
            :statuses="statuses"
            submit-label="Create lesson"
        />
    </AppLayout>
</template>
