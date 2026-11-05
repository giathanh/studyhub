<script setup lang="ts">
import NoteForm from '@/components/NoteForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CourseOption } from '@/types/course';

defineProps<{
    courses: CourseOption[];
    initial_course_id: number | null;
    initial_lesson_id: number | null;
}>();
</script>

<template>
    <AppLayout title="New note">
        <template #heading>
            <PageHeader title="New note" back-href="/notes" back-label="Notes" />
        </template>

        <NoteForm
            action="/notes"
            method="post"
            :courses="courses"
            :initial-course-id="initial_course_id"
            :initial-lesson-id="initial_lesson_id"
            submit-label="Create note"
        />
    </AppLayout>
</template>
