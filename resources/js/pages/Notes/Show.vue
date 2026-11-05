<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import MarkdownContent from '@/components/MarkdownContent.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Note } from '@/types/course';

const props = defineProps<{
    note: { data: Note };
}>();

const note = computed(() => props.note.data);

function deleteNote(): void {
    if (window.confirm(`Delete "${note.value.title}"? This cannot be undone.`)) {
        router.delete(`/notes/${note.value.id}`);
    }
}
</script>

<template>
    <AppLayout :title="note.title">
        <template #heading>
            <PageHeader :title="note.title" back-href="/notes" back-label="Notes">
                <template #meta>
                    <span class="flex flex-wrap items-center gap-2">
                        <Link v-if="note.course" :href="`/courses/${note.course.id}`" class="rounded font-medium text-brand hover:underline">
                            {{ note.course.title }}
                        </Link>
                        <template v-if="note.lesson">
                            <span v-if="note.course" aria-hidden="true">·</span>
                            <Link
                                :href="`/courses/${note.lesson.course_id}/lessons/${note.lesson.id}`"
                                class="rounded font-medium text-brand hover:underline"
                            >
                                {{ note.lesson.title }}
                            </Link>
                        </template>
                        <template v-if="note.updated_at">
                            <span v-if="note.course || note.lesson" aria-hidden="true">·</span>
                            <span>Updated {{ note.updated_at }}</span>
                        </template>
                    </span>
                </template>
                <template #actions>
                    <AppButton :as="Link" :href="`/notes/${note.id}/edit`" variant="secondary">Edit</AppButton>
                    <AppButton type="button" variant="danger" @click="deleteNote">Delete</AppButton>
                </template>
            </PageHeader>
        </template>

        <AppCard as="article" class="px-6 py-8 sm:px-10 sm:py-10">
            <MarkdownContent :content="note.body ?? ''" class="mx-auto max-w-[68ch]" />
        </AppCard>
    </AppLayout>
</template>
