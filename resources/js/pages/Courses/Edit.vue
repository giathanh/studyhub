<script setup lang="ts">
import CourseForm from '@/components/CourseForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, StatusOption } from '@/types/course';

defineProps<{
    course: { data: Course };
    statuses: StatusOption[];
}>();
</script>

<template>
    <AppLayout title="Edit course">
        <CourseForm
            :action="`/courses/${course.data.id}`"
            method="put"
            :course="course.data"
            :statuses="statuses"
            submit-label="Save changes"
        />
    </AppLayout>
</template>
