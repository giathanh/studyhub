<script setup lang="ts">
import CourseForm from '@/components/CourseForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { StatusOption } from '@/types/course';

defineProps<{
    statuses: StatusOption[];
}>();
</script>

<template>
    <AppLayout title="New course">
        <template #heading>
            <PageHeader title="New course" back-href="/courses" back-label="Courses" />
        </template>

        <CourseForm action="/courses" method="post" :statuses="statuses" submit-label="Create course" />
    </AppLayout>
</template>
