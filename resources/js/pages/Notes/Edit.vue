<script setup lang="ts">
import NoteForm from '@/components/NoteForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CourseOption, Note } from '@/types/course';

defineProps<{
    note: { data: Note };
    courses: CourseOption[];
}>();
</script>

<template>
    <AppLayout title="Edit note">
        <template #heading>
            <PageHeader title="Edit note" :back-href="`/notes/${note.data.id}`" :back-label="note.data.title" />
        </template>

        <NoteForm
            :action="`/notes/${note.data.id}`"
            method="put"
            :courses="courses"
            :note="note.data"
            submit-label="Save changes"
        />
    </AppLayout>
</template>
