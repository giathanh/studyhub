<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import FormField from '@/components/FormField.vue';
import FormSelect from '@/components/FormSelect.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ShimmerButton from '@/components/ShimmerButton.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Paginated } from '@/types/course';

defineProps<{
    sessions: Paginated<{ id: number; studied_on: string; minutes: number; lesson: { id: number; course_id: number; title: string } | null }>;
    lessons: Paginated<{ id: number; title: string }>;
    lesson_search: string;
    today: string;
}>();

function deleteSession(id: number): void {
    if (window.confirm('Delete this study session?')) {
        router.delete(`/study-sessions/${id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout title="Study sessions">
        <p class="mb-6 text-sm text-gray-600">Record time you have studied. <Link href="/dashboard" class="text-brand underline">See this week's total on your dashboard.</Link></p>
        <section class="mb-6 rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold">Log study time</h2>
            <Form action="/study-sessions" method="get" class="mb-5 flex flex-wrap items-end gap-3">
                <div class="min-w-0 flex-1"><FormField label="Find a lesson (optional)" name="lesson_search" :value="lesson_search" :required="false" /></div>
                <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm focus-visible:outline-2 focus-visible:outline-brand">Find</button>
            </Form>
            <Form action="/study-sessions" method="post" reset-on-success #default="{ errors, processing }" class="grid gap-4 sm:grid-cols-2">
                <FormField label="Study date" name="studied_on" type="date" :value="today" :error="errors.studied_on" />
                <FormField label="Minutes (1–1,440)" name="minutes" type="number" :error="errors.minutes" />
                <div class="sm:col-span-2">
                    <FormSelect label="Related lesson" name="lesson_id" :error="errors.lesson_id" :options="[{ value: '', label: 'No related lesson' }, ...lessons.data.map(lesson => ({ value: String(lesson.id), label: lesson.title }))]" />
                    <p class="mt-2 text-xs text-gray-500">Showing {{ lessons.data.length }} of {{ lessons.meta.total }} lessons. Search or change page to find another lesson.</p>
                    <PaginationLinks :pagination="lessons" />
                </div>
                <div class="sm:col-span-2"><ShimmerButton type="submit" :disabled="processing">{{ processing ? 'Saving…' : 'Record session' }}</ShimmerButton></div>
            </Form>
        </section>
        <section class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold">Study history</h2>
            <p v-if="sessions.data.length === 0" class="text-sm text-gray-600">No study sessions yet. Record your first session above.</p>
            <ul v-else class="divide-y divide-gray-200">
                <li v-for="session in sessions.data" :key="session.id" class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div class="min-w-0">
                        <p class="font-medium">{{ session.studied_on }} · {{ session.minutes }} minutes</p>
                        <Link v-if="session.lesson" :href="`/courses/${session.lesson.course_id}/lessons/${session.lesson.id}`" class="text-sm wrap-anywhere text-brand hover:underline">{{ session.lesson.title }}</Link>
                        <p v-else class="text-sm text-gray-500">No related lesson</p>
                    </div>
                    <button type="button" :aria-label="`Delete session from ${session.studied_on}, ${session.minutes} minutes`" class="rounded px-2 py-1 text-sm text-red-700 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-red-600" @click="deleteSession(session.id)">Delete</button>
                </li>
            </ul>
            <PaginationLinks :pagination="sessions" />
        </section>
    </AppLayout>
</template>
