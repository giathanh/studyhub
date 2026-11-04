<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import MagicCard from '@/components/MagicCard.vue';
import MarkdownContent from '@/components/MarkdownContent.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Note } from '@/types/course';

const props = defineProps<{
    note: { data: Note };
}>();

const note = computed(() => props.note.data);

const secondaryButtonClass =
    'cursor-pointer rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';

const dangerButtonClass =
    'cursor-pointer rounded-lg border border-red-600 bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:border-red-700 hover:bg-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600';

function deleteNote(): void {
    if (window.confirm(`Delete "${note.value.title}"? This cannot be undone.`)) {
        router.delete(`/notes/${note.value.id}`);
    }
}
</script>

<template>
    <AppLayout :title="note.title">
        <template #heading>
            <Link href="/notes" class="text-sm font-medium text-brand">← Notes</Link>
            <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight wrap-anywhere text-gray-900">
                        {{ note.title }}
                    </h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                        <Link
                            v-if="note.course"
                            :href="`/courses/${note.course.id}`"
                            class="font-medium text-brand hover:underline"
                        >
                            {{ note.course.title }}
                        </Link>
                        <template v-if="note.lesson">
                            <span v-if="note.course">·</span>
                            <Link
                                :href="`/courses/${note.lesson.course_id}/lessons/${note.lesson.id}`"
                                class="font-medium text-brand hover:underline"
                            >
                                {{ note.lesson.title }}
                            </Link>
                        </template>
                        <template v-if="note.updated_at">
                            <span v-if="note.course || note.lesson">·</span>
                            <span>Updated {{ note.updated_at }}</span>
                        </template>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/notes/${note.id}/edit`" :class="[secondaryButtonClass, 'text-gray-700']">
                        Edit
                    </Link>
                    <button type="button" :class="dangerButtonClass" @click="deleteNote">
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <MagicCard class="p-6">
            <MarkdownContent :content="note.body ?? ''" />
        </MagicCard>
    </AppLayout>
</template>
