<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
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
        <template #heading>
            <PageHeader title="Study sessions">
                <template #meta>
                    Record time you have studied.
                    <Link href="/dashboard" class="rounded font-medium text-brand underline underline-offset-4">
                        See this week’s total on your dashboard.
                    </Link>
                </template>
            </PageHeader>
        </template>

        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            <AppCard as="section" aria-labelledby="log-heading" class="min-w-0 p-6">
                <h2 id="log-heading" class="mb-4 font-serif text-xl font-medium tracking-tight text-ink">Log study time</h2>
                <Form action="/study-sessions" method="get" class="mb-5 flex items-end gap-2 border-b border-line pb-5">
                    <div class="min-w-0 flex-1">
                        <FormField label="Find a lesson (optional)" name="lesson_search" :value="lesson_search" :required="false" />
                    </div>
                    <AppButton type="submit" variant="secondary">Find</AppButton>
                </Form>
                <Form
                    action="/study-sessions"
                    method="post"
                    reset-on-success
                    #default="{ errors, processing }"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <FormField label="Study date" name="studied_on" type="date" :value="today" :error="errors.studied_on" />
                    <FormField label="Minutes (1–1,440)" name="minutes" type="number" :error="errors.minutes" />
                    <div class="sm:col-span-2">
                        <FormSelect
                            label="Related lesson"
                            name="lesson_id"
                            :error="errors.lesson_id"
                            :options="[
                                { value: '', label: 'No related lesson' },
                                ...lessons.data.map((lesson) => ({ value: String(lesson.id), label: lesson.title })),
                            ]"
                        />
                        <p class="mt-2 text-xs text-ink-faint">
                            Showing {{ lessons.data.length }} of {{ lessons.meta.total }} lessons. Search or change page to
                            find another lesson.
                        </p>
                        <PaginationLinks :pagination="lessons" />
                    </div>
                    <div class="sm:col-span-2">
                        <AppButton type="submit" :disabled="processing">
                            {{ processing ? 'Saving…' : 'Record session' }}
                        </AppButton>
                    </div>
                </Form>
            </AppCard>

            <section aria-labelledby="history-heading" class="min-w-0">
                <h2 id="history-heading" class="mb-3 font-serif text-xl font-medium tracking-tight text-ink">Study history</h2>
                <EmptyState
                    v-if="sessions.data.length === 0"
                    title="No study sessions yet"
                    description="Record your first session with the form."
                />
                <AppCard v-else as="ul" class="divide-y divide-line">
                    <li
                        v-for="session in sessions.data"
                        :key="session.id"
                        class="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="min-w-0 flex-1 basis-48">
                            <p class="text-sm font-medium text-ink">{{ session.minutes }} minutes</p>
                            <p class="mt-0.5 text-xs wrap-anywhere text-ink-faint">
                                {{ session.studied_on }}
                                <template v-if="session.lesson">
                                    ·
                                    <Link
                                        :href="`/courses/${session.lesson.course_id}/lessons/${session.lesson.id}`"
                                        class="rounded font-medium text-brand hover:underline"
                                    >
                                        {{ session.lesson.title }}
                                    </Link>
                                </template>
                                <template v-else>· No related lesson</template>
                            </p>
                        </div>
                        <button
                            type="button"
                            :aria-label="`Delete session from ${session.studied_on}, ${session.minutes} minutes`"
                            class="cursor-pointer rounded-md px-2 py-1 text-sm text-danger transition-colors hover:bg-danger-soft"
                            @click="deleteSession(session.id)"
                        >
                            Delete
                        </button>
                    </li>
                </AppCard>
                <PaginationLinks :pagination="sessions" />
            </section>
        </div>
    </AppLayout>
</template>
