<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import NoteForm from '@/components/NoteForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CourseOption, Note } from '@/types/course';

defineProps<{
    note: { data: Note };
    courses: CourseOption[];
}>();
</script>

<template>
    <AppLayout title="Edit note">
        <Link :href="`/notes/${note.data.id}`" class="mb-4 inline-block text-sm font-medium text-brand">
            ← {{ note.data.title }}
        </Link>
        <NoteForm
            :action="`/notes/${note.data.id}`"
            method="put"
            :courses="courses"
            :note="note.data"
            submit-label="Save changes"
        />
    </AppLayout>
</template>
