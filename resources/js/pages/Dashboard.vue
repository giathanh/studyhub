<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppButton from '@/components/AppButton.vue';
import AppCard from '@/components/AppCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Course, Paginated } from '@/types/course';

const props = defineProps<{
    courses: Paginated<Course>;
    lessons: Paginated<{ id: number; course_id: number; title: string; course_title: string }>;
    notes: Paginated<{ id: number; title: string }>;
    week: { start: string; end: string; minutes: number };
}>();

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    return hour < 18 ? 'Good afternoon' : 'Good evening';
});

const studyHours = computed(() => Math.floor(props.week.minutes / 60));
const studyMinutes = computed(() => props.week.minutes % 60);

const weekRange = computed(() => {
    const formatter = new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric' });
    const toLocalDate = (date: string): Date => new Date(`${date}T00:00:00`);

    return formatter.formatRange(toLocalDate(props.week.start), toLocalDate(props.week.end));
});

const isNextLesson = (index: number): boolean => index === 0 && props.lessons.meta.current_page === 1;

const sectionTitleClass = 'font-serif text-xl font-medium tracking-tight text-ink';
const sectionLinkClass = 'rounded text-sm font-medium text-brand underline-offset-4 hover:underline';
const rowLinkClass = 'block rounded-lg px-3 py-2.5 transition-colors hover:bg-sunken';
</script>

<template>
    <AppLayout title="Dashboard">
        <template #heading>
            <PageHeader :title="greeting" description="Here’s where your studying stands." />
        </template>

        <section
            aria-labelledby="study-time-heading"
            class="mb-8 flex flex-wrap items-end justify-between gap-6 rounded-xl bg-ink p-6 text-paper sm:p-8"
        >
            <div>
                <h2 id="study-time-heading" class="text-sm font-medium text-paper/80">
                    Study time this week · {{ weekRange }}
                </h2>
                <p class="mt-2 font-serif text-5xl font-medium tracking-tight sm:text-6xl">
                    <template v-if="studyHours > 0">
                        {{ studyHours }}<span class="text-2xl text-paper/80 sm:text-3xl"> h </span>
                    </template>
                    {{ studyMinutes }}<span class="text-2xl text-paper/80 sm:text-3xl"> min</span>
                </p>
                <p v-if="week.minutes === 0" class="mt-2 text-sm text-paper/80">
                    Nothing logged yet this week.
                </p>
            </div>
            <AppButton :as="Link" href="/study-sessions" variant="highlight">Log study time</AppButton>
        </section>

        <div class="grid items-start gap-8 lg:grid-cols-5">
            <section aria-labelledby="courses-heading" class="min-w-0 lg:col-span-3">
                <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="courses-heading" :class="sectionTitleClass">Courses in progress</h2>
                    <Link href="/courses" :class="sectionLinkClass">All courses</Link>
                </div>
                <p v-if="courses.data.length === 0" class="text-sm text-ink-soft">
                    No courses in progress. Open a course and set its status to “In progress” to start.
                </p>
                <ul v-else class="grid gap-3 sm:grid-cols-2">
                    <li v-for="course in courses.data" :key="course.id" class="min-w-0">
                        <AppCard
                            :as="Link"
                            :href="`/courses/${course.id}`"
                            class="flex h-full flex-col justify-between gap-4 p-4 transition-colors hover:border-line-strong"
                        >
                            <span class="font-medium wrap-anywhere text-ink">{{ course.title }}</span>
                            <span class="flex flex-col gap-2">
                                <span class="flex justify-between gap-2 text-xs text-ink-soft">
                                    <span>{{ course.completed_lessons_count }} of {{ course.lessons_count }} lessons</span>
                                    <span class="font-medium text-ink">{{ course.progress }}%</span>
                                </span>
                                <ProgressBar :value="course.progress ?? 0" :label="`${course.title} progress`" />
                            </span>
                        </AppCard>
                    </li>
                </ul>
                <PaginationLinks :pagination="courses" />
            </section>

            <div class="flex min-w-0 flex-col gap-8 lg:col-span-2">
                <section aria-labelledby="lessons-heading">
                    <h2 id="lessons-heading" :class="[sectionTitleClass, 'mb-3']">Unfinished lessons</h2>
                    <p v-if="lessons.data.length === 0" class="text-sm text-ink-soft">
                        No unfinished lessons. Add a lesson from a course when you are ready.
                    </p>
                    <AppCard v-else as="ul" class="p-1.5">
                        <li v-for="(lesson, index) in lessons.data" :key="lesson.id">
                            <Link :href="`/courses/${lesson.course_id}/lessons/${lesson.id}`" :class="rowLinkClass">
                                <span v-if="isNextLesson(index)" class="mb-1 block text-xs font-medium text-ink-faint">
                                    Up next
                                </span>
                                <span class="block text-sm font-medium wrap-anywhere text-ink">
                                    <span :class="{ marker: isNextLesson(index) }">{{ lesson.title }}</span>
                                </span>
                                <span class="block text-xs wrap-anywhere text-ink-faint">{{ lesson.course_title }}</span>
                            </Link>
                        </li>
                    </AppCard>
                    <PaginationLinks :pagination="lessons" />
                </section>

                <section aria-labelledby="notes-heading">
                    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="notes-heading" :class="sectionTitleClass">Recent notes</h2>
                        <Link href="/notes/create" :class="sectionLinkClass">New note</Link>
                    </div>
                    <p v-if="notes.data.length === 0" class="text-sm text-ink-soft">
                        No notes yet. Save a takeaway from your next lesson.
                    </p>
                    <AppCard v-else as="ul" class="p-1.5">
                        <li v-for="note in notes.data" :key="note.id">
                            <Link
                                :href="`/notes/${note.id}`"
                                :class="[rowLinkClass, 'text-sm font-medium wrap-anywhere text-ink']"
                            >
                                {{ note.title }}
                            </Link>
                        </li>
                    </AppCard>
                    <PaginationLinks :pagination="notes" />
                </section>
            </div>
        </div>
    </AppLayout>
</template>
