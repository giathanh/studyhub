<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import NoteForm from '@/components/NoteForm.vue';
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
        <Link href="/notes" class="mb-4 inline-block text-sm font-medium text-brand">← Notes</Link>
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
